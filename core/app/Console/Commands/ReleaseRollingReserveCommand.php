<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\Treasury\ReleaseRollingReserveJob;

class ReleaseRollingReserveCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'finance:release-reserve';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatches the job to release expired rolling reserves';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Dispatching ReleaseRollingReserveJob...');
        dispatch(new ReleaseRollingReserveJob());
        $this->info('Job dispatched successfully.');
        return 0;
    }
}
