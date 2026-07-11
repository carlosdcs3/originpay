<?php
namespace App\Services\Connect\Operations;

use App\Models\Connect\Campaign;
use App\Models\Connect\ConnectCampaignExecution;
use Illuminate\Support\Facades\DB;

class OperationsCampaignService
{
    public function getActiveCampaigns(int $merchantId)
    {
        return Campaign::forMerchant($merchantId)
            ->whereIn('status', [Campaign::STATUS_RUNNING, Campaign::STATUS_PREPARING, Campaign::STATUS_QUEUEING, Campaign::STATUS_RESOLVING])
            ->get();
    }
    
    public function getCampaignTimeline(int $merchantId, int $campaignId)
    {
        return \App\Models\Connect\ConnectEventLog::where('merchant_id', $merchantId)
            ->where('aggregate_type', 'Campaign')
            ->where('aggregate_id', $campaignId)
            ->orderBy('occurred_at', 'asc')
            ->get();
    }
}
