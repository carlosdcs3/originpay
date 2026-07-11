<?php

namespace App\Gateway\Http\Middlewares;

use App\DTOs\Gateway\GatewayResponse;
use Closure;
use Illuminate\Support\Str;

class CorrelationMiddleware implements GatewayMiddlewareInterface
{
    public function handle(array $requestData, Closure $next): GatewayResponse
    {
        $correlationId = Str::uuid()->toString();
        $requestData['correlation_id'] = $correlationId;
        $requestData['headers']['X-Correlation-ID'] = $correlationId;

        return $next($requestData);
    }
}
