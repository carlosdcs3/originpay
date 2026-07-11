<?php

namespace App\Gateway\Pipeline;

use App\Gateway\Http\GatewayHttpClient;
use App\Gateway\Http\Middlewares\CorrelationMiddleware;
use App\Gateway\Http\Middlewares\CircuitBreakerMiddleware;
use App\Gateway\Http\Middlewares\AuthMiddleware;
use App\Gateway\Http\Middlewares\RetryMiddleware;
use App\Gateway\Http\Middlewares\TransportMiddleware;
use App\Gateway\Http\Middlewares\MetricsMiddleware;
use App\Gateway\CircuitBreaker\GatewayCircuitBreakerInterface;
use App\Gateway\Security\GatewayAuthenticationService;
use App\Gateway\Policies\Delay\DelayStrategyInterface;
use App\Gateway\Http\Transports\GatewayTransportInterface;
use App\Gateway\Http\Response\GatewayResponseMapperInterface;
use App\Gateway\Events\GatewayEventDispatcherInterface;

class GatewayPipelineFactory
{
    public function __construct(
        protected GatewayCircuitBreakerInterface $circuitBreaker,
        protected GatewayAuthenticationService $authService,
        protected DelayStrategyInterface $delayStrategy,
        protected GatewayTransportInterface $transport,
        protected GatewayResponseMapperInterface $responseMapper,
        protected GatewayEventDispatcherInterface $dispatcher
    ) {}

    public function createClient(): GatewayHttpClient
    {
        // Constrói uma nova pipeline descartável, imutável.
        // A ordem de execução (da requisição, descendo pro transporte) é top-down na array de registro abaixo
        $middlewares = [
            new CorrelationMiddleware(),
            new CircuitBreakerMiddleware($this->circuitBreaker),
            new AuthMiddleware($this->authService),
            new RetryMiddleware($this->delayStrategy),
            new TransportMiddleware($this->transport, $this->responseMapper),
            new MetricsMiddleware($this->dispatcher)
        ];

        return new GatewayHttpClient($middlewares);
    }
}
