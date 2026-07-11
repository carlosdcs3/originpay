<?php

namespace App\Services;

use App\Models\FeeRecord;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FeeActionService
{
    /**
     * Recalcula uma taxa que sofreu divergência com o Gateway.
     */
    public function recalculate(FeeRecord $feeRecord, int $adminId): bool
    {
        return DB::transaction(function () use ($feeRecord, $adminId) {
            // Se o gateway cobrou mais do que o esperado, a margem despenca.
            // Para efeitos de painel, o administrador pode aprovar a diferença.
            
            // 1. Atualiza Margem
            $feeRecord->margin = $feeRecord->merchant_fee - $feeRecord->gateway_cost;
            $feeRecord->status = 'confirmed';
            $feeRecord->save();

            // 2. Registra ajuste no Ledger (caso houvesse necessidade de reter ou reembolsar o merchant)
            // Aqui assumimos que a divergência não é repassada ao Merchant (quem assume o prejuízo é a Plataforma),
            // logo o Ledger afeta apenas a conta matriz.
            WalletTransaction::create([
                'user_id' => $feeRecord->user_id,
                'wallet_id' => null, 
                'type' => 'fee_adjustment',
                'amount' => 0, // A ser refinado
                'correlation_id' => $feeRecord->reference_id,
                'idempotency_key' => Str::uuid()->toString(),
                'balance_before' => 0, 
                'balance_after' => 0,
                'description' => 'Ajuste de taxa divergente. Ref: ' . $feeRecord->id,
                'reference_type' => FeeRecord::class,
                'reference_id' => $feeRecord->id,
            ]);

            return true;
        });
    }
}
