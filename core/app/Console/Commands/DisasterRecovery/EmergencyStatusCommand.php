<?php

namespace App\Console\Commands\DisasterRecovery;

use Illuminate\Console\Command;
use App\Services\DisasterRecovery\EmergencyCircuitBreaker;

class EmergencyStatusCommand extends Command
{
    protected $signature = 'emergency:status';
    protected $description = 'View the status of all emergency kill switches.';

    public function handle(EmergencyCircuitBreaker $breaker)
    {
        $this->info('--- Emergency Kill Switches Status ---');
        $statuses = $breaker->getAllStatuses();

        $rows = [];
        foreach ($statuses as $switch => $isActive) {
            $rows[] = [
                $switch,
                $isActive ? '<fg=red;options=bold>ENABLED (BLOCKED)</>' : '<fg=green>DISABLED (OK)</>'
            ];
        }

        $this->table(['Switch Name', 'Status'], $rows);
    }
}
