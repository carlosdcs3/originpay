<?php

namespace App\Gateway\Policies;

class GatewayRetryPolicy
{
    public function __construct(
        public readonly int $maxRetries = 3,
        public readonly array $delays = [1000, 2000, 4000] // ms
    ) {}
}
