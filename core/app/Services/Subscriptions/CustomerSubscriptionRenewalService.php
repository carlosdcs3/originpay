<?php

namespace App\Services\Subscriptions;

use App\Enums\CustomerSubscriptionStatus;
use App\Enums\SubscriptionInvoiceStatus;
use App\Events\CustomerSubscriptionLifecycleEvent;
use App\Models\CustomerSubscription;
use App\Models\SubscriptionInvoice;
use App\Models\User;
use App\Services\ChargeService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class CustomerSubscriptionRenewalService
{
    public function __construct(
        private readonly SubscriptionPeriodCalculator $periodCalculator,
        private readonly ChargeService $chargeService,
    ) {
    }

    public function processDue(?int $limit = null): array
    {
        $query = CustomerSubscription::query()
            ->whereIn('status', [
                CustomerSubscriptionStatus::ACTIVE->value,
                CustomerSubscriptionStatus::PAST_DUE->value,
            ])
            ->whereNull('canceled_at')
            ->where('next_billing_at', '<=', now())
            ->orderBy('next_billing_at')
            ->orderBy('id');

        if ($limit) {
            $query->limit($limit);
        }

        $summary = [
            'processed' => 0,
            'renewed' => 0,
            'invoices_created' => 0,
            'charges_created' => 0,
            'failed' => 0,
            'canceled' => 0,
            'skipped' => 0,
        ];

        $query->pluck('id')->each(function (int $subscriptionId) use (&$summary) {
            $result = $this->renewOne($subscriptionId);
            $summary['processed']++;
            $summary[$result] = ($summary[$result] ?? 0) + 1;

            if ($result === 'renewed') {
                $summary['invoices_created']++;
                $summary['charges_created']++;
            }

            if ($result === 'failed') {
                $summary['invoices_created']++;
            }
        });

        return $summary;
    }

    public function renewOne(int $subscriptionId): string
    {
        $lock = Cache::lock("customer_subscription:renew:{$subscriptionId}", 300);

        if (! $lock->get()) {
            return 'skipped';
        }

        try {
            return $this->renewOneLocked($subscriptionId);
        } finally {
            optional($lock)->release();
        }
    }

    private function renewOneLocked(int $subscriptionId): string
    {
        $prepared = DB::transaction(function () use ($subscriptionId) {
            $subscription = CustomerSubscription::with('items')
                ->whereKey($subscriptionId)
                ->lockForUpdate()
                ->first();

            if (! $subscription || ! $this->isProcessable($subscription)) {
                return ['result' => 'skipped'];
            }

            if ($subscription->cancel_at_period_end && $subscription->current_period_end?->lte(now())) {
                $subscription->update([
                    'status' => CustomerSubscriptionStatus::CANCELED,
                    'canceled_at' => now(),
                    'cancel_at_period_end' => false,
                ]);

                event(new CustomerSubscriptionLifecycleEvent('customer_subscription.canceled', $subscription->refresh()));

                return ['result' => 'canceled'];
            }

            $periodStart = CarbonImmutable::parse($subscription->next_billing_at);
            $period = $this->periodCalculator->periodStartingAt(
                $periodStart,
                $subscription->interval,
                $subscription->interval_count
            );
            $idempotencyKey = $this->invoiceIdempotencyKey($subscription, $periodStart);

            $invoice = SubscriptionInvoice::where('customer_subscription_id', $subscription->id)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if (! $invoice) {
                $invoice = SubscriptionInvoice::create([
                    'uuid' => (string) Str::uuid(),
                    'customer_subscription_id' => $subscription->id,
                    'user_id' => $subscription->user_id,
                    'status' => SubscriptionInvoiceStatus::OPEN,
                    'period_start' => $period->start,
                    'period_end' => $period->end,
                    'amount_due' => $subscription->amount,
                    'amount_paid' => 0,
                    'currency' => $subscription->currency,
                    'due_at' => now()->addDays(3),
                    'metadata' => ['source' => 'subscription_renewal'],
                    'idempotency_key' => $idempotencyKey,
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
            }

            $subscription->update([
                'current_period_start' => $period->start,
                'current_period_end' => $period->end,
                'next_billing_at' => $period->nextBillingAt,
            ]);

            if ($invoice->charge_id) {
                return ['result' => 'skipped'];
            }

            return [
                'result' => 'charge',
                'subscription_id' => $subscription->id,
                'invoice_id' => $invoice->id,
                'merchant_id' => $subscription->user_id,
                'amount' => (float) $subscription->amount,
                'payment_method' => $subscription->payment_method,
                'charge_idempotency_key' => $idempotencyKey . ':charge',
                'customer' => [
                    'name' => $subscription->customer_name,
                    'email' => $subscription->customer_email,
                    'document' => $subscription->customer_document,
                ],
                'description' => $subscription->description,
            ];
        });

        if (($prepared['result'] ?? null) !== 'charge') {
            return $prepared['result'];
        }

        $invoice = SubscriptionInvoice::findOrFail($prepared['invoice_id']);
        $merchant = User::findOrFail($prepared['merchant_id']);

        try {
            $charge = $this->chargeService->create($merchant, $prepared['amount'], $prepared['payment_method'], [
                'idempotency_key' => $prepared['charge_idempotency_key'],
                'name' => $prepared['customer']['name'],
                'email' => $prepared['customer']['email'],
                'document' => $prepared['customer']['document'],
                'description' => $prepared['description'],
            ]);

            $invoice->update([
                'charge_id' => $charge->id,
                'status' => SubscriptionInvoiceStatus::OPEN,
            ]);

            $subscription = CustomerSubscription::findOrFail($prepared['subscription_id']);
            event(new CustomerSubscriptionLifecycleEvent('subscription_invoice.created', $subscription, $invoice->refresh()->load('charge')));

            return 'renewed';
        } catch (Throwable $exception) {
            $error = $exception->getMessage();

            DB::transaction(function () use ($prepared, $invoice, $error) {
                $subscription = CustomerSubscription::whereKey($prepared['subscription_id'])
                    ->lockForUpdate()
                    ->first();

                $invoice->update([
                    'status' => SubscriptionInvoiceStatus::FAILED,
                    'failed_at' => now(),
                    'last_error' => $error,
                    'metadata' => array_merge($invoice->metadata ?? [], [
                        'renewal_charge_error' => $error,
                    ]),
                ]);

                if ($subscription) {
                    $subscription->update([
                        'status' => CustomerSubscriptionStatus::PAST_DUE,
                        'last_error' => $error,
                        'metadata' => array_merge($subscription->metadata ?? [], [
                            'last_renewal_failure' => $error,
                        ]),
                    ]);
                }
            });

            $subscription = CustomerSubscription::find($prepared['subscription_id']);
            if ($subscription) {
                $invoice = $invoice->refresh()->load('charge');
                event(new CustomerSubscriptionLifecycleEvent('subscription_invoice.created', $subscription, $invoice));
                event(new CustomerSubscriptionLifecycleEvent('subscription_invoice.failed', $subscription, $invoice));
                event(new CustomerSubscriptionLifecycleEvent('customer_subscription.past_due', $subscription, $invoice));
            }

            return 'failed';
        }
    }

    private function isProcessable(CustomerSubscription $subscription): bool
    {
        if ($subscription->canceled_at || ! $subscription->next_billing_at || $subscription->next_billing_at->gt(now())) {
            return false;
        }

        return in_array($subscription->status, [
            CustomerSubscriptionStatus::ACTIVE,
            CustomerSubscriptionStatus::PAST_DUE,
        ], true);
    }

    private function invoiceIdempotencyKey(CustomerSubscription $subscription, CarbonImmutable $periodStart): string
    {
        return 'sub:' . $subscription->id . ':period:' . $periodStart->utc()->format('YmdHis');
    }
}
