<?php
namespace App\Services\Connect;

use Carbon\Carbon;

class ConnectBillingPolicy
{
    protected $gracePeriodDays = 3;

    public function isPastDueWithinGracePeriod($subscription)
    {
        if ($subscription->status !== 'past_due') return false;
        
        $pastDueSince = $subscription->ends_at ?? $subscription->updated_at;
        return Carbon::now()->diffInDays($pastDueSince) <= $this->gracePeriodDays;
    }
}
