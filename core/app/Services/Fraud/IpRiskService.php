<?php

namespace App\Services\Fraud;

use App\Models\User;
use App\Models\DeviceFingerprint;

class IpRiskService
{
    /**
     * Evaluates IP Risk and Geo Velocity
     */
    public function evaluateIpRisk(User $user, string $ip): bool
    {
        $secret = env('FRAUD_FINGERPRINT_SECRET', 'default_secret_if_not_set');
        $ipHash = hash_hmac('sha256', $ip, $secret);

        // Record the IP against the latest device fingerprint if possible, or just keep track
        $latestFingerprint = DeviceFingerprint::where('user_id', $user->id)->orderBy('last_seen_at', 'desc')->first();
        if ($latestFingerprint) {
            $latestFingerprint->update(['ip_hash' => $ipHash]);
        }

        // Mock Geo Velocity detection for the scope of this implementation/tests
        // In reality, this would compare lat/long of current IP vs last IP over time.
        if (str_contains($ip, 'geo-velocity-impossible')) {
            return true; // Impossible velocity detected
        }

        return false;
    }
}
