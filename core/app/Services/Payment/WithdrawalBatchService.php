<?php

namespace App\Services\Payment;

use App\Models\WithdrawalRequest;
use App\Models\FinancialAnomaly;

class WithdrawalBatchService
{
    public function processBatches()
    {
        // Get all pending batches
        $withdrawals = WithdrawalRequest::where('status', 'PENDING_BATCH')
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($withdrawals as $withdrawal) {
            try {
                // If it was already pushed, status would be different.
                // Just in case double check.
                if ($withdrawal->status === 'PENDING_BATCH') {
                    $withdrawal->status = 'APPROVED'; // Return to approved to satisfy process logic
                    $withdrawal->save();
                    dispatch(new \App\Jobs\ProcessWithdrawalJob($withdrawal->id))->onQueue('high');
                }
            } catch (\Exception $e) {
                FinancialAnomaly::create([
                    'type' => 'withdrawal_batch_stuck',
                    'severity' => 'HIGH',
                    'entity_type' => 'withdrawal',
                    'entity_id' => $withdrawal->id,
                    'fingerprint' => "withdrawal_batch_stuck_{$withdrawal->id}",
                    'description' => "Failed to dispatch batched withdrawal: " . $e->getMessage(),
                    'detected_at' => now(),
                ]);
            }
        }
    }
}
