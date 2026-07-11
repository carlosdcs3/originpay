<?php

namespace App\Services\Gateways;

use App\Contracts\Gateways\GatewayInterface;
use Exception;

class GatewayRegistry
{
    private array $adapters = [];

    public function register(string $name, GatewayInterface $adapter): void
    {
        $this->adapters[$name] = $adapter;
    }

    public function resolve(string $name): GatewayInterface
    {
        if (!isset($this->adapters[$name])) {
            throw new Exception("Gateway adapter [{$name}] not registered.");
        }

        return $this->adapters[$name];
    }
}
