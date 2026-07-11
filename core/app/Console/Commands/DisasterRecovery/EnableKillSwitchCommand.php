<?php

namespace App\Console\Commands\DisasterRecovery;

use Illuminate\Console\Command;
use App\Services\DisasterRecovery\EmergencyCircuitBreaker;

class EnableKillSwitchCommand extends Command
{
    protected $signature = 'emergency:enable {switch} {--reason=CLI Action}';
    protected $description = 'Enable an emergency kill switch to block specific platform operations.';

    public function handle(EmergencyCircuitBreaker $breaker)
    {
        $switch = $this->argument('switch');
        $reason = $this->option('reason');
        
        if (!str_starts_with($switch, 'kill_switch:')) {
            $switch = 'kill_switch:' . $switch;
        }

        if ($this->confirm("Are you sure you want to ENABLE the kill switch [{$switch}]? This may block financial operations!")) {
            $breaker->setSwitch($switch, true, 1, $reason);
            $this->info("Kill switch {$switch} is now ENABLED.");
        } else {
            $this->warn("Operation cancelled.");
        }
    }
}
