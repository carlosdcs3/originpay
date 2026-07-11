<?php

namespace App\Domain\Disputes\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class RefreshActionQueueListener implements ShouldQueue
{
    public function handle($event): void
    {
        Log::info("RefreshActionQueueListener processed for event: {$event->eventId}");
    }
}
