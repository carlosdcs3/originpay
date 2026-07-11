<?php

namespace App\Domain\Disputes\Listeners;

use App\Domain\Disputes\Events\MerchantReplied;
use App\Services\DisputeHealthService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class UpdateHealthScoreListener implements ShouldQueue
{
    public function __construct(
        protected DisputeHealthService $healthService
    ) {}

    public function handle(MerchantReplied $event): void
    {
        // Calculate health score securely decoupled from the HTTP request
        $this->healthService->updateHealth($event->dispute);
        
        Log::info("UpdateHealthScoreListener processed for event: {$event->eventId}", [
            'correlation_id' => $event->correlationId
        ]);
    }
}
