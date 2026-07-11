<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Finance\FinanceHealthService;

class FinanceHealthCheckCommand extends Command
{
    protected $signature = 'finance:health-check';
    protected $description = 'Run the full financial health check and emit events if issues are found';

    public function handle(FinanceHealthService $service)
    {
        $this->info('Starting Finance Health Check...');
        $report = $service->generateReport();

        $this->table(
            ['Metric', 'Value'],
            [
                ['Score', $report->overallScore],
                ['Status', $report->status],
                ['Warnings', count($report->warnings)],
                ['Critical Issues', count($report->criticalIssues)],
            ]
        );

        if ($report->status === 'Critical') {
            $this->error('CRITICAL ISSUES FOUND');
            // In a real scenario, this would dispatch an event or notification
        } elseif ($report->status === 'Warning') {
            $this->warn('Warnings found');
        } else {
            $this->info('System is healthy');
        }
    }
}
