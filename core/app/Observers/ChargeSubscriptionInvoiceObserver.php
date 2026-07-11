<?php

namespace App\Observers;

use App\Enums\ChargeStatus;
use App\Models\Charge;
use App\Services\PaymentLinks\PaymentLinkStatusSyncService;
use App\Services\Subscriptions\CustomerSubscriptionPaymentSyncService;

class ChargeSubscriptionInvoiceObserver
{
    public function updated(Charge $charge): void
    {
        if (! $charge->wasChanged('status')) {
            return;
        }

        app(PaymentLinkStatusSyncService::class)->syncForCharge($charge);

        if (! in_array($charge->status, [
            ChargeStatus::EXPIRED,
            ChargeStatus::CANCELLED,
            ChargeStatus::REFUNDED,
        ], true)) {
            return;
        }

        app(CustomerSubscriptionPaymentSyncService::class)
            ->markInvoiceFailedForCharge($charge, 'Charge status changed to ' . $charge->status->value);
    }
}
