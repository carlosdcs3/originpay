<?php

namespace App\Services\Payment;

use App\Models\User;
use App\Models\Wallet;
use App\Models\WithdrawalRequest;
use App\Models\WithdrawalAudit;
use App\Models\WithdrawalSetting;
use App\Models\Transaction;
use App\Services\LedgerService;
use App\Services\TransactionService;
use App\Services\Security\TenantBypass;
use Illuminate\Support\Facades\DB;

class WithdrawalService
{
    protected LedgerService $ledgerService;
    protected WithdrawalRiskService $riskService;
    protected GatewayFeeService $feeService;

    public function __construct(LedgerService $ledgerService, WithdrawalRiskService $riskService, GatewayFeeService $feeService)
    {
        $this->ledgerService = $ledgerService;
        $this->riskService = $riskService;
        $this->feeService = $feeService;
    }

    public function requestWithdrawal(User $user, float $amount, string $pixKey, string $pixKeyType, array $pixOwner = []): WithdrawalRequest
    {
        $settings = WithdrawalSetting::first();
        if (!$settings || !$settings->withdraw_enabled) {
            throw new \Exception("Withdrawals are currently disabled.");
        }

        if ($amount < $settings->minimum_amount || $amount > $settings->maximum_amount) {
            throw new \Exception("Amount is outside allowed limits.");
        }

        $liquidityStatus = app(\App\Services\Treasury\LiquidityProtectionService::class)->evaluateLiquidity();
        if ($liquidityStatus === 'CRITICAL') {
            throw new \Exception("Withdrawals are temporarily blocked due to critical liquidity levels.");
        }

        // Calculate Fees via GatewayFeeService (Snapshot)
        $feeCalculation = $this->feeService->calculateForWithdraw($amount, 'EFI');

        if (!\App\Models\FeatureFlag::isActive('withdrawals_enabled')) {
            throw new \App\Exceptions\NotifyErrorException("Withdrawals are currently disabled globally by the administrator.");
        }

        $lockKey = "user_withdraw_limit_{$user->id}";

        return \Illuminate\Support\Facades\Cache::lock($lockKey, 10)->block(0, function () use ($user, $amount, $pixKey, $pixKeyType, $pixOwner, $settings, $feeCalculation) {
            
            // Re-evaluate limits before anything
            app(\App\Services\Compliance\UserExposureService::class)->evaluateExposure($user);
            if (app(\App\Services\Compliance\AccountRestrictionService::class)->hasRestriction($user, 'KYC_LIMIT_LOCK')) {
                throw new \Exception("Withdrawal blocked: KYC limit exceeded.");
            }

            return DB::transaction(function () use ($user, $amount, $pixKey, $pixKeyType, $pixOwner, $settings, $feeCalculation) {
                // Lock Wallet
                $wallet = TenantBypass::run(
                    fn () => Wallet::where('user_id', $user->id)->lockForUpdate()->firstOrFail()
                );

                if ($wallet->available_balance < $amount) {
                    throw new \Exception("Insufficient available balance.");
                }

                // Apply Reservation logic
                $wallet->available_balance -= $amount;
                $wallet->reserved_balance += $amount;
                $wallet->save();

                // Create Ledger Reservation Entry
                $this->ledgerService->recordInternal(
                    $wallet,
                    $wallet,
                    $amount,
                    'BRL',
                    'RESERVATION',
                    "Withdrawal Reservation",
                    []
                );

                // Create WithdrawalRequest
                $request = WithdrawalRequest::create([
                    'user_id' => $user->id,
                    'wallet_id' => $wallet->id,
                    'amount' => $amount,
                    'fee_amount' => $feeCalculation->platform_fee_amount,
                    'net_amount' => $feeCalculation->net_amount,
                    'pix_key_snapshot' => $pixKey,
                    'pix_key_type' => $pixKeyType,
                    'pix_owner_name' => $pixOwner['name'] ?? null,
                    'pix_owner_document' => $pixOwner['document'] ?? null,
                    'status' => 'PENDING',
                    'metadata' => [
                        'fee_snapshot' => $feeCalculation->toArray()
                    ]
                ]);

                WithdrawalAudit::create([
                    'withdrawal_id' => $request->id,
                    'user_id' => $user->id,
                    'action' => 'request',
                    'reason' => 'User initiated withdrawal',
                    'ip_address' => request()->ip()
                ]);

                // Auto-Approval Check
                if ($settings->auto_approve_enabled) {
                    $risk = $this->riskService->evaluateRisk($user, $amount, $pixKey, $pixKeyType);
                    if ($risk === 'APPROVE') {
                        $this->approveWithdrawal($request, null, 'Auto-Approved by Risk Engine');
                    } elseif ($risk === 'MANUAL_REVIEW') {
                        $request->status = 'MANUAL_REVIEW';
                        $request->save();

                        WithdrawalAudit::create([
                            'withdrawal_id' => $request->id,
                            'user_id' => $user->id,
                            'action' => 'manual_review',
                            'reason' => 'Risk engine requested manual review',
                            'ip_address' => request()->ip()
                        ]);
                    } elseif ($risk === 'REJECTED') {
                        $this->releaseReservation($request);

                        $request->status = 'REJECTED';
                        $request->rejected_at = now();
                        $request->save();

                        WithdrawalAudit::create([
                            'withdrawal_id' => $request->id,
                            'user_id' => $user->id,
                            'action' => 'reject',
                            'reason' => 'Risk engine rejected withdrawal',
                            'ip_address' => request()->ip()
                        ]);
                    }
                }

                return $request;
            });
        });
    }

