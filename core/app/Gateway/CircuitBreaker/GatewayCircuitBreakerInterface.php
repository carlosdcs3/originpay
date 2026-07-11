<?php

namespace App\Gateway\CircuitBreaker;

interface GatewayCircuitBreakerInterface
{
    public function isAvailable(string $gatewaySlug): bool;
    public function recordSuccess(string $gatewaySlug): void;
    public function recordFailure(string $gatewaySlug): void;
}
