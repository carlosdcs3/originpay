<?php

namespace App\Gateway\Contracts\Data;

class GatewayHealthData
{
    public function __construct(
        public readonly string $status,
        public readonly int $latency_ms,
        public readonly bool $authenticated = false,
        public readonly bool $certificate_valid = false,
        public readonly ?string $certificate_expires_at = null,
        public readonly ?int $rate_limit_remaining = null,
        public readonly ?string $token_expires_at = null,
        public readonly ?string $last_success = null,
        public readonly ?string $last_failure = null,
        public readonly ?string $version = null,
        public readonly ?string $environment = null
    ) {}

    public static function up(int $latencyMs, array $extra = []): self
    {
        return new self(
            status: 'UP',
            latency_ms: $latencyMs,
            authenticated: $extra['authenticated'] ?? true,
            certificate_valid: $extra['certificate_valid'] ?? true,
            certificate_expires_at: $extra['certificate_expires_at'] ?? null,
            rate_limit_remaining: $extra['rate_limit_remaining'] ?? null,
            token_expires_at: $extra['token_expires_at'] ?? null,
            last_success: now()->toIso8601String(),
            version: $extra['version'] ?? null,
            environment: $extra['environment'] ?? 'production'
        );
    }

    public static function down(int $latencyMs, array $extra = []): self
    {
        return new self(
            status: 'DOWN',
            latency_ms: $latencyMs,
            authenticated: $extra['authenticated'] ?? false,
            certificate_valid: $extra['certificate_valid'] ?? false,
            last_failure: now()->toIso8601String(),
            environment: $extra['environment'] ?? 'production'
        );
    }
}
