<?php

namespace App\Services\Connect;

class ConnectInputSecurity
{
    /** @param array<string, int> $quotas */
    public function __construct(private readonly array $quotas = []) {}

    public function isSafeProviderUrl(string $url): bool
    {
        $parts = parse_url($url);
        if (! is_array($parts) || ($parts['scheme'] ?? null) !== 'https' || empty($parts['host'])) {
            return false;
        }

        $host = strtolower(rtrim((string) $parts['host'], '.'));
        if ($host === 'localhost' || str_ends_with($host, '.localhost')) {
            return false;
        }

        $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : gethostbynamel($host);
        if ($ips === false || $ips === []) {
            return false;
        }

        foreach ($ips as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                return false;
            }
        }

        return true;
    }

    public function withinQuota(string $resource, int $current, int $requested = 1): bool
    {
        $limit = $this->quotas[$resource] ?? null;

        return is_int($limit) && $limit > 0 && $current >= 0 && $requested > 0 && ($current + $requested) <= $limit;
    }
}
