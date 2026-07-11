<?php

namespace App\Services\Gateway\Providers;

use App\Contracts\GatewayProviderInterface;
use App\DTOs\Gateway\GatewayResponse;
use App\DTOs\Gateway\GatewayHealthData;
use App\DTOs\Gateway\GatewayOperation;
use LogicException;

class AsaasProvider implements GatewayProviderInterface
{
    public function createCharge(array $data): GatewayResponse
    {
        throw new LogicException('createCharge not implemented for this provider.');
    }

    public function cancelCharge(string $reference): GatewayResponse
    {
        throw new LogicException('cancelCharge not implemented for this provider.');
    }

    public function getCharge(string $reference): GatewayResponse
    {
        throw new LogicException('getCharge not implemented for this provider.');
    }

    public function createPix(array $data): GatewayResponse
    {
        throw new LogicException('createPix not implemented for this provider.');
    }

    public function createBoleto(array $data): GatewayResponse
    {
        throw new LogicException('createBoleto not implemented for this provider.');
    }

    public function withdraw(array $data): GatewayResponse
    {
        throw new LogicException('withdraw not implemented for this provider.');
    }

    public function refund(string $reference, float $amount): GatewayResponse
    {
        throw new LogicException('refund not implemented for this provider.');
    }

    public function health(): GatewayHealthData
    {
        throw new LogicException('health not implemented for this provider.');
    }

    public function operations(): array
    {
        throw new LogicException('operations not implemented for this provider.');
    }
}
