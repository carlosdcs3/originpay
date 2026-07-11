<?php

namespace App\Services;

use App\Models\FinancialAnomaly;
use Illuminate\Support\Facades\Queue;
use App\Models\WebhookDlq;
use Illuminate\Support\Facades\Cache;

class FinancialHealthScoreService
{
    /**
     * Calcula o Financial Health Score entre 0 e 100
     */
    public function calculateScore(): array
    {
        $score = 100;
        $factors = [];

        // 1. Critical Anomalies (-40 points each)
        $criticalCount = FinancialAnomaly::whereNull('resolved_at')->where('severity', 'CRITICAL')->count();
        if ($criticalCount > 0) {
            $penalty = min($criticalCount * 40, 60); // Cap penalty so it doesn't drain too much alone, wait... rules say no negative.
            $score -= $penalty;
            $factors[] = "Critical Anomalies (-{$penalty})";
        }

        // 2. High Anomalies (-15 points each)
        $highCount = FinancialAnomaly::whereNull('resolved_at')->where('severity', 'HIGH')->count();
        if ($highCount > 0) {
            $penalty = min($highCount * 15, 30);
            $score -= $penalty;
            $factors[] = "High Anomalies (-{$penalty})";
        }

        // 3. Medium/Low Anomalies (-5 points each)
        $medLowCount = FinancialAnomaly::whereNull('resolved_at')->whereIn('severity', ['MEDIUM', 'LOW'])->count();
        if ($medLowCount > 0) {
            $penalty = min($medLowCount * 5, 20);
            $score -= $penalty;
            $factors[] = "Medium/Low Anomalies (-{$penalty})";
        }

        // 4. Circuit Breakers Offline (-20 points)
        // Assume checking generic circuit breaker cache prefix or specific ones
        $cbOffline = Cache::get('kill_switch:withdraw') || Cache::get('circuit_breaker_offline_NEW_PROVIDER');
        if ($cbOffline) {
            $score -= 20;
            $factors[] = "Circuit Breaker/Kill Switch Active (-20)";
        }

        // 5. DLQ Backlog (-10 points if > 50)
        $dlqPending = WebhookDlq::whereNull('resolved_at')->count();
        if ($dlqPending > 50) {
            $score -= 10;
            $factors[] = "DLQ Backlog > 50 (-10)";
        }

        // 6. Horizon Offline (-30 points)
        $horizonOffline = false;
        if (class_exists(\Laravel\Horizon\Contracts\MasterSupervisorRepository::class)) {
            try {
                $masters = app(\Laravel\Horizon\Contracts\MasterSupervisorRepository::class)->all();
                if (!$masters) {
                    $horizonOffline = true;
                }
            } catch (\Exception $e) {
                $horizonOffline = true;
            }
        }
        if ($horizonOffline) {
            $score -= 30;
            $factors[] = "Horizon Offline (-30)";
        }

        // Ensure bounds
        $score = max(0, min(100, $score));

        // Determine Category
        $status = 'Critical';
        if ($score >= 90) $status = 'Healthy';
        elseif ($score >= 70) $status = 'Warning';
        elseif ($score >= 40) $status = 'Risk';

        return [
            'score' => $score,
            'status' => $status,
            'factors' => $factors,
            'critical_anomalies' => $criticalCount,
            'high_anomalies' => $highCount,
        ];
    }
}
