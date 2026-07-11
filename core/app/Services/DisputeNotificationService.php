<?php

namespace App\Services;

use App\Models\Dispute;
use Illuminate\Support\Facades\Log;

class DisputeNotificationService
{
    /**
     * Stubs out the notification dispatcher.
     * Real implementations would use Mail::to(), Notification::route(), or Twilio.
     */
    public function notifyMerchant(Dispute $dispute, string $type, string $message)
    {
        // TODO: Push to actual notification queues
        Log::info("Mock Notification to Merchant ID {$dispute->merchant_id}", [
            'dispute_id' => $dispute->id,
            'type' => $type,
            'message' => $message
        ]);
    }

    public function notifyAnalyst(Dispute $dispute, string $type, string $message)
    {
        // TODO: Send Slack/Teams or email to Risk Ops
        Log::info("Mock Notification to Risk Ops", [
            'dispute_id' => $dispute->id,
            'type' => $type,
            'message' => $message
        ]);
    }
}
