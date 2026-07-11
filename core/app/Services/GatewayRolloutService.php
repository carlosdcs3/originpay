<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Config;

class GatewayRolloutService
{
    /**
     * Determines if a given user should use the new provider.
     */
    public function shouldUseNewProvider(?User $user): bool
    {
        $enabled = config('services.new_provider.enabled', false);
        if (!$enabled) {
            return false;
        }

        if (!$user) {
            return false;
        }

        // Whitelist Check
        $whitelist = config('services.new_provider.whitelist_users', '');
        $whitelistedIds = array_filter(array_map('trim', explode(',', $whitelist)));
        if (in_array((string)$user->id, $whitelistedIds, true)) {
            return true;
        }

        // Percentage Check
        $percentage = (int) config('services.new_provider.rollout_percentage', 0);
        if ($percentage > 0) {
            // Determine via deterministic hash
            $hash = crc32('user_' . $user->id);
            $bucket = $hash % 100;
            if ($bucket < $percentage) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if the kill switch is active (blocks outbound, allows inbound).
     */
    public function isKillSwitchActive(): bool
    {
        return filter_var(config('services.new_provider.kill_switch', false), FILTER_VALIDATE_BOOLEAN);
    }
}