    public function approveWithdrawal(WithdrawalRequest $request, ?int $adminId = null, string $reason = 'Manual Approval'): void
    {
        DB::transaction(function () use ($request, $adminId, $reason) {
            $request = TenantBypass::run(
                fn () => WithdrawalRequest::lockForUpdate()->findOrFail($request->id)
            );
            if ($request->status !== 'PENDING') {
                throw new \Exception("Cannot approve non-pending withdrawal.");
            }

            $request->status = 'APPROVED';
            $request->approved_at = now();
            $request->save();

            WithdrawalAudit::create([
                'withdrawal_id' => $request->id,
                'admin_id' => $adminId,
                'action' => 'approve',
                'reason' => $reason
            ]);

            // Dispatch processing job if amount <= 500, else wait for batch
            if ($request->amount <= 500) {
                dispatch(new \App\Jobs\ProcessWithdrawalJob($request->id))->onQueue('high');
            } else {
                $request->status = 'PENDING_BATCH';
                $request->save();
            }
        });
    }

    public function rejectWithdrawal(WithdrawalRequest $request, ?int $adminId = null, string $reason = 'Manual Rejection'): void
    {
        DB::transaction(function () use ($request, $adminId, $reason) {
            $request = TenantBypass::run(
                fn () => WithdrawalRequest::lockForUpdate()->findOrFail($request->id)
            );
            if (!in_array($request->status, ['PENDING', 'APPROVED'])) {
                throw new \Exception("Cannot reject this withdrawal.");
            }

            $this->releaseReservation($request);

            $request->status = 'REJECTED';
            $request->rejected_at = now();
            $request->save();

            WithdrawalAudit::create([
                'withdrawal_id' => $request->id,
                'admin_id' => $adminId,
                'action' => 'reject',
                'reason' => $reason
            ]);
        });
    }

