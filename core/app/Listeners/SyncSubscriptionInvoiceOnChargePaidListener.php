<?php

namespace App\Listeners;

use App\Events\ChargePaidEvent;
use App\Services\Subscriptions\CustomerSubscriptionPaymentSyncService;

class SyncSubscriptionInvoiceOnChargePaidListener
{
    public function handle(ChargePaidEvent $event): void
    {
        app(CustomerSubscriptionPaymentSyncService::class)
            ->markInvoicePaidForCharge($event->charge, $event->amountPaid);
    }
}
