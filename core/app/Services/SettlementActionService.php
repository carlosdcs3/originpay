<?php

namespace App\Services;

use App\Models\Settlement;
use App\Models\Wallet;
use App\Services\Financial\WalletBalanceService;
use Illuminate\Support\Facades\DB;
use Exception;

class SettlementActionService
{
    protected $walletBalanceService;

    public function __construct(WalletBalanceService $walletBalanceService)
    {
        $this->walletBalanceService = $walletBalanceService;
    }

    public function forceSettle(Settlement $settlement, int $adminId): bool
    {
        return DB::transaction(function () use ($settlement, $adminId) {
            $lockedSettlement = Settlement::where('id', $settlement->id)->lockForUpdate()->firstOrFail();

            if ($lockedSettlement->status === 'settled') {
                return true;
            }

            if ($lockedSettlement->status !== 'pending' && $lockedSettlement->status !== 'processing') {
                throw new Exception("Apenas repasses pendentes podem ser forçados à liquidação.");
            }

            if (! $lockedSettlement->gateway_id) {
                throw new Exception('Repasse sem Gateway definido.');
            }

            $wallet = Wallet::where('user_id', $lockedSettlement->user_id)->lockForUpdate()->firstOrFail();
            $gatewayId = (int) $lockedSettlement->gateway_id;
            $idempotencyKey = "settlement:{$lockedSettlement->id}:payout";

            $this->walletBalanceService->debitGateway($wallet->id, $gatewayId, (float) $lockedSettlement->net_amount, [
                'transaction_type' => 'settlement_payout',
                'correlation_id' => $idempotencyKey,
                'idempotency_key' => $idempotencyKey,
                'description' => 'Liquidação de repasse forçada. Destino: ' . $lockedSettlement->destination,
                'reference_type' => Settlement::class,
                'reference_id' => $lockedSettlement->id,
                'metadata' => [
                    'admin_id' => $adminId,
                    'settlement_id' => $lockedSettlement->id,
                    'gateway_id' => $gatewayId,
                ],
            ]);

            $metadata = $lockedSettlement->metadata ?? [];
            $metadata['settlement_idempotency_key'] = $idempotencyKey;
            $metadata['settled_by_admin_id'] = $adminId;

            $lockedSettlement->status = 'settled';
            $lockedSettlement->settled_date = now();
            $lockedSettlement->metadata = $metadata;
            $lockedSettlement->save();

            return true;
        });
    }
}
