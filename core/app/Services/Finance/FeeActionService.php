<?php

namespace App\Services\Finance;

use App\Models\FeeRecord;
use Illuminate\Support\Facades\DB;

class FeeActionService
{
    /**
     * Stub para ações futuras do módulo de Taxas (recalcular margem, aceitar divergência).
     * Toda ação deve passar por DB::transaction, auditar e gerar rastro no Ledger se envolver dinheiro.
     */
    public function acceptDivergence(FeeRecord $fee): void
    {
        DB::transaction(function () use ($fee) {
            // Lógica de auditoria, possível ajuste no ledger (Transaction)
            // e confirmação do status
            $fee->status = \App\Enums\Finance\FeeStatus::CONFIRMED->value;
            $fee->save();
        });
    }
}
