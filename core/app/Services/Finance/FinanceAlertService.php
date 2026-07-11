<?php

namespace App\Services\Finance;

use App\Data\Finance\FinanceAlertRule;

class FinanceAlertService
{
    /** @var FinanceAlertRule[] */
    private array $rules = [];

    public function __construct()
    {
        $this->loadRules();
    }

    private function loadRules()
    {
        // Future: Load from database (Admin configurable)
        // For now, load predefined rules
        $this->rules = [
            new FinanceAlertRule('balance < 0', 'CRITICAL', 60, true, 'Negative balance detected in a gateway', 'LEDGER'),
            new FinanceAlertRule('gateway_offline', 'HIGH', 30, true, 'Payment gateway is offline or unresponsive', 'INFRA'),
            new FinanceAlertRule('no_operations', 'WARNING', 120, true, 'Gateway has no operations assigned', 'CONFIG'),
            new FinanceAlertRule('divergent_wallet', 'CRITICAL', 60, true, 'Consolidated wallet balance differs from ledger breakdown', 'LEDGER'),
            new FinanceAlertRule('abnormal_volume', 'WARNING', 60, true, 'Unusually high transaction volume detected', 'METRICS'),
        ];
    }

    public function getActiveAlerts(): array
    {
        $startTime = microtime(true);
        $activeAlerts = [];

        // Logic to evaluate rules against current state would go here
        // (e.g., pulling metrics and evaluating conditions)

        $executionTimeMs = (microtime(true) - $startTime) * 1000;
        \Log::info('FinanceAlertService evaluated', [
            'execution_time_ms' => $executionTimeMs,
            'rules_checked' => count($this->rules),
            'alerts_triggered' => count($activeAlerts)
        ]);

        return $activeAlerts;
    }
}
