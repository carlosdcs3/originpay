<?php

namespace App\Services\Finance;

use App\Models\LedgerEntry;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReconciliationService
{
    /**
     * Run a full reconciliation on a batch of wallets.
     * Returns an array of discrepancies.
     */
    public function reconcileWallets(int $chunkSize = 100, array $filters = []): array
    {
        $discrepancies = [];

        $query = Wallet::query();
        if (!empty($filters['wallet_id'])) {
            $query->where('id', $filters['wallet_id'])->orWhere('uuid', $filters['wallet_id']);
        }

        $query->chunk($chunkSize, function ($wallets) use (&$discrepancies) {
            foreach ($wallets as $wallet) {
                // Calculate theoretical balances based strictly on Ledger Entries
                $totals = LedgerEntry::where('wallet_id', $wallet->id)
                    ->select(
                        DB::raw("SUM(CASE WHEN direction = 'credit' THEN amount ELSE 0 END) as total_credits"),
                        DB::raw("SUM(CASE WHEN direction = 'debit' THEN amount ELSE 0 END) as total_debits")
                    )
                    ->first();

                $theoreticalBalance = ($totals->total_credits ?? 0) - ($totals->total_debits ?? 0);
                $actualBalance = $wallet->balance; // Considering overall balance 

                if (round((float)$theoreticalBalance, 2) !== round((float)$actualBalance, 2)) {
                    $discrepancies[] = [
                        'type' => 'CRITICAL',
                        'wallet_id' => $wallet->id,
                        'wallet_uuid' => $wallet->uuid,
                        'message' => 'Wallet balance does not match Ledger sum.',
                        'expected' => $theoreticalBalance,
                        'actual' => $actualBalance,
                        'diff' => $actualBalance - $theoreticalBalance,
                    ];
                }
            }
        });

        return $discrepancies;
    }

    /**
     * Reconcile completed transactions against the ledger.
     * Ensures every COMPLETED charge has a corresponding ledger entry,
     * and no FAILED charge has a ledger entry.
     */
    public function reconcileTransactions(string $dateRangeStart, string $dateRangeEnd, int $chunkSize = 500, array $filters = []): array
    {
        $discrepancies = [];

        $query = Transaction::whereBetween('created_at', [$dateRangeStart, $dateRangeEnd]);
        
        if (!empty($filters['wallet_id'])) {
            $query->where('wallet_reference', $filters['wallet_id']) // Assuming uuid is passed or fallback if id
                  ->orWhereHas('wallet', function($q) use ($filters) {
                      $q->where('id', $filters['wallet_id']);
                  });
        }
        
        if (!empty($filters['charge'])) {
            $query->where('trx_id', $filters['charge'])->orWhere('id', $filters['charge']);
        }
        
        if (!empty($filters['gateway'])) {
            $query->where('provider', $filters['gateway']);
        }

        $query->chunk($chunkSize, function ($transactions) use (&$discrepancies) {
                foreach ($transactions as $trx) {
                    $hasLedger = LedgerEntry::where('transaction_id', $trx->id)->exists();

                    if ($trx->status === \App\Enums\TrxStatus::COMPLETED && !$hasLedger) {
                        // Se é uma transação de movimentação que deve ter ledger
                        if (in_array($trx->trx_type, [\App\Enums\TrxType::DEPOSIT, \App\Enums\TrxType::WITHDRAW, \App\Enums\TrxType::TRANSFER, \App\Enums\TrxType::REFUND])) {
                            $discrepancies[] = [
                                'type' => 'CRITICAL',
                                'transaction_id' => $trx->id,
                                'trx_id' => $trx->trx_id,
                                'message' => 'Transaction is COMPLETED but has no Ledger entries.',
                            ];
                        }
                    }

                    if ($trx->status === \App\Enums\TrxStatus::FAILED && $hasLedger) {
                        $discrepancies[] = [
                            'type' => 'CRITICAL',
                            'transaction_id' => $trx->id,
                            'trx_id' => $trx->trx_id,
                            'message' => 'Transaction is FAILED but has Ledger entries (Potential Double Spend / Improper Rollback).',
                        ];
                    }
                }
            });

        return $discrepancies;
    }

    /**
     * Reconcile PSP Webhooks vs Transactions
     * Identifies duplicate webhooks or unhandled PSP events.
     */
    public function reconcileWebhooks(string $dateRangeStart, string $dateRangeEnd, array $filters = []): array
    {
        $discrepancies = [];

        $query = \App\Models\ProcessedEvent::whereBetween('created_at', [$dateRangeStart, $dateRangeEnd])
            ->where('event_type', 'webhook')
            ->where('status', 'processed');

        if (!empty($filters['gateway'])) {
            $query->where('source', $filters['gateway']);
        }
        
        if (!empty($filters['charge'])) {
            $query->where('source_id', $filters['charge']);
        }

        $query->chunk(500, function($events) use (&$discrepancies) {
             foreach($events as $event) {
                 $transaction = Transaction::where('trx_id', $event->source_id)->first();
                 if ($transaction && $transaction->status === \App\Enums\TrxStatus::PENDING) {
                     $discrepancies[] = [
                         'type' => 'WARNING', 
                         'trx_id' => $transaction->trx_id,
                         'message' => 'Webhook marked as processed but Transaction is still PENDING.'
                     ];
                 }
             }
        });

        return $discrepancies;
    }
}
