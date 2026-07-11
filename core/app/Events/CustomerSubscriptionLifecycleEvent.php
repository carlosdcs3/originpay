<?php

namespace App\Events;

use App\Models\CustomerSubscription;
use App\Models\SubscriptionInvoice;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CustomerSubscriptionLifecycleEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $eventType,
        public readonly CustomerSubscription $subscription,
        public readonly ?SubscriptionInvoice $invoice = null,
    ) {
    }
}
