<?php

namespace App\Gateway\Http\Middlewares;

use App\DTOs\Gateway\GatewayResponse;
use Closure;

interface GatewayMiddlewareInterface
{
    /**
     * Processa a requisicao e passa para o proximo pipeline
     */
    public function handle(array $requestData, Closure $next): GatewayResponse;
}
