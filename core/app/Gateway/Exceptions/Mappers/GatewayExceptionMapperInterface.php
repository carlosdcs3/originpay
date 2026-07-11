<?php

namespace App\Gateway\Exceptions\Mappers;

use App\DTOs\Gateway\GatewayResponse;
use App\Exceptions\Gateway\GatewayException;

interface GatewayExceptionMapperInterface
{
    /**
     * Traduz uma resposta HTTP falha para uma Excecao estruturada.
     * Deve retornar nulo se a resposta nao representar um erro, ou lancar a respectiva GatewayException.
     */
    public function throwMappedException(GatewayResponse $response): void;
}
