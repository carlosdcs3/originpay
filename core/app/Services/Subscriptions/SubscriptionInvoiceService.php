<?php

namespace App\Services\Subscriptions;

use App\Enums\SubscriptionInvoiceStatus;
use App\Models\CustomerSubscription;
use App\Models\SubscriptionInvoice;
use Illuminate\Support\Str;

class SubscriptionInvoiceService
{
    public function createFirstInvoice(CustomerSubscription $subscription): SubscriptionInvoice
    {
        $invoice = SubscriptionInvoice::create([
            'uuid' => (string) Str::uuid(),
            'customer_subscription_id' => $subscription->id,
            'user_id' => $subscription->user_id,
            'status' => SubscriptionInvoiceStatus::OPEN,
            'period_start' => $subscription->current_period_start,
            'period_end' => $subscription->current_period_end,
            'amount_due' => $subscription->amount,
            'amount_paid' => 0,
            'currency' => $subscription->currency,
            'due_at' => now()->addDays(3),
            'metadata' => [
                'source' => 'first_subscription_invoice',
            ],
            'idempotency_key' => $subscription->idempotency_key
                ? $subscription->idempotency_key . ':invoice:first'
                : null,
        ]);

        foreach ($subscription->items as $item) {
            $invoice->items()->create([
                'customer_subscription_item_id' => $item->id,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_amount' => $item->unit_amount,
                'total_amount' => $item->total_amount,
                'metadata' => $item->metadata,
            ]);
        }

        return $invoice->refresh();
    }
}
