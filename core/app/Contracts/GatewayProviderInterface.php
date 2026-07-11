<?php

namespace App\Contracts;

use App\DTOs\Gateway\GatewayResponse;
use App\DTOs\Gateway\GatewayHealthData;

interface GatewayProviderInterface
{
    public function createCharge(array $data): GatewayResponse;
    public function cancelCharge(string $reference): GatewayResponse;
    public function getCharge(string $reference): GatewayResponse;
    public function createPix(array $data): GatewayResponse;
    public function createBoleto(array $data): GatewayResponse;
    public function withdraw(array $data): GatewayResponse;
    public function refund(string $reference, float $amount): GatewayResponse;
    
    public function health(): GatewayHealthData;
    
    /**
     * @return \App\DTOs\Gateway\GatewayOperation[]
     */
    public function operations(): array;
}
