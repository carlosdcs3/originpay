<?php

namespace App\Services\Subscriptions;

use App\Enums\ChargeStatus;
use App\Enums\CustomerSubscriptionStatus;
use App\Enums\SubscriptionInvoiceStatus;
use App\Events\CustomerSubscriptionLifecycleEvent;
use App\Models\Charge;
use App\Models\SubscriptionInvoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class CustomerSubscriptionPaymentSyncService
{
    public function markInvoicePaidForCharge(Charge $charge, ?float $amountPaid = null): void
    {
        if (! $charge->exists || ! $charge->id) {
            return;
        }

        $synced = DB::transaction(function () use ($charge, $amountPaid) {
            $invoice = $this->invoiceForCharge($charge);

            if (! $invoice || $invoice->status === SubscriptionInvoiceStatus::PAID) {
                return null;
            }

            $subscription = $invoice->subscription()->lockForUpdate()->first();
            if (! $subscription) {
                return null;
            }

            $invoice->update([
                'status' => SubscriptionInvoiceStatus::PAID,
                'amount_paid' => number_format($amountPaid ?? (float) $charge->amount, 2, '.', ''),
                'paid_at' => $invoice->paid_at ?? now(),
                'failed_at' => null,
                'last_error' => null,
                'metadata' => array_merge($invoice->metadata ?? [], [
                    'payment_confirmed_at' => now()->toIso8601String(),
                    'charge_status' => ChargeStatus::PAID->value,
                ]),
            ]);

            $subscription->update([
                'status' => CustomerSubscriptionStatus::ACTIVE,
                'last_error' => null,
            ]);

            return [$subscription->refresh(), $invoice->refresh()->load('charge')];
        });

        if ($synced) {
            [$subscription, $invoice] = $synced;
            event(new CustomerSubscriptionLifecycleEvent('subscription_invoice.paid', $subscription, $invoice));
            event(new CustomerSubscriptionLifecycleEvent('customer_subscription.activated', $subscription, $invoice));
        }
    }

    public function markInvoiceFailedForCharge(Charge $charge, string $reason): void
    {
        if (! $charge->exists || ! $charge->id) {
            return;
        }

        $synced = DB::transaction(function () use ($charge, $reason) {
            $invoice = $this->invoiceForCharge($charge);

            if (! $invoice || $invoice->status === SubscriptionInvoiceStatus::PAID) {
                return null;
            }

            if ($invoice->status === SubscriptionInvoiceStatus::FAILED) {
                return null;
            }

            $subscription = $invoice->subscription()->lockForUpdate()->first();
            if (! $subscription) {
                return null;
            }

            $invoice->update([
                'status' => SubscriptionInvoiceStatus::FAILED,
                'failed_at' => $invoice->failed_at ?? now(),
                'last_error' => $reason,
                'metadata' => array_merge($invoice->metadata ?? [], [
                    'payment_failed_at' => now()->toIso8601String(),
                    'charge_status' => $charge->status?->value ?? (string) $charge->status,
                    'failure_reason' => $reason,
                ]),
            ]);

            $nextStatus = $subscription->status === CustomerSubscriptionStatus::ACTIVE
                ? CustomerSubscriptionStatus::PAST_DUE
                : CustomerSubscriptionStatus::INCOMPLETE;

            $subscription->update([
                'status' => $nextStatus,
                'last_error' => $reason,
                'metadata' => array_merge($subscription->metadata ?? [], [
                    'last_payment_failure' => $reason,
                ]),
            ]);

            return [$subscription->refresh(), $invoice->refresh()->load('charge'), $nextStatus];
        });

        if ($synced) {
            [$subscription, $invoice, $nextStatus] = $synced;
            event(new CustomerSubscriptionLifecycleEvent('subscription_invoice.failed', $subscription, $invoice));
            if ($nextStatus === CustomerSubscriptionStatus::PAST_DUE) {
                event(new CustomerSubscriptionLifecycleEvent('customer_subscription.past_due', $subscription, $invoice));
            }
        }
    }

    private function invoiceForCharge(Charge $charge): ?SubscriptionInvoice
    {
        if (! Schema::hasTable('subscription_invoices')) {
            Log::warning('Subscription invoices table is missing; skipping subscription sync for charge.', [
                'charge_id' => $charge->id,
            ]);

            return null;
        }

        return SubscriptionInvoice::with('subscription')
            ->where('charge_id', $charge->id)
            ->where('user_id', $charge->user_id)
            ->lockForUpdate()
            ->first();
    }
}
