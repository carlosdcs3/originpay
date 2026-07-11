<?php

namespace App\Services\Handlers;

use App\Enums\MethodType;
use App\Models\Admin;
use App\Models\Transaction;
use App\Notifications\TemplateNotification;
use App\Services\Financial\WalletBalanceService;
use App\Services\Handlers\Interfaces\FailHandlerInterface;
use App\Services\Handlers\Interfaces\SubmittedHandlerInterface;
use App\Services\Handlers\Interfaces\SuccessHandlerInterface;
use App\Services\Security\TenantBypass;
use App\Services\TransactionNotifierService;
use Notification;
use Wallet;

class WithdrawHandler implements FailHandlerInterface, SubmittedHandlerInterface, SuccessHandlerInterface
{
    protected TransactionNotifierService $notifier;

    public function __construct(TransactionNotifierService $notifier)
    {
        $this->notifier = $notifier;
    }

    public function handleSuccess(Transaction $transaction): void
    {
        $wallet = TenantBypass::run(
            fn () => \App\Models\Wallet::where('uuid', $transaction->wallet_reference)->firstOrFail()
        );
        
        // Read fees from snapshot
        $trxData = $transaction->trx_data ?? [];
        $snapshot = $trxData['fee_snapshot'] ?? null;
        
        $settledAmount = (float) $transaction->payable_amount;
        $platformFee = (float) ($snapshot['platform_fee_amount'] ?? $transaction->fee);
        $platformFee = min($platformFee, $settledAmount);
        $netAmount = $settledAmount - $platformFee;
        $selectedGatewayId = $trxData['gateway_id'] ?? null;
        
        $provider = $transaction->provider ?? 'EFI';
        $gatewayPayoutUuid = 'GATEWAY_' . $provider . '_PIX_PAYOUT_HOLDING';
        $systemRevenue = TenantBypass::run(
            fn () => \App\Models\Wallet::where('uuid', \App\Enums\SystemWalletUUID::SYSTEM_REVENUE->value)->firstOrFail()
        );

        $gatewayPayoutWallet = TenantBypass::run(fn () => \App\Models\Wallet::firstOrCreate(
            ['uuid' => $gatewayPayoutUuid],
            [
                'user_id' => $systemRevenue->user_id,
                'currency_id' => $systemRevenue->currency_id,
                'balance' => 0,
                'available_balance' => 0,
            ]
        ));

        if ($selectedGatewayId) {
            app(WalletBalanceService::class)->settleWithdrawalFunds(
                $wallet->id,
                (int) $selectedGatewayId,
                $settledAmount,
                [
                    'transaction_type' => 'legacy_withdraw_settlement',
                    'description' => 'Legacy withdrawal approval settlement',
                    'correlation_id' => $transaction->trx_id,
                    'idempotency_key' => 'legacy-withdraw-settlement:'.$transaction->trx_id,
                    'reference_type' => Transaction::class,
                    'reference_id' => $transaction->id,
                ]
            );
            $wallet = TenantBypass::run(
                fn () => \App\Models\Wallet::whereKey($wallet->id)->firstOrFail()
            );
        }

        // 1. Credit fee to system revenue without debiting the source wallet again
        if ($platformFee > 0) {
            app(\App\Services\LedgerService::class)->credit(
                $systemRevenue,
                $platformFee,
                $transaction,
                "Withdraw Fee for {$transaction->trx_id}",
                ['transaction_id' => $transaction->id, 'currency' => $transaction->currency, 'bypass_feature_flags' => true]
            );
        }

        // 2. Credit net value to payout holding, then consume it as an external payout
        if ($netAmount > 0) {
            app(\App\Services\LedgerService::class)->credit(
                $gatewayPayoutWallet,
                $netAmount,
                $transaction,
                "Withdraw Holding for {$transaction->trx_id}",
                ['transaction_id' => $transaction->id, 'currency' => $transaction->currency, 'bypass_feature_flags' => true]
            );

            // 3. Subtract from Payout Holding (External Network)
            // This represents money leaving the gateway to the real world
            $gatewayPayoutWallet = TenantBypass::run(
                fn () => \App\Models\Wallet::whereKey($gatewayPayoutWallet->id)->lockForUpdate()->firstOrFail()
            );
            $gatewayPayoutWallet->balance -= $netAmount;
            $gatewayPayoutWallet->available_balance -= $netAmount;
            $gatewayPayoutWallet->save();
        }

        $this->notifier->toUser($transaction, 'withdraw_user_approved', [
            'amount' => $transaction->amount.' '.$transaction->currency,
            'method' => $transaction->provider,
        ]);

        if ($transaction->processing_type === MethodType::AUTOMATIC) {
            $admins = Admin::permission('withdraw-notification')->get();

            Notification::send($admins, new TemplateNotification(
                identifier: 'withdraw_admin_auto_processed',
                data: [
                    'user'   => $transaction->user->name,
                    'amount' => $transaction->amount.' '.$transaction->currency,
                    'method' => $transaction->provider,
                    'trx'    => $transaction->trx_id,
                ],
                sender: $transaction->user
            ));
        }
    }

    public function handleFail(Transaction $transaction): void
    {
        $this->notifier->toUser($transaction, 'withdraw_user_rejected', [
            'amount' => $transaction->amount.' '.$transaction->currency,
            'method' => $transaction->provider,
            'reason' => $transaction->remarks,
        ]);
    }

    public function handleSubmitted(Transaction $transaction): void
    {
        // Notify user
        $this->notifier->toUser($transaction, 'withdraw_user_requested', [
            'amount' => $transaction->amount.' '.$transaction->currency,
            'method' => $transaction->provider,
            'trx'    => $transaction->trx_id,
        ]);

        // Notify admin
        $this->notifier->toAdmins('withdraw-notification', 'withdraw_admin_manual_submitted', [
            'user'   => $transaction->user->name,
            'amount' => $transaction->amount.' '.$transaction->currency,
            'method' => $transaction->provider,
            'trx'    => $transaction->trx_id,
        ],
            sender: $transaction->user,
            action: route('admin.withdraw.manual-request')
        );

    }
}
