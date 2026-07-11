<?php

namespace App\Services;

use App\Models\ApiLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ApiMetricsService
{
    /**
     * Retorna estatísticas de requisições por minuto no último dia.
     */
    public function getRpmSummary(): array
    {
        return Cache::remember('api_metrics_rpm', 300, function () {
            // Conta requisições nas últimas 24h e divide por minutos (1440)
            $total24h = ApiLog::where('created_at', '>=', now()->subHours(24))->count();
            return [
                'rpm_avg_24h' => round($total24h / 1440, 2),
                'total_24h' => $total24h,
            ];
        });
    }

    /**
     * Retorna a taxa de erros nas últimas 24h.
     */
    public function getErrorRateSummary(): array
    {
        return Cache::remember('api_metrics_error_rate', 300, function () {
            $total = ApiLog::where('created_at', '>=', now()->subHours(24))->count();
            if ($total === 0) {
                return ['error_rate' => 0, 'total_errors' => 0];
            }

            $errors = ApiLog::where('created_at', '>=', now()->subHours(24))
                ->where('status_code', '>=', 400)
                ->count();

            return [
                'error_rate' => round(($errors / $total) * 100, 2),
                'total_errors' => $errors,
            ];
        });
    }

    /**
     * Tempo médio de resposta (Latência)
     */
    public function getAverageLatency(): float
    {
        return Cache::remember('api_metrics_avg_latency', 300, function () {
            $avg = ApiLog::where('created_at', '>=', now()->subHours(24))->avg('response_time_ms');
            return round((float) $avg, 2);
        });
    }

    /**
     * Top Endpoints mais utilizados
     */
    public function getTopEndpoints(int $limit = 5): array
    {
        return Cache::remember('api_metrics_top_endpoints', 300, function () use ($limit) {
            return ApiLog::where('created_at', '>=', now()->subHours(24))
                ->select('endpoint', 'method', DB::raw('count(*) as total'))
                ->groupBy('endpoint', 'method')
                ->orderByDesc('total')
                ->limit($limit)
                ->get()
                ->toArray();
        });
    }
}
