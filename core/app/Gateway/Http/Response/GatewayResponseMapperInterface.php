<?php

namespace App\Gateway\Http\Response;

use App\DTOs\Gateway\GatewayResponse;

interface GatewayResponseMapperInterface
{
    /**
     * Mapeia os dados brutos de transporte para o DTO GatewayResponse
     */
    public function map(array $transportResult, array $requestData): GatewayResponse;
}
