<?php

namespace App\Gateway\Providers\Asaas;

use App\Gateway\Contracts\AbstractGatewayProvider;
use App\Gateway\Contracts\Enums\GatewayOperation;
use App\Gateway\Contracts\Data\GatewayResponse;
use App\Gateway\Contracts\Data\GatewayHealthData;

class AsaasProvider extends AbstractGatewayProvider
{
    public function getIdentifier(): string
    {
        return 'asaas';
    }

    public function sendRequest(GatewayOperation $operation, array $payload): GatewayResponse
    {
        // Stub: Implement Asaas API call
        return GatewayResponse::success(
            gatewayReference: 'stub_asaas_' . uniqid(),
            status: 'PENDING',
            amount: $payload['amount'] ?? 0.0,
            rawResponse: ['stub' => true]
        );
    }

    public function checkHealth(): GatewayHealthData
    {
        // Stub: Ping Asaas API
        return GatewayHealthData::up(60);
    }
}
