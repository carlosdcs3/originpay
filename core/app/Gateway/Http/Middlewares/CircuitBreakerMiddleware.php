<?php

namespace App\Gateway\Http\Middlewares;

use App\DTOs\Gateway\GatewayResponse;
use App\Gateway\CircuitBreaker\GatewayCircuitBreakerInterface;
use App\Exceptions\Gateway\GatewayCommunicationException;
use Closure;

class CircuitBreakerMiddleware implements GatewayMiddlewareInterface
{
    public function __construct(protected GatewayCircuitBreakerInterface $circuitBreaker) {}

    public function handle(array $requestData, Closure $next): GatewayResponse
    {
        $slug = $requestData['gatewaySlug'] ?? 'unknown';

        if (!$this->circuitBreaker->isAvailable($slug)) {
            throw new GatewayCommunicationException("Circuit Breaker is OPEN for {$slug}. Request aborted.", 503);
        }

        try {
            $response = $next($requestData);
            
            if ($response->success) {
                $this->circuitBreaker->recordSuccess($slug);
            } else {
                $this->circuitBreaker->recordFailure($slug);
            }

            return $response;
        } catch (\Exception $e) {
            $this->circuitBreaker->recordFailure($slug);
            throw $e;
        }
    }
}
