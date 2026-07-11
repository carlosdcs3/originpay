<?php

namespace Tests\Feature;

use App\Enums\ChargeStatus;
use App\Enums\CustomerSubscriptionStatus;
use App\Enums\SubscriptionInterval;
use App\Enums\SubscriptionInvoiceStatus;
use App\Events\ChargePaidEvent;
use App\Models\Charge;
use App\Models\CustomerSubscription;
use App\Models\SubscriptionInvoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class CustomerSubscriptionPaymentSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_charge_activates_subscription_and_marks_invoice_paid(): void
    {
        Queue::fake();
        [$subscription, $invoice, $charge] = $this->subscriptionInvoiceAndCharge();

        Event::dispatch(new ChargePaidEvent($charge, 100.00));

        $invoice->refresh();
        $subscription->refresh();

        $this->assertSame(SubscriptionInvoiceStatus::PAID, $invoice->status);
        $this->assertSame('100.00', $invoice->amount_paid);
        $this->assertNotNull($invoice->paid_at);
        $this->assertNull($invoice->failed_at);
        $this->assertSame(CustomerSubscriptionStatus::ACTIVE, $subscription->status);
        $this->assertSame('2026-01-01', $subscription->current_period_start->toDateString());
        $this->assertSame('2026-02-01', $subscription->current_period_end->toDateString());
        $this->assertSame('2026-02-01', $subscription->next_billing_at->toDateString());
    }

    public function test_duplicate_paid_event_is_idempotent(): void
    {
        Queue::fake();
        [$subscription, $invoice, $charge] = $this->subscriptionInvoiceAndCharge();

        Event::dispatch(new ChargePaidEvent($charge, 100.00));
        $firstPaidAt = $invoice->refresh()->paid_at;

        Event::dispatch(new ChargePaidEvent($charge, 100.00));

        $invoice->refresh();
        $subscription->refresh();

        $this->assertSame(SubscriptionInvoiceStatus::PAID, $invoice->status);
        $this->assertSame('100.00', $invoice->amount_paid);
        $this->assertTrue($invoice->paid_at->equalTo($firstPaidAt));
        $this->assertSame(CustomerSubscriptionStatus::ACTIVE, $subscription->status);
    }

    public function test_expired_charge_marks_invoice_failed_and_subscription_incomplete(): void
    {
        [$subscription, $invoice, $charge] = $this->subscriptionInvoiceAndCharge();

        $charge->update(['status' => ChargeStatus::EXPIRED]);

        $invoice->refresh();
        $subscription->refresh();

        $this->assertSame(SubscriptionInvoiceStatus::FAILED, $invoice->status);
        $this->assertNotNull($invoice->failed_at);
        $this->assertStringContainsString('expired', $invoice->last_error);
        $this->assertSame(CustomerSubscriptionStatus::INCOMPLETE, $subscription->status);
    }

    public function test_failed_charge_for_active_subscription_marks_past_due(): void
    {
        [$subscription, $invoice, $charge] = $this->subscriptionInvoiceAndCharge(CustomerSubscriptionStatus::ACTIVE);

        $charge->update(['status' => ChargeStatus::CANCELLED]);

        $invoice->refresh();
        $subscription->refresh();

        $this->assertSame(SubscriptionInvoiceStatus::FAILED, $invoice->status);
        $this->assertSame(CustomerSubscriptionStatus::PAST_DUE, $subscription->status);
    }

    public function test_subscription_from_another_merchant_is_not_affected(): void
    {
        $merchantA = User::factory()->create();
        $merchantB = User::factory()->create();
        $subscription = $this->subscriptionFor($merchantB);
        $charge = $this->chargeFor($merchantA);

        SubscriptionInvoice::create([
            'uuid' => (string) Str::uuid(),
            'customer_subscription_id' => $subscription->id,
            'user_id' => $merchantB->id,
            'charge_id' => $charge->id,
            'status' => SubscriptionInvoiceStatus::OPEN,
            'period_start' => now(),
            'period_end' => now()->addMonth(),
            'amount_due' => 100,
            'amount_paid' => 0,
            'currency' => 'BRL',
        ]);

        Event::dispatch(new ChargePaidEvent($charge, 100.00));

        $this->assertSame(CustomerSubscriptionStatus::PENDING, $subscription->refresh()->status);
        $this->assertDatabaseHas('subscription_invoices', [
            'customer_subscription_id' => $subscription->id,
            'status' => SubscriptionInvoiceStatus::OPEN->value,
        ]);
    }

    public function test_charge_without_subscription_invoice_is_ignored(): void
    {
        Queue::fake();
        $charge = $this->chargeFor(User::factory()->create());

        Event::dispatch(new ChargePaidEvent($charge, 100.00));
        $charge->update(['status' => ChargeStatus::EXPIRED]);

        $this->assertSame(0, SubscriptionInvoice::count());
    }

    private function subscriptionInvoiceAndCharge(
        CustomerSubscriptionStatus $subscriptionStatus = CustomerSubscriptionStatus::PENDING
    ): array {
        $merchant = User::factory()->create();
        $subscription = $this->subscriptionFor($merchant, $subscriptionStatus);
        $charge = $this->chargeFor($merchant);

        $invoice = SubscriptionInvoice::create([
            'uuid' => (string) Str::uuid(),
            'customer_subscription_id' => $subscription->id,
            'user_id' => $merchant->id,
            'charge_id' => $charge->id,
            'status' => SubscriptionInvoiceStatus::OPEN,
            'period_start' => $subscription->current_period_start,
            'period_end' => $subscription->current_period_end,
            'amount_due' => 100,
            'amount_paid' => 0,
            'currency' => 'BRL',
        ]);

        return [$subscription, $invoice, $charge];
    }

    private function subscriptionFor(
        User $merchant,
        CustomerSubscriptionStatus $status = CustomerSubscriptionStatus::PENDING
    ): CustomerSubscription {
        return CustomerSubscription::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $merchant->id,
            'customer_name' => 'Cliente Recorrente',
            'customer_email' => 'cliente@example.com',
            'status' => $status,
            'amount' => 100,
            'currency' => 'BRL',
            'payment_method' => 'pix',
            'interval' => SubscriptionInterval::MONTH,
            'interval_count' => 1,
            'start_at' => '2026-01-01 00:00:00',
            'current_period_start' => '2026-01-01 00:00:00',
            'current_period_end' => '2026-02-01 00:00:00',
            'next_billing_at' => '2026-02-01 00:00:00',
            'metadata' => [],
        ]);
    }

    private function chargeFor(User $merchant): Charge
    {
        return Charge::create([
            'uuid' => (string) Str::uuid(),
            'correlation_id' => (string) Str::uuid(),
            'idempotency_key' => (string) Str::uuid(),
            'user_id' => $merchant->id,
            'payment_method' => 'pix',
            'amount' => 100,
            'platform_fee' => 1,
            'gateway_fee' => 0,
            'net_amount' => 99,
            'status' => ChargeStatus::WAITING_PAYMENT,
            'expires_at' => now()->addDay(),
        ]);
    }
}
