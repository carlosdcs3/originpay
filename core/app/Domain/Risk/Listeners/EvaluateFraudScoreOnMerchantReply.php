<?php

namespace App\Domain\Risk\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class EvaluateFraudScoreOnMerchantReply implements ShouldQueue
{
    public function handle($event): void
    {
        // MOCK: This would trigger the ML Risk Engine
        Log::info("EvaluateFraudScoreOnMerchantReply processed for event: {$event->eventId}", [
            'correlation_id' => $event->correlationId
        ]);
    }
}
