<?php

namespace App\Services\PaymentLinks;

use App\Enums\ChargeStatus;
use App\Enums\CustomerSubscriptionStatus;
use App\Models\Charge;
use App\Models\CustomerSubscription;
use App\Models\PaymentLink;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class PaymentLinkStatusSyncService
{
    public function sync(PaymentLink $link): PaymentLink
    {
        $status = $this->resolvedStatus($link);

        if ($status !== $link->status) {
            $updates = ['status' => $status];
            if ($status === PaymentLink::STATUS_PAID) {
                $updates['paid_at'] = $link->paid_at ?: now();
            }
            if ($status === PaymentLink::STATUS_CANCELED) {
                $updates['canceled_at'] = $link->canceled_at ?: now();
            }

            $link->update($updates);
            $link->status = $status;
        }

        return $link;
    }

    public function syncForCharge(Charge $charge): void
    {
        if (! Schema::hasTable('payment_links')) {
            Log::warning('Payment links table is missing; skipping charge link sync.', [
                'charge_id' => $charge->id,
            ]);

            return;
        }

        PaymentLink::where('charge_id', $charge->id)
            ->whereIn('status', [
                PaymentLink::STATUS_PENDING,
                PaymentLink::STATUS_AWAITING_PAYMENT,
                PaymentLink::STATUS_PAID,
                PaymentLink::STATUS_EXPIRED,
            ])
            ->get()
            ->each(fn (PaymentLink $link) => $this->sync($link->setRelation('charge', $charge)));
    }

    public function syncForSubscription(CustomerSubscription $subscription): void
    {
        if (! Schema::hasTable('payment_links')) {
            Log::warning('Payment links table is missing; skipping subscription link sync.', [
                'subscription_id' => $subscription->id,
            ]);

            return;
        }

        PaymentLink::where('customer_subscription_id', $subscription->id)
            ->where('status', PaymentLink::STATUS_ACTIVE)
            ->get()
            ->each(fn (PaymentLink $link) => $this->sync($link->setRelation('subscription', $subscription)));
    }

    private function resolvedStatus(PaymentLink $link): string
    {
        if ($link->status === PaymentLink::STATUS_CANCELED) {
            return PaymentLink::STATUS_CANCELED;
        }

        if (in_array($link->status, [PaymentLink::STATUS_PAID, PaymentLink::STATUS_FAILED], true)) {
            return $link->status;
        }

        if ($link->relationLoaded('subscription') && $link->subscription?->status === CustomerSubscriptionStatus::CANCELED) {
            return PaymentLink::STATUS_CANCELED;
        }

        if ($link->relationLoaded('charge')) {
            return match ($link->charge?->status) {
                ChargeStatus::PAID => PaymentLink::STATUS_PAID,
                ChargeStatus::EXPIRED, ChargeStatus::CANCELLED, ChargeStatus::REFUNDED => PaymentLink::STATUS_EXPIRED,
                default => $link->isExpired() ? PaymentLink::STATUS_EXPIRED : ($link->charge_id ? PaymentLink::STATUS_AWAITING_PAYMENT : PaymentLink::STATUS_PENDING),
            };
        }

        return $link->isExpired() ? PaymentLink::STATUS_EXPIRED : $link->status;
    }
}
