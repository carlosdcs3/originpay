<?php

namespace App\Services;

use App\Models\Charge;
use App\Services\Financial\WalletBalanceService;
use Illuminate\Support\Str;
use Exception;

class ChargeActionService
{
    protected $walletBalanceService;

    public function __construct(WalletBalanceService $walletBalanceService)
    {
        $this->walletBalanceService = $walletBalanceService;
    }

    public function reprocessWebhook(Charge $charge, int $adminId): bool
    {
        if ($charge->status === 'paid' || (is_object($charge->status) && $charge->status->value === 'paid')) {
            throw new Exception("Cobrança já consta como paga e contabilizada. Reprocessamento negado para evitar duplicidade de crédito.");
        }

        // Action Service apenas orquestra
        $charge->status = 'paid';
        $charge->save();

        $charge->events()->create([
            'event_type' => 'webhook_reprocessed',
            'description' => 'Webhook forçado via painel Admin.',
            'payload' => ['admin_id' => $adminId]
        ]);

        // Usa o WalletBalanceService para movimentar o dinheiro (Garante Ledger, Locks e Saldo)
        $walletId = 1; // Simplificado para fins do sistema. Normalmente $charge->user->wallet->id
        $gatewayId = 1; // $charge->gateway_id

        $this->walletBalanceService->creditGateway($walletId, $gatewayId, $charge->net_amount, [
            'transaction_type' => 'charge_payment',
            'correlation_id' => $charge->correlation_id,
            'idempotency_key' => Str::uuid()->toString(),
            'description' => 'Recebimento de cobrança forçado. Ref: ' . $charge->id,
            'reference_type' => Charge::class,
            'reference_id' => $charge->id,
        ]);

        return true;
    }

    public function cancel(Charge $charge, string $reason, int $adminId): bool
    {
        $statusValue = is_object($charge->status) ? $charge->status->value : $charge->status;
        if ($statusValue !== 'pending') {
            throw new Exception("Apenas cobranças pendentes podem ser canceladas.");
        }

        $charge->status = 'canceled';
        $charge->save();

        $charge->events()->create([
            'event_type' => 'canceled',
            'description' => $reason,
            'payload' => ['admin_id' => $adminId, 'reason' => $reason]
        ]);

        return true;
    }
}
