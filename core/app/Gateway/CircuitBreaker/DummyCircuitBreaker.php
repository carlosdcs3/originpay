<?php

namespace App\Gateway\CircuitBreaker;

class DummyCircuitBreaker implements GatewayCircuitBreakerInterface
{
    public function isAvailable(string $gatewaySlug): bool
    {
        return true; // Sempre aberto na implementacao dummy inicial
    }

    public function recordSuccess(string $gatewaySlug): void
    {
        // ...
    }

    public function recordFailure(string $gatewaySlug): void
    {
        // ...
    }
}
