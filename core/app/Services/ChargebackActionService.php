<?php

namespace App\Services;

use App\Models\Chargeback;
use App\Services\Financial\WalletBalanceService;
use Illuminate\Support\Str;

class ChargebackActionService
{
    protected $walletBalanceService;

    public function __construct(WalletBalanceService $walletBalanceService)
    {
        $this->walletBalanceService = $walletBalanceService;
    }

    public function receive(Chargeback $chargeback, int $adminId): bool
    {
        $chargeback->status = 'disputed';
        $chargeback->save();

        $walletId = 1; // $chargeback->user->wallet->id
        $gatewayId = $chargeback->gateway_id;

        // Service movimenta o dinheiro, gera Ledger e Audit.
        $this->walletBalanceService->blockFunds($walletId, $gatewayId, $chargeback->amount, [
            'transaction_type' => 'chargeback_hold',
            'correlation_id' => $chargeback->provider_reference,
            'idempotency_key' => Str::uuid()->toString(),
            'description' => 'Bloqueio cautelar por Chargeback. Motivo: ' . $chargeback->reason,
            'reference_type' => Chargeback::class,
            'reference_id' => $chargeback->id,
        ]);

        return true;
    }
}
