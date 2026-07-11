<?php

namespace App\Gateway\Contracts;

use App\Gateway\Contracts\Enums\GatewayOperation;
use App\Gateway\Contracts\Data\GatewayResponse;
use App\Gateway\Contracts\Data\GatewayHealthData;

interface GatewayProviderInterface
{
    /**
     * Define the unique identifier of the provider.
     */
    public function getIdentifier(): string;

    /**
     * Execute a specific operation in the Gateway.
     */
    public function sendRequest(GatewayOperation $operation, array $payload): GatewayResponse;

    /**
     * Ping the Gateway to check availability and latency.
     */
    public function checkHealth(): GatewayHealthData;
}
