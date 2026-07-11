<?php

namespace App\Jobs\Disputes;

use App\Models\Dispute;
use App\Services\DisputeSlaService;
use App\Services\DisputeEventService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckDisputeSlaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(DisputeSlaService $slaService, DisputeEventService $eventService): void
    {
        // 1. Fetch all open disputes
        $openDisputes = Dispute::whereNotIn('status', ['won', 'lost', 'canceled', 'closed'])
            ->whereNotNull('due_at')
            ->get();

        // 2. Loop and check SLAs
        foreach ($openDisputes as $dispute) {
            $slaService->checkAndEscalate($dispute, $eventService);
        }
    }
}
