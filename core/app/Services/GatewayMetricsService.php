<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class GatewayMetricsService
{
    /**
     * Incrementa contadores atômicos em janelas de 5m, 15m e 24h
     */
    public function increment(string $metricName, int $value = 1): void
    {
        $windows = [
            '5m' => now()->addMinutes(5),
            '15m' => now()->addMinutes(15),
            '24h' => now()->addHours(24),
        ];

        foreach ($windows as $suffix => $ttl) {
            $key = "metrics:{$metricName}:{$suffix}";
            
            // Usar o Redis atomicamente se suportado pelo driver.
            // Para drivers de arquivo/database, increment() pode ter race conditions, mas atende aos requisitos do projeto (que exigiu não criar tabelas MySQL e usar Redis/Cache).
            if (Cache::has($key)) {
                Cache::increment($key, $value);
            } else {
                Cache::put($key, $value, $ttl);
            }
        }
    }

    /**
     * Registra latência com média simples por janela
     */
    public function recordLatency(string $metricName, float $latencyMs): void
    {
        $windows = [
            '5m' => now()->addMinutes(5),
            '15m' => now()->addMinutes(15),
            '24h' => now()->addHours(24),
        ];

        foreach ($windows as $suffix => $ttl) {
            $sumKey = "metrics:{$metricName}_sum:{$suffix}";
            $countKey = "metrics:{$metricName}_count:{$suffix}";

            if (!Cache::has($sumKey)) {
                Cache::put($sumKey, 0, $ttl);
                Cache::put($countKey, 0, $ttl);
            }

            // Using lock to prevent race condition in sum/count if possible,
            // but for simple metrics, ignoring slight drifts is acceptable.
            Cache::increment($sumKey, (int) $latencyMs);
            Cache::increment($countKey, 1);
        }
    }

    public function getLatencyAvg(string $metricName, string $window): float
    {
        $sum = Cache::get("metrics:{$metricName}_sum:{$window}", 0);
        $count = Cache::get("metrics:{$metricName}_count:{$window}", 0);
        
        if ($count == 0) return 0;
        
        return round($sum / $count, 2);
    }

    public function getMetric(string $metricName, string $window): int
    {
        return Cache::get("metrics:{$metricName}:{$window}", 0);
    }

    /**
     * Compatibilidade com chamadas antigas
     */
    public function logMetric(string $metricName, array $context = []): void
    {
        if (isset($context['latency_ms'])) {
            $this->recordLatency($metricName, $context['latency_ms']);
        } else {
            $this->increment($metricName);
        }
        
        Log::channel('gateway')->info("METRIC: {$metricName}", $context);
    }

    /**
     * Monitora eventos vitais e dispara Alertas Multicanais se necessário
     */
    public function alertIncident(string $incidentType, string $message, array $context = []): void
    {
        Log::channel('gateway')->emergency("INCIDENT [{$incidentType}]: {$message}", $context);
        
        try {
            app(\App\Services\PlatformAlertService::class)->critical(
                \App\Models\PlatformAlert::CATEGORY_GATEWAY,
                $incidentType,
                [
                    'description' => $message . ' | Context: ' . json_encode($context),
                    'source' => 'GatewayMetricsService'
                ]
            );
        } catch (\Exception $e) {
            // Failsafe
        }
    }

    public function getGatewaySummary(string $gatewayCode): array
    {
        return [
            'success_5m' => $this->getMetric("{$gatewayCode}_success", '5m'),
            'error_5m' => $this->getMetric("{$gatewayCode}_error", '5m'),
            'latency_avg_5m' => $this->getLatencyAvg("{$gatewayCode}_latency", '5m'),
            'success_24h' => $this->getMetric("{$gatewayCode}_success", '24h'),
            'error_24h' => $this->getMetric("{$gatewayCode}_error", '24h'),
            'volume_24h' => $this->getMetric("{$gatewayCode}_volume", '24h'),
        ];
    }
}
