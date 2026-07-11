<?php

namespace App\Services\Finance;

use App\Data\Finance\FinanceDashboardData;
use App\Data\Finance\KpiCollection;
use App\Data\Finance\ChartCollection;
use App\Data\Finance\AlertCollection;
use App\Data\Finance\HealthCollection;
use App\Data\Finance\GatewayCollection;
use App\Data\Finance\WalletCollection;

class FinanceDashboardService
{
    public function __construct(
        private FinanceMetricsService $metricsService,
        private FinanceHealthService $healthService,
        private FinanceAlertService $alertService
    ) {}

    public function getDashboardData(): FinanceDashboardData
    {
        $startTime = microtime(true);
        
        $aggregated = $this->metricsService->getAggregatedMetrics();
        $realtime = $this->metricsService->getRealtimeMetrics();
        $healthReport = $this->healthService->generateReport();
        $alerts = $this->alertService->getActiveAlerts();

        $kpis = new KpiCollection([
            'tpv_daily' => $aggregated['tpv_daily'] ?? 0,
            'tpv_monthly' => $aggregated['tpv_monthly'] ?? 0,
            'withdrawals' => $aggregated['withdrawals_count'] ?? 0,
            'failures' => $aggregated['failures_count'] ?? 0
        ]);

        $charts = new ChartCollection([
            // example structure
            'volume_by_gateway' => $aggregated['gateway_volumes'] ?? []
        ]);

        $alertsCollection = new AlertCollection($alerts);

        $healthCollection = new HealthCollection([
            'score' => $healthReport->overallScore,
            'status' => $healthReport->status,
            'critical_issues' => $healthReport->criticalIssues
        ]);

        $gatewayCollection = new GatewayCollection($realtime['gateway_balances'] ?? []);

        $walletCollection = new WalletCollection([]); // could be populated similarly

        $dto = new FinanceDashboardData(
            $kpis,
            $charts,
            $alertsCollection,
            $healthCollection,
            $gatewayCollection,
            $walletCollection
        );

        $executionTimeMs = (microtime(true) - $startTime) * 1000;
        \Log::info('FinanceDashboardService executed', [
            'execution_time_ms' => $executionTimeMs
        ]);

        return $dto;
    }
}
