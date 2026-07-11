<?php

namespace App\Listeners;

use App\Events\CustomerSubscriptionLifecycleEvent;
use App\Services\Subscriptions\CustomerSubscriptionWebhookService;

class DispatchCustomerSubscriptionWebhookListener
{
    public function handle(CustomerSubscriptionLifecycleEvent $event): void
    {
        app(CustomerSubscriptionWebhookService::class)->dispatch(
            $event->eventType,
            $event->subscription,
            $event->invoice,
        );
    }
}
