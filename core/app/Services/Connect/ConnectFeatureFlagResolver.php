<?php
namespace App\Services\Connect;

class ConnectFeatureFlagResolver
{
    /**
     * Placeholder pipeline step.
     * In the future, it removes capabilities if a tenant-specific feature flag is disabled.
     */
    public function resolve(array $capabilities, $merchantId): array
    {
        return $capabilities;
    }
}
