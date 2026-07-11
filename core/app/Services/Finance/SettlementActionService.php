<?php

namespace App\Services\Finance;

use App\Models\Settlement;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Enums\Finance\TransactionStatus;
use App\Enums\Finance\TransactionOperation;
use App\Services\Financial\WalletBalanceService;
use Illuminate\Support\Facades\DB;
use Exception;

class SettlementActionService
{
    public function __construct(private readonly WalletBalanceService $walletBalanceService)
    {
    }

    /**
     * Efetua o pagamento (liquidação) do repasse, garantindo integridade financeira no gateway.
     */
    public function pay(Settlement $settlement): void
    {
        DB::transaction(function () use ($settlement) {
            $lockedSettlement = Settlement::where('id', $settlement->id)->lockForUpdate()->firstOrFail();

            if ($lockedSettlement->status === TransactionStatus::SUCCEEDED->value || $lockedSettlement->status === 'settled') {
                return;
            }

            if ($lockedSettlement->status !== TransactionStatus::PENDING->value && $lockedSettlement->status !== 'pending') {
                throw new Exception("Repasse não está pendente.");
            }

            if (!$lockedSettlement->gateway_id) {
                throw new Exception("Repasse sem Gateway definido.");
            }

            $wallet = Wallet::where('user_id', $lockedSettlement->user_id)->lockForUpdate()->first();
            if (!$wallet) {
                throw new Exception("Wallet do usuário não encontrada.");
            }

            $idempotencyKey = "settlement:{$lockedSettlement->id}:payout";

            $walletTransaction = $this->walletBalanceService->debitGateway($wallet->id, (int) $lockedSettlement->gateway_id, (float) $lockedSettlement->net_amount, [
                'transaction_type' => 'settlement_payout',
                'correlation_id' => $idempotencyKey,
                'idempotency_key' => $idempotencyKey,
                'description' => 'Liquidação de repasse. Destino: ' . $lockedSettlement->destination,
                'reference_type' => Settlement::class,
                'reference_id' => $lockedSettlement->id,
                'metadata' => [
                    'settlement_id' => $lockedSettlement->id,
                    'gateway_id' => $lockedSettlement->gateway_id,
                ],
            ]);

            $existingTransaction = Transaction::where('trx_reference', $idempotencyKey)->first();
            if (!$existingTransaction) {
                $trx = new Transaction();
                $trx->user_id = $lockedSettlement->user_id;
                $trx->wallet_reference = $wallet->uuid;
                $trx->gateway_id = $lockedSettlement->gateway_id;
                $trx->amount = -$lockedSettlement->net_amount;
                $trx->net_amount = -$lockedSettlement->net_amount;
                $trx->charge = 0;
                $trx->trx_type = '-';
                $trx->operation = TransactionOperation::SETTLEMENT_PAID->value;
                $trx->status = TransactionStatus::SUCCEEDED->value;
                $trx->trx_reference = $idempotencyKey;
                $trx->trx_data = [
                    'settlement_id' => $lockedSettlement->id,
                    'wallet_transaction_id' => $walletTransaction->id,
                ];
                $trx->save();
            }

            $metadata = $lockedSettlement->metadata ?? [];
            $metadata['settlement_idempotency_key'] = $idempotencyKey;
            $metadata['wallet_transaction_id'] = $walletTransaction->id;

            $lockedSettlement->status = TransactionStatus::SUCCEEDED->value;
            $lockedSettlement->settled_date = now();
            $lockedSettlement->metadata = $metadata;
            $lockedSettlement->save();
        });
    }
}
