<?php

namespace App\Services\Subscriptions;

use App\Data\CreateCustomerSubscriptionData;
use App\Enums\CustomerSubscriptionStatus;
use App\Enums\SubscriptionInvoiceStatus;
use App\Events\CustomerSubscriptionLifecycleEvent;
use App\Models\CustomerSubscription;
use App\Models\User;
use App\Services\ChargeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class CustomerSubscriptionService
{
    public function __construct(
        private readonly SubscriptionPeriodCalculator $periodCalculator,
        private readonly SubscriptionInvoiceService $invoiceService,
        private readonly ChargeService $chargeService,
    ) {
    }

    public function create(User $merchant, CreateCustomerSubscriptionData $data): CustomerSubscription
    {
        if ($data->idempotencyKey) {
            $existing = CustomerSubscription::where('user_id', $merchant->id)
                ->where('idempotency_key', $data->idempotencyKey)
                ->first();

            if ($existing) {
                return $existing->load(['items', 'latestInvoice.charge']);
            }
        }

        $subscription = DB::transaction(function () use ($merchant, $data) {
            $period = $this->periodCalculator->firstPeriod($data->startAt, $data->interval, $data->intervalCount);

            $subscription = CustomerSubscription::create([
                'uuid' => (string) Str::uuid(),
                'user_id' => $merchant->id,
                'customer_name' => $data->customerName,
                'customer_email' => $data->customerEmail,
                'customer_document' => $data->customerDocument,
                'status' => CustomerSubscriptionStatus::PENDING,
                'amount' => $data->amount,
                'currency' => $data->currency,
                'payment_method' => $data->paymentMethod,
                'interval' => $data->interval,
                'interval_count' => $data->intervalCount,
                'description' => $data->description,
                'start_at' => $data->startAt,
                'current_period_start' => $period->start,
                'current_period_end' => $period->end,
                'next_billing_at' => $period->nextBillingAt,
                'metadata' => $data->metadata,
                'idempotency_key' => $data->idempotencyKey,
            ]);

            $subscription->items()->create([
                'description' => $data->description,
                'quantity' => 1,
                'unit_amount' => $data->amount,
                'total_amount' => $data->amount,
                'metadata' => $data->metadata,
            ]);

            return $subscription->load('items');
        });

        $invoice = $this->invoiceService->createFirstInvoice($subscription);

        try {
            $charge = $this->chargeService->create($merchant, (float) $data->amount, $data->paymentMethod, [
                'idempotency_key' => $data->idempotencyKey
                    ? $data->idempotencyKey . ':charge:first'
                    : 'sub_' . $subscription->uuid . ':charge:first',
                'name' => $data->customerName,
                'email' => $data->customerEmail,
                'document' => $data->customerDocument,
                'description' => $data->description,
            ]);

            $invoice->update([
                'charge_id' => $charge->id,
                'status' => SubscriptionInvoiceStatus::OPEN,
            ]);
        } catch (Throwable $exception) {
            $error = $exception->getMessage();

            $subscription->update([
                'status' => CustomerSubscriptionStatus::INCOMPLETE,
                'last_error' => $error,
                'metadata' => array_merge($subscription->metadata ?? [], [
                    'first_charge_error' => $error,
                ]),
            ]);

            $invoice->update([
                'status' => SubscriptionInvoiceStatus::FAILED,
                'failed_at' => now(),
                'last_error' => $error,
                'metadata' => array_merge($invoice->metadata ?? [], [
                    'first_charge_error' => $error,
                ]),
            ]);

            $subscription = $subscription->refresh()->load(['items', 'latestInvoice.charge']);
            $invoice = $invoice->refresh()->load('charge');
            event(new CustomerSubscriptionLifecycleEvent('customer_subscription.created', $subscription, $invoice));
            event(new CustomerSubscriptionLifecycleEvent('subscription_invoice.created', $subscription, $invoice));
            event(new CustomerSubscriptionLifecycleEvent('subscription_invoice.failed', $subscription, $invoice));

            return $subscription;
        }

        $subscription = $subscription->refresh()->load(['items', 'latestInvoice.charge']);
        $invoice = $invoice->refresh()->load('charge');
        event(new CustomerSubscriptionLifecycleEvent('customer_subscription.created', $subscription, $invoice));
        event(new CustomerSubscriptionLifecycleEvent('subscription_invoice.created', $subscription, $invoice));

        return $subscription;
    }

    public function cancel(User $merchant, CustomerSubscription $subscription, bool $atPeriodEnd = false): CustomerSubscription
    {
        abort_unless($subscription->user_id === $merchant->id, 404);

        if ($atPeriodEnd) {
            $subscription->update(['cancel_at_period_end' => true]);

            return $subscription->refresh();
        }

        $subscription->update([
            'status' => CustomerSubscriptionStatus::CANCELED,
            'cancel_at_period_end' => false,
            'canceled_at' => now(),
        ]);

        $subscription = $subscription->refresh();
        event(new CustomerSubscriptionLifecycleEvent('customer_subscription.canceled', $subscription));

        return $subscription;
    }
}