    public function processWithdrawal(WithdrawalRequest $request): void
    {
        // Check Liquidity
        app(LiquidityGuardService::class)->validateLiquidityOrThrow($request->amount);

        $liquidityStatus = app(\App\Services\Treasury\LiquidityProtectionService::class)->evaluateLiquidity();
        if ($liquidityStatus === 'CRITICAL') {
            throw new \Exception("Processing is temporarily blocked due to critical liquidity levels.");
        }

        DB::transaction(function () use ($request) {
            $request = TenantBypass::run(
                fn () => WithdrawalRequest::lockForUpdate()->findOrFail($request->id)
            );
            if ($request->status !== 'APPROVED') {
                throw new \Exception("Cannot process non-approved withdrawal.");
            }

            $request->status = 'PROCESSING';
            $request->save();

            WithdrawalAudit::create([
                'withdrawal_id' => $request->id,
                'action' => 'process',
                'reason' => 'Sent to Gateway'
            ]);

            // Here we would call EfiGateway->sendPix()
            // In the real system, it's done within ProcessWithdrawalJob, 
            // but this is the wrapper that job calls.
        });
    }

    public function completeWithdrawal(WithdrawalRequest $request, string $gatewayTxId): void
    {
        DB::transaction(function () use ($request, $gatewayTxId) {
            $request = TenantBypass::run(
                fn () => WithdrawalRequest::lockForUpdate()->findOrFail($request->id)
            );
            if ($request->status !== 'PROCESSING') {
                throw new \Exception("Cannot complete non-processing withdrawal.");
            }

            $wallet = TenantBypass::run(
                fn () => Wallet::lockForUpdate()->findOrFail($request->wallet_id)
            );

            // Deduct the reserved balance permanently
            $wallet->reserved_balance -= $request->amount;
            $wallet->balance -= $request->amount; // Total balance finally drops
            $wallet->save();

            // Create Transaction for history
            $txData = new \App\Data\TransactionData(
                user_id: $request->user_id,
                trx_type: \App\Enums\TrxType::WITHDRAW,
                amount: $request->amount,
                amount_flow: \App\Enums\AmountFlow::MINUS,
                provider: $request->provider,
                processing_type: \App\Enums\MethodType::AUTOMATIC,
                wallet_reference: $wallet->uuid,
                trx_data: ['fee_snapshot' => $request->metadata['fee_snapshot'] ?? []],
                status: \App\Enums\TrxStatus::COMPLETED,
            );
            
            $transactionService = app(TransactionService::class);
            $transaction = $transactionService->create($txData);
            $transaction->trx_id = $gatewayTxId;
            $transaction->status = \App\Enums\TrxStatus::COMPLETED;
            $transaction->save();
            
            $request->transaction_id = $transaction->trx_id;

            $request->status = 'COMPLETED';
            $request->processed_at = now();
            $request->save();

            WithdrawalAudit::create([
                'withdrawal_id' => $request->id,
                'action' => 'complete',
                'reason' => 'Gateway confirmed settlement'
            ]);
        });
    }

    public function failWithdrawal(WithdrawalRequest $request, string $reason): void
    {
        DB::transaction(function () use ($request, $reason) {
            $request = TenantBypass::run(
                fn () => WithdrawalRequest::lockForUpdate()->findOrFail($request->id)
            );
            if (!in_array($request->status, ['PROCESSING', 'APPROVED'])) {
                throw new \Exception("Cannot fail this withdrawal.");
            }

            $this->releaseReservation($request);

            $request->status = 'FAILED';
            $request->save();

            WithdrawalAudit::create([
                'withdrawal_id' => $request->id,
                'action' => 'fail',
                'reason' => $reason
            ]);
        });
    }

    protected function releaseReservation(WithdrawalRequest $request): void
    {
        $wallet = TenantBypass::run(
            fn () => Wallet::lockForUpdate()->findOrFail($request->wallet_id)
        );
        
        $wallet->reserved_balance -= $request->amount;
        $wallet->available_balance += $request->amount;
        $wallet->save();

        $this->ledgerService->recordInternal(
            $wallet,
            $wallet,
            $request->amount,
            'BRL',
            'RESERVATION_RELEASE',
            "Withdrawal Reservation Release",
            []
        );
    }
}
