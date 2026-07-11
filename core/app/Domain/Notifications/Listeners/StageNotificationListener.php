<?php

namespace App\Domain\Notifications\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class StageNotificationListener implements ShouldQueue
{
    public function handle($event): void
    {
        Log::info("StageNotificationListener processed for event: {$event->eventId}");
    }
}
