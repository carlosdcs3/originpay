<?php
namespace App\Services\Connect;

use App\Models\Connect\Subscription;

class ConnectSubscriptionService
{
    protected $billingPolicy;

    public function __construct(ConnectBillingPolicy $billingPolicy)
    {
        $this->billingPolicy = $billingPolicy;
    }

    public function hasActiveAccess($merchantId)
    {
        $subscription = Subscription::where('merchant_id', $merchantId)->first();
        if (!$subscription) return false;

        if (in_array($subscription->status, ['active', 'trialing'])) {
            return true;
        }

        if ($subscription->status === 'past_due') {
            return $this->billingPolicy->isPastDueWithinGracePeriod($subscription);
        }

        return false;
    }
}
