<?php

namespace App\Services\Compliance;

use App\Models\User;
use App\Models\KycProfile;
use App\Models\FraudProfile;
use App\Models\Transaction;
use App\Models\AccountRestriction;
use App\Enums\TrxStatus;
use App\Services\Security\TenantBypass;

class SellerHealthService
{
    public function calculateScore(User $user): int
    {
        $score = 0;

        // 1. KYC
        $kyc = TenantBypass::run(fn () => KycProfile::where('user_id', $user->id)->first());
        if ($kyc && $kyc->level >= 2 && $kyc->status === 'APPROVED') {
            $score += 20;
        } elseif ($kyc && $kyc->level >= 1 && $kyc->status === 'APPROVED') {
            $score += 10;
        }

        // 2. Account Age
        $days = $user->created_at->diffInDays(now());
        if ($days >= 90) {
            $score += 15;
        } elseif ($days >= 30) {
            $score += 10;
        }

        // 3. Consistent Volume
        $monthlyVolume = TenantBypass::run(fn () => Transaction::where('user_id', $user->id)
            ->where('status', TrxStatus::COMPLETED)
            ->where('created_at', '>=', now()->subDays(30))
            ->sum('amount'));
        if ($monthlyVolume > 1000) {
            $score += 20;
        } elseif ($monthlyVolume > 100) {
            $score += 10;
        }

        // 4. No Fraud History
        $fraudProfile = TenantBypass::run(fn () => FraudProfile::where('user_id', $user->id)->first());
        if (!$fraudProfile || $fraudProfile->risk_level === 'LOW') {
            $score += 20;
        }

        // 5. No Chargebacks (Mocking since no Chargeback Model is explicitly linked in this request, though Phase 5.5 handled Gateway fees. Let's assume transaction refund count)
        $chargebacks = TenantBypass::run(fn () => Transaction::where('user_id', $user->id)
            ->where('remark', 'like', '%chargeback%')
            ->count());
        if ($chargebacks === 0) {
            $score += 15;
        }

        // 6. No Blocks/Restrictions
        $restrictions = AccountRestriction::where('user_id', $user->id)->count();
        if ($restrictions === 0) {
            $score += 10;
        }

        return min($score, 100);
    }

    public function classifyScore(int $score): string
    {
        if ($score >= 90) return 'EXCELLENT';
        if ($score >= 70) return 'GOOD';
        if ($score >= 50) return 'WARNING';
        return 'HIGH_RISK';
    }
}
