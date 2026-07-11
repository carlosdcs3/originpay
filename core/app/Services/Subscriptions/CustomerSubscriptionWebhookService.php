<?php

namespace App\Services\Subscriptions;

use App\Models\CustomerSubscription;
use App\Models\SubscriptionInvoice;
use App\Services\WebhookDispatcher;

class CustomerSubscriptionWebhookService
{
    public function __construct(private readonly WebhookDispatcher $dispatcher)
    {
    }

    public function dispatch(
        string $eventType,
        CustomerSubscription $subscription,
        ?SubscriptionInvoice $invoice = null
    ): void {
        $subscription->loadMissing('latestInvoice.charge');

        if ($invoice) {
            $invoice->loadMissing('charge');
        }

        $this->dispatcher->dispatchOnce(
            $subscription->user_id,
            $eventType,
            $this->payload($subscription, $invoice),
            $this->idempotencyKey($eventType, $subscription, $invoice),
        );
    }

    private function payload(CustomerSubscription $subscription, ?SubscriptionInvoice $invoice): array
    {
        $charge = $invoice?->charge;

        return [
            'subscription' => [
                'id' => $subscription->uuid,
                'status' => $subscription->status?->value ?? $subscription->status,
                'amount' => (float) $subscription->amount,
                'currency' => $subscription->currency,
                'payment_method' => $subscription->payment_method,
                'interval' => $subscription->interval?->value ?? $subscription->interval,
                'interval_count' => $subscription->interval_count,
                'current_period_start' => $subscription->current_period_start?->toIso8601String(),
                'current_period_end' => $subscription->current_period_end?->toIso8601String(),
                'next_billing_at' => $subscription->next_billing_at?->toIso8601String(),
                'cancel_at_period_end' => $subscription->cancel_at_period_end,
                'canceled_at' => $subscription->canceled_at?->toIso8601String(),
                'metadata' => $subscription->metadata ?? [],
            ],
            'invoice' => $invoice ? [
                'id' => $invoice->uuid,
                'status' => $invoice->status?->value ?? $invoice->status,
                'amount_due' => (float) $invoice->amount_due,
                'amount_paid' => (float) $invoice->amount_paid,
                'currency' => $invoice->currency,
                'period_start' => $invoice->period_start?->toIso8601String(),
                'period_end' => $invoice->period_end?->toIso8601String(),
                'paid_at' => $invoice->paid_at?->toIso8601String(),
                'failed_at' => $invoice->failed_at?->toIso8601String(),
                'metadata' => $invoice->metadata ?? [],
            ] : null,
            'charge' => $charge ? [
                'id' => $charge->uuid,
                'status' => $charge->status?->value ?? $charge->status,
                'amount' => (float) $charge->amount,
                'currency' => $subscription->currency,
            ] : null,
            'customer' => [
                'name' => $subscription->customer_name,
                'email' => $subscription->customer_email,
                'document' => $subscription->customer_document,
            ],
        ];
    }

    private function idempotencyKey(
        string $eventType,
        CustomerSubscription $subscription,
        ?SubscriptionInvoice $invoice
    ): string {
        return implode(':', [
            'customer_subscription',
            $eventType,
            $subscription->id,
            $invoice?->id ?? 'subscription',
        ]);
    }
}
