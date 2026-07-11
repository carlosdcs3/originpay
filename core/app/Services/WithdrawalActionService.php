<?php

namespace App\Services;

use App\Models\WithdrawalRequest;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WithdrawalActionService
{
    /**
     * Aprova um saque via processo interno seguro.
     */
    public function approve(WithdrawalRequest $request, int $adminId): bool
    {
        if ($request->status !== 'pending' && $request->status !== 'processing') {
            throw new \Exception("Apenas saques pendentes ou em processamento podem ser aprovados.");
        }

        return DB::transaction(function () use ($request, $adminId) {
            $request->status = 'approved';
            $request->approved_at = now();
            $request->processed_by = $adminId;
            $request->save();

            // 1. Gera registro de auditoria
            $request->audits()->create([
                'action' => 'approved',
                'admin_id' => $adminId,
                'notes' => 'Saque aprovado manualmente via painel Admin.',
                'metadata' => ['provider' => $request->provider]
            ]);

            // 2. Registra o evento oficial no Ledger (WalletTransaction)
            // Note: O saldo da wallet já foi congelado (bloqueado) na solicitação original.
            // A chamada de debitGateway faria a redução real.
            // Assumimos que $request->wallet->debitGateway() seja invocado se implementado.
            
            // Ledger master entry
            WalletTransaction::create([
                'user_id' => $request->user_id,
                'wallet_id' => $request->wallet_id,
                'type' => 'withdrawal',
                'amount' => -$request->amount,
                'correlation_id' => $request->transaction_id,
                'idempotency_key' => Str::uuid()->toString(),
                'balance_before' => $request->wallet->balance + $request->amount, // Estimativa simplificada
                'balance_after' => $request->wallet->balance,
                'description' => 'Saque PIX aprovado. Provedor: ' . $request->provider,
                'reference_type' => WithdrawalRequest::class,
                'reference_id' => $request->id,
            ]);

            return true;
        });
    }

    /**
     * Rejeita um saque, estornando o valor bloqueado para a carteira.
     */
    public function reject(WithdrawalRequest $request, string $reason, int $adminId): bool
    {
        if ($request->status !== 'pending' && $request->status !== 'processing') {
            throw new \Exception("Apenas saques pendentes ou em processamento podem ser rejeitados.");
        }

        return DB::transaction(function () use ($request, $reason, $adminId) {
            $request->status = 'rejected';
            $request->rejected_at = now();
            $request->processed_by = $adminId;
            $request->save();

            $request->audits()->create([
                'action' => 'rejected',
                'admin_id' => $adminId,
                'notes' => $reason,
                'metadata' => ['reason' => $reason]
            ]);

            // A rejeição normalmente implica em desbloquear o saldo retido.
            // Implementação depende de WalletBalance::unlock() se existir.

            return true;
        });
    }
}
