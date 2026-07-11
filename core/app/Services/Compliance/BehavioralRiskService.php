<?php

namespace App\Services\Compliance;

use App\Models\User;
use App\Models\WithdrawalRequest;
use App\Models\Transaction;
use App\Enums\TrxType;
use App\Enums\TrxStatus;
use App\Models\FinancialAnomaly;
use Carbon\Carbon;

class BehavioralRiskService
{
    /**
     * Evaluates behavioral risk focusing heavily on financial context.
     */
    public function evaluate(User $user, float $requestedAmount): string
    {
        $totalDeposited = Transaction::where('user_id', $user->id)
            ->where('trx_type', TrxType::DEPOSIT)
            ->where('status', TrxStatus::COMPLETED)
            ->sum('amount');

        $totalWithdrawn = Transaction::where('user_id', $user->id)
            ->where('trx_type', TrxType::WITHDRAW)
            ->where('status', TrxStatus::COMPLETED)
            ->sum('amount');
            
        $salesCount = Transaction::where('user_id', $user->id)
            ->where('trx_type', TrxType::RECEIVE_PAYMENT) // Assumed sales trx type
            ->where('status', TrxStatus::COMPLETED)
            ->count();

        $accountAgeDays = $user->created_at->diffInDays(now());
        
        $isFirstWithdrawal = WithdrawalRequest::where('user_id', $user->id)->count() === 0;

        // CRITICAL Rule: Account created recently (< 3 days), low/zero sales, 
        // trying to withdraw almost everything deposited or a huge amount.
        if ($accountAgeDays <= 3) {
            if ($requestedAmount >= 5000) {
                $this->registerAnomaly('large_withdraw_after_recent_account_creation', 'CRITICAL', $user->id, $requestedAmount, $totalDeposited);
                return 'CRITICAL';
            }
            if ($totalDeposited > 0 && $requestedAmount > ($totalDeposited * 0.9) && $salesCount < 2) {
                $this->registerAnomaly('suspicious_withdraw_pattern', 'CRITICAL', $user->id, $requestedAmount, $totalDeposited);
                return 'CRITICAL';
            }
        }

        // HIGH Rule: First withdrawal is very large compared to history
        if ($isFirstWithdrawal && $requestedAmount > 1000) {
            $this->registerAnomaly('first_withdraw_high_risk', 'HIGH', $user->id, $requestedAmount, $totalDeposited);
            return 'HIGH';
        }

        // Add typical behavioral checks
        // if user changed password recently, etc. (skipping DB schema checks for brevity, assuming standard features exist or mocked)
        
        // LOW Rule: Normal usage
        return 'LOW';
    }

    private function registerAnomaly(string $type, string $severity, int $userId, float $requestedAmount, float $totalDeposited)
    {
        $fingerprint = "{$type}:{$userId}";
        $anomaly = FinancialAnomaly::where('fingerprint', $fingerprint)->whereNull('resolved_at')->first();

        if (!$anomaly) {
            FinancialAnomaly::create([
                'type' => $type,
                'severity' => $severity,
                'entity_type' => 'user',
                'entity_id' => $userId,
                'fingerprint' => $fingerprint,
                'description' => "Behavioral risk triggered: {$type} for user {$userId}.",
                'metadata' => [
                    'requested_amount' => $requestedAmount,
                    'total_deposited' => $totalDeposited
                ],
                'suggested_actions' => ['manual_review', 'freeze_account_temporarily'],
                'detected_at' => now(),
            ]);
        }
    }
}
