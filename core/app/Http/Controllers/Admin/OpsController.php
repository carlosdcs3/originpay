<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use App\Services\PlatformAlertService;
use App\Services\ApiMetricsService;
use App\Services\GatewayMetricsService;
use App\Services\QueueMonitorService;
use App\Services\SchedulerMonitorService;
use App\Services\DlqMonitorService;
use App\Services\PlatformIntelligenceService;
use App\Models\PaymentGateway;

class OpsController extends Controller
{
    /**
     * Endpoint consolidado para a tela inicial do Platform Operations
     */
    public function dashboard(PlatformIntelligenceService $intelligenceService): JsonResponse
    {
        return response()->json([
            'insights' => $intelligenceService->getInsights(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Retorna os alertas ativos do sistema
     */
    public function getAlerts(PlatformAlertService $alertService): JsonResponse
    {
        return response()->json([
            'summary' => $alertService->getActiveAlertsSummary(),
            'alerts' => $alertService->getActiveAlerts(20),
        ]);
    }

    /**
     * Resolve um alerta
     */
    public function resolveAlert(int $id, PlatformAlertService $alertService): JsonResponse
    {
        $resolved = $alertService->resolve($id);
        
        if ($resolved) {
            return response()->json(['success' => true, 'message' => 'Alerta resolvido.']);
        }
        
        return response()->json(['success' => false, 'message' => 'Alerta não encontrado ou já resolvido.'], 404);
    }

    /**
     * Retorna métricas de saúde da API
     */
    public function getApiMetrics(ApiMetricsService $apiMetricsService): JsonResponse
    {
        return response()->json([
            'rpm' => $apiMetricsService->getRpmSummary(),
            'error_rate' => $apiMetricsService->getErrorRateSummary(),
            'latency_avg' => $apiMetricsService->getAverageLatency(),
            'top_endpoints' => $apiMetricsService->getTopEndpoints(),
        ]);
    }

    /**
     * Retorna status e métricas dos Gateways Ativos
     */
    public function getGatewayMetrics(GatewayMetricsService $gatewayMetricsService): JsonResponse
    {
        $activeGateways = PaymentGateway::where('status', 1)->get();
        $metrics = [];
        
        foreach ($activeGateways as $gw) {
            $metrics[$gw->code] = [
                'name' => $gw->name,
                'metrics' => $gatewayMetricsService->getGatewaySummary($gw->code)
            ];
        }

        return response()->json(['gateways' => $metrics]);
    }

    /**
     * Retorna a saúde das filas e DLQ
     */
    public function getQueueMetrics(QueueMonitorService $queueMonitor, DlqMonitorService $dlqMonitor): JsonResponse
    {
        return response()->json([
            'queues' => $queueMonitor->getQueueSummary(),
            'latest_failed_jobs' => $queueMonitor->getLatestFailedJobs(5),
            'dlq_status' => $dlqMonitor->getStatusSummary(),
            'dlq_gateways' => $dlqMonitor->getGatewaySummary(),
            'dlq_avg_age_hours' => $dlqMonitor->getAverageAgeInHours(),
        ]);
    }
    
    /**
     * Retorna os logs e status do Scheduler (Tarefas Cron)
     */
    public function getSchedulerMetrics(SchedulerMonitorService $schedulerMonitor): JsonResponse
    {
        return response()->json([
            'summary' => $schedulerMonitor->getSummary(),
            'latest_failures' => $schedulerMonitor->getLatestFailures(5),
        ]);
    }
    
    // --- Sprint 3.1: Operations Enterprise Expansion ---
    
    public function getIncidents(\App\Services\IncidentManagerService $incidentManager): JsonResponse
    {
        return response()->json([
            'active_incidents' => $incidentManager->getActiveIncidents()
        ]);
    }
    
    public function getMaintenanceWindows(): JsonResponse
    {
        $active = \App\Models\MaintenanceWindow::where('status', 'in_progress')->get();
        $scheduled = \App\Models\MaintenanceWindow::where('status', 'scheduled')->get();
        
        return response()->json([
            'active' => $active,
            'scheduled' => $scheduled
        ]);
    }
    
    public function getSlaMetrics(\App\Services\SlaMonitorService $slaMonitor): JsonResponse
    {
        return response()->json([
            'slas' => $slaMonitor->getSlas()
        ]);
    }
    
    public function getPlatformCosts(\App\Services\PlatformCostService $costService): JsonResponse
    {
        return response()->json([
            'estimated_costs' => $costService->getEstimatedCosts30Days()
        ]);
    }
    
    public function getFeatureUsage(\App\Services\FeatureUsageService $featureUsage): JsonResponse
    {
        return response()->json([
            'usage_summary' => $featureUsage->getSummary()
        ]);
    }

    public function getCircuitBreakerStates(\App\Services\CircuitBreakerService $circuitBreaker, \App\Services\GatewayHealthScoreService $healthScore): JsonResponse
    {
        $gateways = \App\Models\PaymentGateway::pluck('code')->toArray();
        $circuits = $circuitBreaker->getAllStates($gateways);
        
        $redis = \Illuminate\Support\Facades\Redis::connection();

        foreach ($gateways as $code) {
            $circuits[$code]['health_score'] = $healthScore->getScore($code);
            // If we used a standard key we could count concurrency, but Laravel's limiter uses Lua scripts. 
            // We'll just return the configured limit for now.
            $circuits[$code]['concurrency_limit'] = 30;
        }

        return response()->json([
            'circuits' => $circuits
        ]);
    }
}
