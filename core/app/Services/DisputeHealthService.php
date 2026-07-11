<?php

namespace App\Services;

use App\Models\Dispute;

class DisputeHealthService
{
    public function calculate(Dispute $dispute): int
    {
        $score = 50; // Base score

        // +20 If merchant replied
        if ($dispute->messages()->where('sender_type', 'merchant')->exists()) {
            $score += 20;
        } else {
            $score -= 20;
        }

        // +15 if any required document is received or validated
        if ($dispute->evidenceItems()->whereIn('status', ['received', 'validated'])->exists()) {
            $score += 15;
        }

        // -30 if NF is requested but missing (example rule)
        $hasMissingNf = $dispute->evidenceItems()
            ->where('type', 'nota_fiscal')
            ->where('status', 'pending')
            ->exists();
            
        if ($hasMissingNf) {
            $score -= 30;
        }

        // Penalty if SLA is expired
        if ($dispute->due_at && $dispute->due_at->isPast()) {
            $score -= 20;
        }

        // Ensure bounds 0 - 100
        return max(0, min(100, $score));
    }

    public function updateHealth(Dispute $dispute): void
    {
        $newScore = $this->calculate($dispute);
        
        if ($dispute->health_score !== $newScore) {
            $dispute->update(['health_score' => $newScore]);
        }
    }
}
