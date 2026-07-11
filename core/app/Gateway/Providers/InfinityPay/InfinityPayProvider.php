<?php

namespace App\Gateway\Providers\InfinityPay;

use App\Gateway\Contracts\AbstractGatewayProvider;
use App\Gateway\Contracts\Enums\GatewayOperation;
use App\Gateway\Contracts\Data\GatewayResponse;
use App\Gateway\Contracts\Data\GatewayHealthData;

class InfinityPayProvider extends AbstractGatewayProvider
{
    public function getIdentifier(): string
    {
        return 'infinitypay';
    }

    public function sendRequest(GatewayOperation $operation, array $payload): GatewayResponse
    {
        // Stub: Implement InfinityPay API call
        return GatewayResponse::success(
            gatewayReference: 'stub_infinity_' . uniqid(),
            status: 'PENDING',
            amount: $payload['amount'] ?? 0.0,
            rawResponse: ['stub' => true]
        );
    }

    public function checkHealth(): GatewayHealthData
    {
        // Stub: Ping InfinityPay API
        return GatewayHealthData::up(50);
    }
}
