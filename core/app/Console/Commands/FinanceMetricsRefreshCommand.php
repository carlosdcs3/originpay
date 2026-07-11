<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Finance\FinanceMetricsService;

class FinanceMetricsRefreshCommand extends Command
{
    protected $signature = 'finance:metrics-refresh';
    protected $description = 'Invalidate and rebuild aggregated finance metrics cache';

    public function handle(FinanceMetricsService $service)
    {
        $this->info('Invalidating metrics cache...');
        $service->invalidateAggregatedCache();
        
        $this->info('Rebuilding metrics cache...');
        $metrics = $service->getAggregatedMetrics();
        
        $this->info('Metrics refreshed successfully. (TPV Daily: ' . ($metrics['tpv_daily'] ?? 0) . ')');
    }
}
