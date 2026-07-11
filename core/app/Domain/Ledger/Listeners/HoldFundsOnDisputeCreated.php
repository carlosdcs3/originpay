<?php

namespace App\Domain\Ledger\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class HoldFundsOnDisputeCreated implements ShouldQueue
{
    public function handle($event): void
    {
        // MOCK: This would talk to the Ledger Service to freeze funds
        Log::info("HoldFundsOnDisputeCreated processed for event: {$event->eventId}", [
            'aggregate_id' => $event->getAggregateId(),
            'correlation_id' => $event->correlationId
        ]);
    }
}
