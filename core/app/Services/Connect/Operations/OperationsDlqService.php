<?php
namespace App\Services\Connect\Operations;

use App\Models\Connect\ConnectCampaignDlq;
use App\Models\Connect\ConnectCampaignRecipient;
use App\Jobs\Connect\ProcessCampaignRecipientJob;

class OperationsDlqService
{
    public function recoverRecipient(int $dlqId, int $merchantId)
    {
        $dlq = ConnectCampaignDlq::where('id', $dlqId)->where('merchant_id', $merchantId)->firstOrFail();
        $recipient = $dlq->recipient;

        // Reset recipient state securely without altering its core ID
        $recipient->update([
            'status' => ConnectCampaignRecipient::STATUS_QUEUED,
            'attempts' => 0, // Fresh start
            'failed_reason' => null
        ]);
        
        $campaign = $recipient->campaign;

        // Push back into the native pipeline respecting priority
        ProcessCampaignRecipientJob::dispatch($recipient->id)
            ->onQueue('connect_' . $campaign->channel);
            
        // Delete from DLQ (Archived)
        $dlq->delete();
        
        return true;
    }
}
