<?php

namespace App\Services\Fraud;

use App\Models\User;
use App\Models\DeviceFingerprint;

/**
 * Dados coletados exclusivamente para prevenção a fraude, segurança da conta e proteção financeira.
 */
class DeviceFingerprintService
{
    /**
     * Records a fingerprint and returns true if this device is shared with other accounts.
     */
    public function recordFingerprint(User $user, array $deviceData): bool
    {
        $userAgent = $deviceData['user_agent'] ?? '';
        $timezone = $deviceData['timezone'] ?? '';
        $language = $deviceData['language'] ?? '';
        $resolution = $deviceData['resolution'] ?? '';
        $platform = $deviceData['platform'] ?? '';
        $frontendHash = $deviceData['frontend_hash'] ?? '';

        $payload = implode('|', [$userAgent, $timezone, $language, $resolution, $platform, $frontendHash]);
        
        $secret = env('FRAUD_FINGERPRINT_SECRET', 'default_secret_if_not_set');
        $fingerprintHash = hash_hmac('sha256', $payload, $secret);
        $userAgentHash = hash_hmac('sha256', $userAgent, $secret);

        $record = DeviceFingerprint::where('user_id', $user->id)
            ->where('fingerprint_hash', $fingerprintHash)
            ->first();

        if ($record) {
            $record->update(['last_seen_at' => now()]);
        } else {
            DeviceFingerprint::create([
                'user_id' => $user->id,
                'fingerprint_hash' => $fingerprintHash,
                'user_agent_hash' => $userAgentHash,
                'reduced_metadata' => ['platform' => $platform, 'timezone' => $timezone],
                'first_seen_at' => now(),
                'last_seen_at' => now()
            ]);
        }

        // Check if this fingerprint is used by other users
        $sharedCount = DeviceFingerprint::where('fingerprint_hash', $fingerprintHash)
            ->where('user_id', '!=', $user->id)
            ->distinct('user_id')
            ->count();

        return $sharedCount > 0;
    }
}
