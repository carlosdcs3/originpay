<?php

namespace App\Gateway;

class GatewayHealth
{
    public bool $isOnline;
    public ?int $latencyMs;
    public string $environment;
    public ?string $lastSync;

    public function __construct(bool $isOnline, ?int $latencyMs, string $environment, ?string $lastSync = null)
    {
        $this->isOnline = $isOnline;
        $this->latencyMs = $latencyMs;
        $this->environment = $environment;
        $this->lastSync = $lastSync ?? now()->toIso8601String();
    }
}
