<?php

namespace App\Console\Commands\DisasterRecovery;

use Illuminate\Console\Command;
use App\Services\DisasterRecovery\EmergencyCircuitBreaker;

class DisableKillSwitchCommand extends Command
{
    protected $signature = 'emergency:disable {switch} {--reason=CLI Action}';
    protected $description = 'Disable an emergency kill switch to restore platform operations.';

    public function handle(EmergencyCircuitBreaker $breaker)
    {
        $switch = $this->argument('switch');
        $reason = $this->option('reason');
        
        if (!str_starts_with($switch, 'kill_switch:')) {
            $switch = 'kill_switch:' . $switch;
        }

        $breaker->setSwitch($switch, false, 1, $reason);
        $this->info("Kill switch {$switch} is now DISABLED (operations restored).");
    }
}
