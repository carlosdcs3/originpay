<?php

namespace App\Services\Compliance;

use App\Models\User;
use App\Models\WithdrawalRequest;
use Carbon\Carbon;

class VelocityRiskService
{
    /**
     * Checks withdrawal velocity.
     */
    public function evaluate(User $user, float $requestedAmount): string
    {
        $withdrawalsPastHour = WithdrawalRequest::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($withdrawalsPastHour >= 5) {
            return 'HIGH';
        }

        $withdrawalsPastDay = WithdrawalRequest::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDay())
            ->count();

        if ($withdrawalsPastDay >= 20) {
            return 'CRITICAL';
        }

        // Volume Spikes (> 500% above average)
        $averageDailyVolume = WithdrawalRequest::where('user_id', $user->id)
            ->where('status', 'COMPLETED')
            ->where('created_at', '>=', now()->subDays(30))
            ->sum('amount') / 30;
            
        if ($averageDailyVolume > 100 && $requestedAmount > ($averageDailyVolume * 5)) {
            return 'CRITICAL';
        }

        return 'LOW';
    }
}
