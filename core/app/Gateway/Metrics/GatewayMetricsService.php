<?php

namespace App\Gateway\Metrics;

use Illuminate\Support\Facades\Log;

class GatewayMetricsService
{
    public function record(
        string $gatewaySlug, 
        string $operation, 
        int $statusCode, 
        int $latencyMs, 
        bool $success
    ): void {
        Log::info("GatewayMetrics", [
            'gateway' => $gatewaySlug,
            'operation' => $operation,
            'status_code' => $statusCode,
            'latency_ms' => $latencyMs,
            'success' => $success
        ]);
        // TODO: Enviar para FinanceMetricsService via Evento/Queue no futuro
    }
}
