<?php

namespace App\Services\Treasury;

use App\Models\Transaction;
use App\Models\WithdrawalRequest;
use App\Models\FinancialAnomaly;

class SettlementMonitoringService
{
    public function scanForOrphans()
    {
        // 1. Withdrawal sent without confirmation (stuck in PROCESSING for > 1 hour)
        $stuckWithdrawals = WithdrawalRequest::where('status', 'PROCESSING')
            ->where('updated_at', '<', now()->subHour())
            ->get();

        foreach ($stuckWithdrawals as $withdraw) {
            $this->triggerAnomaly('orphan_transaction', "Withdrawal {$withdraw->id} stuck in PROCESSING.", $withdraw->id);
        }

        // 2. PIX received without webhook / Webhook without transaction
        // Often these are logged in a webhook_logs table, but if we don't have it natively,
        // we can check PENDING deposits that are old
        $stuckDeposits = Transaction::where('trx_type', \App\Enums\TrxType::DEPOSIT)
            ->where('status', \App\Enums\TrxStatus::PENDING)
            ->where('created_at', '<', now()->subHours(24))
            ->get();

        foreach ($stuckDeposits as $deposit) {
            $this->triggerAnomaly('settlement_mismatch', "Deposit {$deposit->id} pending for > 24h.", $deposit->id);
        }
        
        // 3. Refund without completion (if we had a refund queue)
    }

    private function triggerAnomaly(string $type, string $description, int $entityId)
    {
        $fingerprint = "{$type}_{$entityId}";
        
        $anomaly = FinancialAnomaly::where('fingerprint', $fingerprint)->whereNull('resolved_at')->first();

        if (!$anomaly) {
            FinancialAnomaly::create([
                'type' => $type,
                'severity' => 'HIGH',
                'entity_type' => 'transaction',
                'entity_id' => $entityId,
                'fingerprint' => $fingerprint,
                'description' => $description,
                'detected_at' => now(),
            ]);
        }
    }
}
