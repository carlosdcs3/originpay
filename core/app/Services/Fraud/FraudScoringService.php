<?php

namespace App\Services\Fraud;

use App\Models\User;
use App\Models\FraudProfile;
use App\Models\FraudEvent;
use App\Models\FinancialAnomaly;
use App\Models\AccountRestriction;

class FraudScoringService
{
    public function evaluateUser(User $user, array $triggers = [])
    {
        $profile = FraudProfile::firstOrCreate(
            ['user_id' => $user->id],
            ['fraud_score' => 0, 'risk_level' => 'LOW']
        );

        $addedScore = 0;

        foreach ($triggers as $trigger => $details) {
            $score = $this->getScoreForTrigger($trigger);
            if ($score > 0) {
                $addedScore += $score;
                FraudEvent::create([
                    'user_id' => $user->id,
                    'type' => $trigger,
                    'severity' => $this->getSeverityForScore($score),
                    'metadata' => $details
                ]);

                // Create specific anomalies if required
                $this->createAnomalyForTrigger($user, $trigger, $details);
            }
        }

        if ($addedScore > 0) {
            $profile->fraud_score += $addedScore;
            $profile->risk_level = $this->determineRiskLevel($profile->fraud_score);
            $profile->last_evaluation_at = now();
            $profile->save();

            $this->applyAutomaticActions($user, $profile->risk_level);
        }

        return $profile;
    }

    private function getScoreForTrigger(string $trigger): int
    {
        return match ($trigger) {
            'duplicated_identity' => 100,
            'duplicated_document' => 100,
            'duplicated_selfie' => 100,
            'shared_pix' => 50,
            'new_acc_high_volume' => 40,
            'shared_device' => 30,
            'geo_velocity_impossible' => 20,
            default => 0,
        };
    }

    private function getSeverityForScore(int $score): string
    {
        if ($score >= 100) return 'CRITICAL';
        if ($score >= 60) return 'HIGH';
        if ($score >= 30) return 'MEDIUM';
        return 'LOW';
    }

    private function determineRiskLevel(int $score): string
    {
        if ($score >= 100) return 'CRITICAL';
        if ($score >= 60) return 'HIGH';
        if ($score >= 30) return 'MEDIUM';
        return 'LOW';
    }

    private function createAnomalyForTrigger(User $user, string $trigger, array $details)
    {
        $fingerprint = "fraud_trigger_{$trigger}_{$user->id}";
        
        if (!FinancialAnomaly::where('fingerprint', $fingerprint)->exists()) {
            FinancialAnomaly::create([
                'type' => $trigger,
                'severity' => 'HIGH',
                'entity_type' => 'user',
                'entity_id' => $user->id,
                'fingerprint' => $fingerprint,
                'description' => "Fraud trigger detected: {$trigger}",
                'detected_at' => now(),
            ]);
        }
    }

    private function applyAutomaticActions(User $user, string $riskLevel)
    {
        if ($riskLevel === 'CRITICAL') {
            AccountRestriction::updateOrCreate(
                ['user_id' => $user->id, 'restriction_type' => 'FULL_FREEZE'],
                ['reason' => 'Critical Fraud Score', 'expires_at' => null]
            );
            
            FinancialAnomaly::create([
                'type' => 'fraud_score_critical',
                'severity' => 'CRITICAL',
                'entity_type' => 'user',
                'entity_id' => $user->id,
                'fingerprint' => "fraud_score_critical_{$user->id}_" . time(),
                'description' => "User reached CRITICAL fraud score. FULL_FREEZE applied.",
                'detected_at' => now(),
            ]);
        } elseif ($riskLevel === 'HIGH') {
            AccountRestriction::updateOrCreate(
                ['user_id' => $user->id, 'restriction_type' => 'WITHDRAW_BLOCK'],
                ['reason' => 'High Fraud Score', 'expires_at' => null]
            );
        }
    }
}
