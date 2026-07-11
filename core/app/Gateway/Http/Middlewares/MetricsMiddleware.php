<?php

namespace App\Gateway\Http\Middlewares;

use App\DTOs\Gateway\GatewayResponse;
use App\Gateway\Events\GatewayMetricRecordedEvent;
use App\Gateway\Events\GatewayEventDispatcherInterface;
use Closure;
use Exception;

class MetricsMiddleware implements GatewayMiddlewareInterface
{
    public function __construct(protected GatewayEventDispatcherInterface $dispatcher) {}

    public function handle(array $requestData, Closure $next): GatewayResponse
    {
        $start = microtime(true);
        $success = false;
        $statusCode = 0;

        try {
            $response = $next($requestData);
            $success = $response->success;
            $statusCode = $response->status_code;

            return new GatewayResponse(
                success: $response->success,
                transaction_id: $response->transaction_id,
                status: $response->status,
                error_message: $response->error_message,
                payload: $response->payload,
                request_id: $response->request_id,
                correlation_id: $response->correlation_id,
                provider_reference: $response->provider_reference,
                status_code: $response->status_code,
                headers: $response->headers,
                raw_body: $response->raw_body,
                latency: (int) ((microtime(true) - $start) * 1000),
                retry_count: $response->retry_count
            );
        } catch (Exception $e) {
            $statusCode = method_exists($e, 'getCode') ? $e->getCode() : 0;
            throw $e;
        } finally {
            $latencyMs = (int) ((microtime(true) - $start) * 1000);
            
            $this->dispatcher->dispatch(new GatewayMetricRecordedEvent(
                gatewaySlug: $requestData['gatewaySlug'] ?? 'unknown',
                operation: $requestData['method'] . ' ' . parse_url($requestData['url'] ?? '', PHP_URL_PATH),
                statusCode: $statusCode,
                latencyMs: $latencyMs,
                success: $success,
                correlationId: $requestData['correlation_id'] ?? 'none'
            ));
        }
    }
}
