<?php

namespace App\Domain\Core\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class AuditTrailListener implements ShouldQueue
{
    public function handle($event): void
    {
        Log::info("AuditTrailListener processed for event: {$event->eventId}");
    }
}
