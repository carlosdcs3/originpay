<?php

namespace App\Services\Connect;

use App\Models\Connect\ConnectCampaignRecipient;
use Illuminate\Support\Facades\Log;

class CampaignQuotaService
{
    public function allowsRecipients(int $merchantId, int $requested): bool
    {
        $limit = max(1, (int) config('connect_security.provisional_quotas.campaign_jobs', 10000));
        $current = ConnectCampaignRecipient::where('merchant_id', $merchantId)
            ->whereIn('status', ['queued', 'processing'])
            ->count();
        $allowed = $requested > 0 && ($current + $requested) <= $limit;

        if (! $allowed) {
            Log::warning('Connect campaign quota denied', [
                'merchant_id' => $merchantId,
                'operation' => 'campaign_jobs',
                'result' => 'denied',
            ]);
        }

        return $allowed;
    }
}
