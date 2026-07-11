<?php

namespace App\Gateway\Providers\Cora;

use App\Gateway\Contracts\AbstractGatewayProvider;
use App\Gateway\Contracts\Enums\GatewayOperation;
use App\Gateway\Contracts\Data\GatewayResponse;
use App\Gateway\Contracts\Data\GatewayHealthData;

class CoraProvider extends AbstractGatewayProvider
{
    public function getIdentifier(): string
    {
        return 'cora';
    }

    public function sendRequest(GatewayOperation $operation, array $payload): GatewayResponse
    {
        // Stub: Implement Cora API call
        return GatewayResponse::success(
            gatewayReference: 'stub_cora_' . uniqid(),
            status: 'PENDING',
            amount: $payload['amount'] ?? 0.0,
            rawResponse: ['stub' => true]
        );
    }

    public function checkHealth(): GatewayHealthData
    {
        // Stub: Ping Cora API
        return GatewayHealthData::up(35);
    }
}
