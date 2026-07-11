<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WebhookDlq;
use Illuminate\Support\Facades\Queue;
use App\Services\GatewayMetricsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CheckSystemHealthCommand extends Command
{
    protected $signature = 'system:health-check';
    protected $description = 'Checks system health metrics and triggers alerts if limits are exceeded.';

    public function handle(GatewayMetricsService $metrics)
    {
        $this->info('Starting System Health Check...');

        // 1. Check DLQ Size
        $dlqPending = WebhookDlq::whereNull('resolved_at')->count();
        if ($dlqPending > 100) {
            $metrics->alertIncident('DLQ_OVERFLOW', "DLQ has {$dlqPending} pending items.", ['count' => $dlqPending]);
            $this->error("Alert Triggered: DLQ Overflow ({$dlqPending})");
        }

        // 2. Check High Queue Size
        try {
            $queueHighSize = Queue::size('high');
        } catch (\Throwable $e) {
            $queueHighSize = 0;
            \Illuminate\Support\Facades\Log::emergency("CRITICAL ALERT: Queue backend unavailable. " . $e->getMessage());
        }
        if ($queueHighSize > 500) {
            $metrics->alertIncident('QUEUE_HIGH_CONGESTION', "High queue has {$queueHighSize} pending jobs.", ['count' => $queueHighSize]);
            $this->error("Alert Triggered: Queue Congestion ({$queueHighSize})");
        }

        // 3. Check Circuit Breaker
        $cbStatus = Cache::get('circuit_breaker_offline_NEW_PROVIDER');
        if ($cbStatus) {
            $metrics->alertIncident('CIRCUIT_BREAKER_OFFLINE', "Circuit Breaker for NEW_PROVIDER is currently OFFLINE.");
            $this->error("Alert Triggered: Circuit Breaker Offline");
        }

        // 4. Check Webhook Failures Spike (e.g. > 50 in 15m)
        $failedCount = $metrics->getMetric('webhook_failed_total', '15m');
        if ($failedCount > 50) {
            $metrics->alertIncident('HIGH_WEBHOOK_FAILURE_RATE', "There were {$failedCount} webhook failures in the last 15 minutes.");
            $this->error("Alert Triggered: High Webhook Failure Rate");
        }

        // 5. Check Horizon
        $horizonStatus = 'OFFLINE';
        if (class_exists(\Laravel\Horizon\Contracts\MasterSupervisorRepository::class)) {
            try {
                $masters = app(\Laravel\Horizon\Contracts\MasterSupervisorRepository::class)->all();
                if ($masters) {
                    $horizonStatus = collect($masters)->every(function ($master) {
                        return $master->status === 'paused';
                    }) ? 'PAUSED' : 'RUNNING';
                }
            } catch (\Exception $e) {}
        }

        if ($horizonStatus === 'OFFLINE') {
            $metrics->alertIncident('HORIZON_OFFLINE', "Laravel Horizon is OFFLINE or not accessible.");
            $this->error("Alert Triggered: Horizon Offline");
        }

        // 6. Check Ledger Mismatch (mocked logic or real if exists in LedgerService)
        // Assume LedgerService has a method or we track it via metrics
        $mismatchCount = $metrics->getMetric('ledger_balance_mismatch_total', '24h');
        if ($mismatchCount > 0) {
            $metrics->alertIncident('LEDGER_MISMATCH_DETECTED', "Ledger detected {$mismatchCount} balance mismatch incidents in the last 24h.");
            $this->error("Alert Triggered: Ledger Mismatch Detected");
        }

        // 7. Check DB & Redis Connectivity
        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::emergency("CRITICAL ALERT: Database OFFLINE. " . $e->getMessage());
            // Se o DB cair, métricas DB não gravam. Usar Log.
        }

        try {
            \Illuminate\Support\Facades\Redis::ping();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::emergency("CRITICAL ALERT: Redis OFFLINE. " . $e->getMessage());
        }

        $this->info('System Health Check completed.');
    }
}
