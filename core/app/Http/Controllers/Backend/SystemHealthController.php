<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use App\Models\WebhookDlq;
use App\Models\WebhookEvent;
use App\Services\GatewayMetricsService;
use App\Payment\Modern\ModernPaymentGatewayFactory;
use App\Enums\ProviderType;

class SystemHealthController extends Controller
{
    protected GatewayMetricsService $metrics;

    public function __construct(GatewayMetricsService $metrics)
    {
        $this->metrics = $metrics;
    }

    public function index()
    {
        // DB Status
        $dbStatus = 'ONLINE';
        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            $dbStatus = 'OFFLINE';
        }

        // Redis/Cache Status
        $cacheStatus = 'ONLINE';
        try {
            Cache::store()->get('ping'); // simple test
        } catch (\Exception $e) {
            $cacheStatus = 'OFFLINE';
        }

        // Circuit Breaker Check
        $cbStatus = Cache::get('circuit_breaker_offline_NEW_PROVIDER') ? 'OFFLINE' : 'ONLINE';

        // DLQ & Queue
        $dlqPending = WebhookDlq::whereNull('resolved_at')->count();
        $queueHighSize = Queue::size('high');
        
        // Let's assume standard queue table name for failed jobs if using database queue
        $failedJobs = DB::table('failed_jobs')->count() ?? 0;

        // Metrics from 15m window
        $webhookReceived = $this->metrics->getMetric('webhook_received_total', '15m');
        $webhookFailed = $this->metrics->getMetric('webhook_failed_total', '15m');
        $latencyMs = $this->metrics->getLatencyAvg('webhook_processing_latency_ms', '15m');

        // Warnings / Errors
        $warnings = [];
        if ($dlqPending > 100) $warnings[] = "DLQ is accumulating ({$dlqPending} pending).";
        if ($queueHighSize > 500) $warnings[] = "High Queue is congested ({$queueHighSize} jobs).";
        if ($cbStatus === 'OFFLINE') $warnings[] = "Circuit Breaker is OFFLINE.";

        // Horizon Status
        $horizonStatus = 'OFFLINE';
        if (class_exists(\Laravel\Horizon\Contracts\MasterSupervisorRepository::class)) {
            try {
                $masters = app(\Laravel\Horizon\Contracts\MasterSupervisorRepository::class)->all();
                if ($masters) {
                    $horizonStatus = collect($masters)->every(function ($master) {
                        return $master->status === 'paused';
                    }) ? 'PAUSED' : 'RUNNING';
                }
            } catch (\Exception $e) {
                // Horizon not running or not accessible
            }
        }

        // Fetch overall health score
        $healthService = app(\App\Services\FinancialHealthScoreService::class);
        $health = $healthService->calculateScore();

        // Check Backup
        $backupDir = storage_path('backups');
        $backupStatus = 'Unknown';
        $backupSize = '0 MB';
        $backupDate = 'None';
        if (\Illuminate\Support\Facades\File::exists($backupDir)) {
            $files = \Illuminate\Support\Facades\File::files($backupDir);
            $backups = array_filter($files, function($file) {
                return str_ends_with($file->getFilename(), '.sql.gz');
            });
            if (!empty($backups)) {
                usort($backups, function($a, $b) {
                    return $b->getMTime() <=> $a->getMTime();
                });
                $latest = $backups[0];
                $backupSize = round($latest->getSize() / 1024 / 1024, 2) . ' MB';
                $backupDate = \Carbon\Carbon::createFromTimestamp($latest->getMTime())->diffForHumans();
                $backupStatus = 'Healthy';
                
                // Fast check age
                if (\Carbon\Carbon::createFromTimestamp($latest->getMTime())->diffInHours(now()) >= 24) {
                    $backupStatus = 'Expired';
                }
            } else {
                $backupStatus = 'Missing';
            }
        }

        return view('backend.system.health', compact(
            'dbStatus', 'cacheStatus', 'cbStatus', 'dlqPending', 'queueHighSize', 'failedJobs',
            'webhookReceived', 'webhookFailed', 'latencyMs', 'warnings', 'horizonStatus', 'health',
            'backupStatus', 'backupSize', 'backupDate'
        ));
    }

    public function healthCheck(Request $request, ModernPaymentGatewayFactory $factory)
    {
        // Optional active healthcheck against the Gateway
        $providerStr = $request->get('provider', 'NEW_PROVIDER');
        $providerType = ProviderType::tryFrom(strtoupper($providerStr));
        
        if (!$providerType) {
            return back()->with('error', 'Invalid provider');
        }

        try {
            $gateway = $factory->getGateway($providerType);
            
            // Log this action since it requires admin
            \App\Models\WebhookAdminAudit::create([
                'admin_id' => auth()->guard('admin')->id() ?? auth()->id() ?? 1,
                'action' => 'triggered_health_check',
                'target_type' => 'gateway',
                'target_id' => 0,
                'reason' => 'Manual Health Check via Admin UI',
                'ip_address' => request()->ip(),
            ]);

            $isHealthy = $gateway->healthCheck(); // Suppose gateway has this

            if ($isHealthy) {
                return back()->with('success', "Gateway {$providerStr} is ONLINE and healthy.");
            } else {
                return back()->with('error', "Gateway {$providerStr} reported UNHEALTHY.");
            }
        } catch (\Exception $e) {
            return back()->with('error', "Gateway {$providerStr} Health Check Failed: " . $e->getMessage());
        }
    }
}
