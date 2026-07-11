<?php

namespace App\Gateway\Listeners;

use App\Gateway\Events\GatewayMetricRecordedEvent;
use Illuminate\Support\Facades\Log;

class LogGatewayMetricListener
{
    public function handle(GatewayMetricRecordedEvent $event): void
    {
        Log::info("Gateway Metric: [{$event->gatewaySlug}] {$event->operation}", [
            'status_code' => $event->statusCode,
            'latency_ms' => $event->latencyMs,
            'success' => $event->success,
            'correlation_id' => $event->correlationId
        ]);
        
        // No futuro: enviar ao Prometheus/Grafana ou salvar no banco
    }
}
