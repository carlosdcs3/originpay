<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Finance\FinanceAlertService;

class FinanceAlertsCheckCommand extends Command
{
    protected $signature = 'finance:alerts-check';
    protected $description = 'Evaluate finance alert rules and emit active alerts';

    public function handle(FinanceAlertService $service)
    {
        $this->info('Evaluating finance alerts...');
        $alerts = $service->getActiveAlerts();
        
        if (empty($alerts)) {
            $this->info('No active alerts triggered.');
            return;
        }

        $this->warn(count($alerts) . ' alerts triggered!');
        // Here we would dispatch notifications or log to the DB
    }
}
