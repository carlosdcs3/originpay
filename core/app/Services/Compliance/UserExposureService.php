<?php

namespace App\Services\Compliance;

use App\Models\User;
use App\Models\Wallet;
use App\Models\WithdrawalRequest;
use App\Models\Transaction;
use App\Models\FinancialAnomaly;
use App\Models\KycProfile;
use App\Models\UserFinancialLimit;
use App\Enums\TrxType;
use App\Services\Security\TenantBypass;

class UserExposureService
{
    protected AccountRestrictionService $restrictionService;

    public function __construct(AccountRestrictionService $restrictionService)
    {
        $this->restrictionService = $restrictionService;
    }

    public function getLimitsForLevel(int $level): array
    {
        switch ($level) {
            case 0:
                return ['max_balance' => 500, 'max_daily_withdraw' => 500, 'max_monthly_volume' => 1000];
            case 1:
                return ['max_balance' => 2000, 'max_daily_withdraw' => 2000, 'max_monthly_volume' => 5000];
            case 2:
                return ['max_balance' => 10000, 'max_daily_withdraw' => 10000, 'max_monthly_volume' => 25000];
            default:
                return ['max_balance' => null, 'max_daily_withdraw' => null, 'max_monthly_volume' => null];
        }
    }

    public function evaluateExposure(User $user)
    {
        // Get custom limits or level limits
        $customLimits = TenantBypass::run(fn () => UserFinancialLimit::where('user_id', $user->id)->first());
        $kyc = TenantBypass::run(fn () => KycProfile::firstOrCreate(['user_id' => $user->id], ['level' => 0, 'status' => 'PENDING']));
        
        $limits = $this->getLimitsForLevel($kyc->level);
        
        $maxBalance = $customLimits->max_balance ?? $limits['max_balance'];
        $maxDailyWithdraw = $customLimits->max_daily_withdraw ?? $limits['max_daily_withdraw'];
        $maxMonthlyVolume = $customLimits->max_monthly_volume ?? $limits['max_monthly_volume'];

        // 1. Evaluate Balance
        $wallet = TenantBypass::run(fn () => Wallet::where('user_id', $user->id)->first());
        if ($wallet && $maxBalance !== null && $wallet->balance > $maxBalance) {
            $this->lockUserAndTriggerAnomaly($user, 'max_balance_exceeded', "Balance {$wallet->balance} exceeds max {$maxBalance}");
            return; // Already locked
        }

        // 2. Evaluate Daily Withdraw
        if ($maxDailyWithdraw !== null) {
            $dailyWithdrawSum = TenantBypass::run(fn () => WithdrawalRequest::where('user_id', $user->id)
                ->where('created_at', '>=', now()->startOfDay())
                ->whereNotIn('status', ['REJECTED', 'FAILED'])
                ->sum('amount'));
                
            if ($dailyWithdrawSum > $maxDailyWithdraw) {
                $this->lockUserAndTriggerAnomaly($user, 'max_daily_withdraw_exceeded', "Daily withdraw sum {$dailyWithdrawSum} exceeds max {$maxDailyWithdraw}");
                return;
            }
        }

        // 3. Evaluate Monthly Volume
        if ($maxMonthlyVolume !== null) {
            $monthlyVolume = TenantBypass::run(fn () => Transaction::where('user_id', $user->id)
                ->where('created_at', '>=', now()->startOfMonth())
                ->where('status', \App\Enums\TrxStatus::COMPLETED)
                ->sum('amount')); // rough calculation of all flow
                
            if ($monthlyVolume > $maxMonthlyVolume) {
                $this->lockUserAndTriggerAnomaly($user, 'max_monthly_volume_exceeded', "Monthly volume {$monthlyVolume} exceeds max {$maxMonthlyVolume}");
            }
        }
    }

    private function lockUserAndTriggerAnomaly(User $user, string $reasonType, string $description)
    {
        // Check if already locked to avoid spamming anomalies
        if ($this->restrictionService->hasRestriction($user, 'KYC_LIMIT_LOCK')) {
            return;
        }

        // Lock Outflow but allow Inflow
        \App\Models\AccountRestriction::create([
            'user_id' => $user->id,
            'restriction_type' => 'KYC_LIMIT_LOCK',
            'reason' => 'Exceeded KYC Financial Limits: ' . $reasonType,
        ]);

        $fingerprint = "kyc_required_limit_exceeded_{$user->id}_" . now()->format('Y-m');

        if (!FinancialAnomaly::where('fingerprint', $fingerprint)->exists()) {
            FinancialAnomaly::create([
                'type' => 'kyc_required_limit_exceeded',
                'severity' => 'HIGH',
                'entity_type' => 'user',
                'entity_id' => $user->id,
                'fingerprint' => $fingerprint,
                'description' => "User exceeded financial limits and requires KYC upgrade. Details: {$description}",
                'detected_at' => now(),
            ]);
        }
    }
}
