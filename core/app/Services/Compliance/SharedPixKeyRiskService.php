<?php

namespace App\Services\Compliance;

use App\Models\PixKey;
use App\Models\FinancialAnomaly;
use App\Services\Security\TenantBypass;

class SharedPixKeyRiskService
{
    public function detectSharedKey(string $pixKey): bool
    {
        $usersUsingKey = TenantBypass::run(
            fn () => PixKey::where('pix_key', $pixKey)->distinct()->count('user_id')
        );

        if ($usersUsingKey > 1) {
            $fingerprint = "shared_pix_key_detected_{$pixKey}";
            if (!FinancialAnomaly::where('fingerprint', $fingerprint)->exists()) {
                FinancialAnomaly::create([
                    'type' => 'shared_pix_key_detected',
                    'severity' => 'HIGH',
                    'entity_type' => 'pix_key',
                    'entity_id' => null,
                    'fingerprint' => $fingerprint,
                    'description' => "PIX Key {$pixKey} is being used by {$usersUsingKey} different users.",
                    'detected_at' => now(),
                ]);
            }
            return true;
        }

        return false;
    }
}
