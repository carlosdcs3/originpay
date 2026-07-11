<?php

namespace App\DTOs\Gateway;

class GatewayHealthData
{
    public function __construct(
        public readonly bool $is_healthy,
        public readonly int $latency_ms,
        public readonly string $status_message,
        
        // Novos campos
        public readonly ?bool $authenticated = null,
        public readonly ?bool $certificate_valid = null,
        public readonly ?string $certificate_expires_at = null,
        public readonly ?string $oauth_token_expires_at = null,
        public readonly ?int $rate_limit_remaining = null,
        public readonly ?string $last_success = null,
        public readonly ?string $last_failure = null,
        public readonly ?string $api_version = null,
        public readonly ?string $environment = null,
        public readonly ?bool $dns_ok = null,
        public readonly ?bool $ssl_ok = null
    ) {}
}
