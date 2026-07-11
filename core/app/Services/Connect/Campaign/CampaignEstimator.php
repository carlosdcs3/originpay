<?php
namespace App\Services\Connect\Campaign;

class CampaignEstimator
{
    public function estimate(int $audienceCount, string $channel): array
    {
        $ratePerSecond = match($channel) {
            'email' => 100,
            'whatsapp' => 20,
            'sms' => 50,
            default => 10
        };

        $timeSeconds = ceil($audienceCount / $ratePerSecond);

        return [
            'audience_count' => $audienceCount,
            'estimated_time_seconds' => $timeSeconds,
            'cost_placeholder' => 0.00
        ];
    }
}
