<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\GatewayLog;
use Carbon\Carbon;

class PruneGatewayLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gateway:logs:prune {--days=30 : The number of days to retain logs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune old gateway logs from the database to save space';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $date = Carbon::now()->subDays($days);

        $deleted = GatewayLog::where('created_at', '<', $date)->delete();

        $this->info("Pruned {$deleted} gateway logs older than {$days} days.");
    }
}
