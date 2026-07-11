<?php

namespace App\Gateway\Http\Middlewares;

use App\DTOs\Gateway\GatewayResponse;
use App\Gateway\Http\Transports\GatewayTransportInterface;
use App\Gateway\Http\Response\GatewayResponseMapperInterface;
use Closure;

class TransportMiddleware implements GatewayMiddlewareInterface
{
    public function __construct(
        protected GatewayTransportInterface $transport,
        protected GatewayResponseMapperInterface $responseMapper
    ) {}

    public function handle(array $requestData, Closure $next): GatewayResponse
    {
        $result = $this->transport->request(
            $requestData['method'],
            $requestData['url'],
            $requestData['headers'] ?? [],
            $requestData['body'] ?? [],
            $requestData['timeout'] ?? 30,
            $requestData['options'] ?? []
        );

        return $this->responseMapper->map($result, $requestData);
    }
}
