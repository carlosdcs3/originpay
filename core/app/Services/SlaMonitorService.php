<?php

namespace App\Services;

use App\Models\Charge;
use App\Models\WebhookDelivery;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SlaMonitorService
{
    /**
     * Calcula os principais SLAs operacionais
     */
    public function getSlas(): array
    {
        return Cache::remember('sla_metrics_summary', 300, function () {
            return [
                'api_response_time_ms' => app(ApiMetricsService::class)->getAverageLatency(),
                'webhook_delivery_time_ms' => $this->getAverageWebhookDeliveryTime(),
                'pix_creation_time_ms' => $this->getAveragePixCreationTime(),
                'reconciliation_time_ms' => $this->getAverageReconciliationTime(),
                'system_uptime_percentage' => $this->getEstimatedUptime(),
            ];
        });
    }

    private function getAverageWebhookDeliveryTime(): float
    {
        // Pega o tempo médio de entrega dos webhooks nas últimas 24h (mock de calculo real ou tabela existente)
        $avg = WebhookDelivery::where('created_at', '>=', now()->subHours(24))
            ->where('status', 'success')
            ->avg('delivery_time_ms');
            
        return round((float) ($avg ?? 120), 2); // default 120ms if none exists
    }

    private function getAveragePixCreationTime(): float
    {
        // Se a gente registrar o tempo que demorou a request do gateway no metadata ou em tabela separada
        // Por hora simulando pelo GatewayMetricsService
        $gwMetrics = app(GatewayMetricsService::class);
        $avg = $gwMetrics->getLatencyAvg('efi_latency', '24h');
        
        return $avg > 0 ? $avg : 250.0;
    }
    
    private function getAverageReconciliationTime(): float
    {
        $avg = \App\Models\ReconciliationHistory::where('created_at', '>=', now()->subDays(7))
            ->avg('duration_ms');
            
        return round((float) ($avg ?? 5000), 2);
    }
    
    private function getEstimatedUptime(): float
    {
        // Uptime baseado na ausencia de incidentes criticos longos.
        // Em um cenario real, veriamos a diferenca entre o tempo total do mes e o downtime.
        $downtimeMs = \App\Models\PlatformIncident::where('severity', 'critical')
            ->where('started_at', '>=', now()->subDays(30))
            ->sum('duration_ms');
            
        $totalMs30Days = 30 * 24 * 60 * 60 * 1000;
        
        if ($totalMs30Days == 0) return 100.0;
        
        $uptime = (($totalMs30Days - $downtimeMs) / $totalMs30Days) * 100;
        return round(max(0, min(100, $uptime)), 4);
    }
}
