<?php

namespace App\Gateway\Http\Middlewares;

use App\DTOs\Gateway\GatewayResponse;
use App\Exceptions\Gateway\GatewayCommunicationException;
use App\Exceptions\Gateway\GatewayTimeoutException;
use App\Gateway\Policies\Delay\DelayStrategyInterface;
use Closure;

class RetryMiddleware implements GatewayMiddlewareInterface
{
    public function __construct(protected DelayStrategyInterface $delayStrategy) {}

    public function handle(array $requestData, Closure $next): GatewayResponse
    {
        $policy = $requestData['retry_policy'] ?? null;
        
        if (!$policy) {
            return $next($requestData);
        }

        $attempts = 0;
        $maxAttempts = $policy->maxRetries + 1;

        while ($attempts < $maxAttempts) {
            $attempts++;
            $requestData['retry_count'] = $attempts - 1;

            try {
                return $next($requestData);
            } catch (GatewayTimeoutException | GatewayCommunicationException $e) {
                if ($attempts >= $maxAttempts) {
                    throw $e;
                }
                
                $delayMs = $policy->delays[$attempts - 1] ?? 1000;
                $this->delayStrategy->delay($delayMs);
            }
        }
        
        throw new \Exception("Exceeded max retries in middleware");
    }
}
