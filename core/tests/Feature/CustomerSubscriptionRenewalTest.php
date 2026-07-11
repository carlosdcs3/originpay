<?php

namespace Tests\Feature;

use App\Enums\ChargeStatus;
use App\Enums\CustomerSubscriptionStatus;
use App\Enums\SubscriptionInterval;
use App\Enums\SubscriptionInvoiceStatus;
use App\Events\ChargePaidEvent;
use App\Models\Charge;
use App\Models\CustomerSubscription;
use App\Models\User;
use App\Services\ChargeService;
use Carbon\Carbon;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class CustomerSubscriptionRenewalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-02-01 00:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_active_due_subscription_generates_next_invoice_and_charge(): void
    {
        $subscription = $this->subscriptionDue();
        $this->fakeChargeService(expectedCalls: 1);

        $this->artisan('subscriptions:renew')
            ->assertSuccessful();

        $invoice = $subscription->invoices()->firstOrFail();

        $this->assertSame(1, $subscription->invoices()->count());
        $this->assertSame(1, Charge::count());
        $this->assertNotNull($invoice->charge_id);
        $this->assertSame(SubscriptionInvoiceStatus::OPEN, $invoice->status);
        $this->assertSame('2026-02-01', $subscription->refresh()->current_period_start->toDateString());
        $this->assertSame('2026-03-01', $subscription->current_period_end->toDateString());
        $this->assertSame('2026-03-01', $subscription->next_billing_at->toDateString());
        $this->assertSame(CustomerSubscriptionStatus::ACTIVE, $subscription->status);
    }

    public function test_running_renewal_twice_does_not_duplicate_invoice_or_charge(): void
    {
        $subscription = $this->subscriptionDue();
        $this->fakeChargeService(expectedCalls: 1);

        $this->artisan('subscriptions:renew')->assertSuccessful();
        $this->artisan('subscriptions:renew')->assertSuccessful();

        $this->assertSame(1, $subscription->invoices()->count());
        $this->assertSame(1, Charge::count());
    }

    public function test_subscription_lock_prevents_duplicate_processing(): void
    {
        $subscription = $this->subscriptionDue();
        $this->fakeChargeService(expectedCalls: 0);

        $lock = Cache::lock("customer_subscription:renew:{$subscription->id}", 300);
        $this->assertTrue($lock->get());

        try {
            $this->artisan('subscriptions:renew')
                ->assertSuccessful()
                ->expectsOutputToContain('Subscriptions ignoradas: 1');
        } finally {
            $lock->release();
        }

        $this->assertSame(0, $subscription->invoices()->count());
        $this->assertSame(0, Charge::count());
    }

    public function test_command_outputs_operational_summary(): void
    {
        $this->subscriptionDue();
        $this->fakeChargeService(expectedCalls: 1);

        $this->artisan('subscriptions:renew')
            ->assertSuccessful()
            ->expectsOutput('Customer subscriptions renewal completed.')
            ->expectsOutput('Subscriptions processadas: 1')
            ->expectsOutput('Invoices criadas: 1')
            ->expectsOutput('Charges criadas: 1')
            ->expectsOutput('Subscriptions canceladas: 0')
            ->expectsOutput('Subscriptions ignoradas: 0')
            ->expectsOutput('Falhas: 0');
    }

    public function test_canceled_subscription_is_ignored(): void
    {
        $subscription = $this->subscriptionDue(CustomerSubscriptionStatus::CANCELED, [
            'canceled_at' => now()->subDay(),
        ]);
        $this->fakeChargeService(expectedCalls: 0);

        $this->artisan('subscriptions:renew')->assertSuccessful();

        $this->assertSame(0, $subscription->invoices()->count());
        $this->assertSame(0, Charge::count());
    }

    public function test_cancel_at_period_end_cancels_subscription_when_period_is_due(): void
    {
        $subscription = $this->subscriptionDue(CustomerSubscriptionStatus::ACTIVE, [
            'cancel_at_period_end' => true,
        ]);
        $this->fakeChargeService(expectedCalls: 0);

        $this->artisan('subscriptions:renew')->assertSuccessful();

        $subscription->refresh();
        $this->assertSame(CustomerSubscriptionStatus::CANCELED, $subscription->status);
        $this->assertNotNull($subscription->canceled_at);
        $this->assertFalse($subscription->cancel_at_period_end);
        $this->assertSame(0, $subscription->invoices()->count());
    }

    public function test_charge_service_failure_marks_invoice_failed_and_subscription_past_due(): void
    {
        $subscription = $this->subscriptionDue();

        $mock = Mockery::mock(ChargeService::class);
        $mock->shouldReceive('create')->once()->andThrow(new Exception('Gateway timeout'));
        app()->instance(ChargeService::class, $mock);

        $this->artisan('subscriptions:renew')->assertSuccessful();

        $invoice = $subscription->invoices()->firstOrFail();
        $subscription->refresh();

        $this->assertSame(SubscriptionInvoiceStatus::FAILED, $invoice->status);
        $this->assertNotNull($invoice->failed_at);
        $this->assertStringContainsString('Gateway timeout', $invoice->last_error);
        $this->assertSame(CustomerSubscriptionStatus::PAST_DUE, $subscription->status);
        $this->assertStringContainsString('Gateway timeout', $subscription->last_error);
    }

    public function test_payment_after_renewal_uses_phase_two_listener(): void
    {
        Queue::fake();
        $subscription = $this->subscriptionDue();
        $this->fakeChargeService(expectedCalls: 1);

        $this->artisan('subscriptions:renew')->assertSuccessful();

        $invoice = $subscription->invoices()->firstOrFail();
        $charge = $invoice->charge;

        Event::dispatch(new ChargePaidEvent($charge, 100.00));

        $this->assertSame(SubscriptionInvoiceStatus::PAID, $invoice->refresh()->status);
        $this->assertSame('100.00', $invoice->amount_paid);
        $this->assertSame(CustomerSubscriptionStatus::ACTIVE, $subscription->refresh()->status);
    }

    public function test_tenant_isolation_preserves_each_subscription_owner(): void
    {
        $subscriptionA = $this->subscriptionDue();
        $subscriptionB = $this->subscriptionDue();
        $this->fakeChargeService(expectedCalls: 2);

        $this->artisan('subscriptions:renew')->assertSuccessful();

        $invoiceA = $subscriptionA->invoices()->firstOrFail();
        $invoiceB = $subscriptionB->invoices()->firstOrFail();

        $this->assertSame($subscriptionA->user_id, $invoiceA->user_id);
        $this->assertSame($subscriptionB->user_id, $invoiceB->user_id);
        $this->assertNotSame($invoiceA->user_id, $invoiceB->user_id);
        $this->assertSame($subscriptionA->user_id, $invoiceA->charge->user_id);
        $this->assertSame($subscriptionB->user_id, $invoiceB->charge->user_id);
    }

    private function subscriptionDue(
        CustomerSubscriptionStatus $status = CustomerSubscriptionStatus::ACTIVE,
        array $overrides = []
    ): CustomerSubscription {
        $merchant = User::factory()->create();
        $subscription = CustomerSubscription::create(array_merge([
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
            'description' => 'Assinatura mensal',
            'start_at' => '2026-01-01 00:00:00',
            'current_period_start' => '2026-01-01 00:00:00',
            'current_period_end' => '2026-02-01 00:00:00',
            'next_billing_at' => '2026-02-01 00:00:00',
            'metadata' => [],
        ], $overrides));

        $subscription->items()->create([
            'description' => 'Assinatura mensal',
            'quantity' => 1,
            'unit_amount' => 100,
            'total_amount' => 100,
            'metadata' => [],
        ]);

        return $subscription;
    }

    private function fakeChargeService(int $expectedCalls): void
    {
        $mock = Mockery::mock(ChargeService::class);
        $mock->shouldReceive('create')
            ->times($expectedCalls)
            ->andReturnUsing(function (User $user, float $amount, string $paymentMethod, array $customerData) {
                return Charge::create([
                    'uuid' => (string) Str::uuid(),
                    'correlation_id' => (string) Str::uuid(),
                    'idempotency_key' => $customerData['idempotency_key'] ?? Str::uuid()->toString(),
                    'user_id' => $user->id,
                    'payment_method' => $paymentMethod,
                    'amount' => $amount,
                    'platform_fee' => 0,
                    'gateway_fee' => 0,
                    'net_amount' => $amount,
                    'description' => $customerData['description'] ?? null,
                    'customer_name' => $customerData['name'] ?? null,
                    'customer_email' => $customerData['email'] ?? null,
                    'customer_document' => $customerData['document'] ?? null,
                    'status' => ChargeStatus::WAITING_PAYMENT,
                    'expires_at' => now()->addDays(3),
                ]);
            });

        app()->instance(ChargeService::class, $mock);
    }
}
