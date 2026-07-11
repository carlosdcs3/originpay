<?php

namespace App\Observers;

use App\Models\CustomerSubscription;
use App\Services\PaymentLinks\PaymentLinkStatusSyncService;

class CustomerSubscriptionPaymentLinkObserver
{
    public function updated(CustomerSubscription $subscription): void
    {
        if (! $subscription->wasChanged('status')) {
            return;
        }

        app(PaymentLinkStatusSyncService::class)->syncForSubscription($subscription);
    }
}
