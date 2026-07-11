<?php

namespace App\Services\Finance;

use App\Models\Transaction;

class ChargebackActionService
{
    /**
     * Stub para ações futuras do módulo de Chargeback
     */
    public function markAsWon(Transaction $transaction)
    {
        // ... Logica de disputa ganha
    }

    public function markAsLost(Transaction $transaction)
    {
        // ... Logica de disputa perdida
    }
}
