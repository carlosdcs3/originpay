<?php

namespace Tests\Feature;

use App\Enums\CustomerSubscriptionStatus;
use App\Enums\SubscriptionInterval;
use App\Enums\SubscriptionInvoiceStatus;
use App\Models\ApiKey;
use App\Models\Charge;
use App\Models\CustomerSubscription;
use App\Models\User;
use App\Services\ChargeService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class CustomerSubscriptionApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_merchant_creates_subscription_invoice_and_first_charge(): void
    {
        [$merchant, $headers] = $this->merchantHeaders();
        $this->fakeChargeService($merchant);

        $response = $this->postJson('/api/v1/customer-subscriptions', $this->payload(), $headers);

        $response->assertCreated()
            ->assertJsonPath('status', CustomerSubscriptionStatus::PENDING->value)
            ->assertJsonPath('latest_invoice.status', SubscriptionInvoiceStatus::OPEN->value);

        $subscription = CustomerSubscription::with('latestInvoice.charge')->firstOrFail();

        $this->assertSame($merchant->id, $subscription->user_id);
        $this->assertSame(CustomerSubscriptionStatus::PENDING, $subscription->status);
        $this->assertSame(SubscriptionInvoiceStatus::OPEN, $subscription->latestInvoice->status);
        $this->assertNotNull($subscription->latestInvoice->charge_id);
        $this->assertSame('100.00', $subscription->latestInvoice->amount_due);
        $this->assertSame('0.00', $subscription->latestInvoice->amount_paid);
        $this->assertSame('2026-02-28', $subscription->current_period_end->toDateString());
        $this->assertSame(1, $subscription->items()->count());
        $this->assertSame(1, $subscription->latestInvoice->items()->count());
    }

    public function test_charge_service_failure_marks_subscription_incomplete_and_invoice_failed(): void
    {
        [$merchant, $headers] = $this->merchantHeaders();

        $mock = Mockery::mock(ChargeService::class);
        $mock->shouldReceive('create')->once()->andThrow(new Exception('Gateway unavailable'));
        app()->instance(ChargeService::class, $mock);

        $response = $this->postJson('/api/v1/customer-subscriptions', $this->payload(), $headers);

        $response->assertCreated()
            ->assertJsonPath('status', CustomerSubscriptionStatus::INCOMPLETE->value)
            ->assertJsonPath('latest_invoice.status', SubscriptionInvoiceStatus::FAILED->value);

        $subscription = CustomerSubscription::with('latestInvoice')->firstOrFail();
        $this->assertStringContainsString('Gateway unavailable', $subscription->last_error);
        $this->assertStringContainsString('Gateway unavailable', $subscription->latestInvoice->last_error);
    }

    public function test_body_idempotency_key_returns_same_resource_for_same_merchant(): void
    {
        [$merchant, $headers] = $this->merchantHeaders();
        $this->fakeChargeService($merchant, expectedCalls: 1);
        $payload = $this->payload(['idempotency_key' => 'sub_idem_body_123']);

        $first = $this->postJson('/api/v1/customer-subscriptions', $payload, $headers);
        $second = $this->postJson('/api/v1/customer-subscriptions', $payload, $headers);

        $first->assertCreated();
        $second->assertOk();
        $this->assertSame($first->json('id'), $second->json('id'));
        $this->assertSame(1, CustomerSubscription::count());
        $this->assertSame(1, Charge::count());
    }

    public function test_idempotency_key_is_scoped_by_merchant(): void
    {
        [$merchantA, $headersA] = $this->merchantHeaders();
        [$merchantB, $headersB] = $this->merchantHeaders();
        $this->fakeChargeServiceForAnyMerchant(expectedCalls: 2);
        $payload = $this->payload(['idempotency_key' => 'sub_shared_123']);

        $first = $this->postJson('/api/v1/customer-subscriptions', $payload, $headersA);
        $second = $this->postJson('/api/v1/customer-subscriptions', $payload, $headersB);

        $first->assertCreated();
        $second->assertCreated();
        $this->assertNotSame($first->json('id'), $second->json('id'));
        $this->assertDatabaseHas('customer_subscriptions', [
            'user_id' => $merchantA->id,
            'idempotency_key' => 'sub_shared_123',
        ]);
        $this->assertDatabaseHas('customer_subscriptions', [
            'user_id' => $merchantB->id,
            'idempotency_key' => 'sub_shared_123',
        ]);
    }

    public function test_header_idempotency_key_has_priority_over_body_key(): void
    {
        [$merchant, $headers] = $this->merchantHeaders(['Idempotency-Key' => 'sub_idem_header_123']);
        $this->fakeChargeService($merchant, expectedCalls: 1);
        $payload = $this->payload(['idempotency_key' => 'sub_idem_body_ignored']);

        $response = $this->postJson('/api/v1/customer-subscriptions', $payload, $headers);

        $response->assertCreated();
        $this->assertDatabaseHas('customer_subscriptions', [
            'user_id' => $merchant->id,
            'idempotency_key' => 'sub_idem_header_123',
        ]);
    }

    public function test_merchant_a_cannot_access_merchant_b_subscription(): void
    {
        [$merchantA, $headersA] = $this->merchantHeaders();
        [$merchantB] = $this->merchantHeaders();
        $subscription = $this->subscriptionFor($merchantB);

        $this->getJson('/api/v1/customer-subscriptions/' . $subscription->uuid, $headersA)
            ->assertNotFound();

        $this->postJson('/api/v1/customer-subscriptions/' . $subscription->uuid . '/cancel', [], $headersA)
            ->assertNotFound();
    }

    public function test_cancel_at_period_end_does_not_cancel_immediately(): void
    {
        [$merchant, $headers] = $this->merchantHeaders();
        $subscription = $this->subscriptionFor($merchant);

        $response = $this->postJson('/api/v1/customer-subscriptions/' . $subscription->uuid . '/cancel', [
            'cancel_at_period_end' => true,
        ], $headers);

        $response->assertOk()
            ->assertJsonPath('status', CustomerSubscriptionStatus::PENDING->value)
            ->assertJsonPath('cancel_at_period_end', true);

        $subscription->refresh();
        $this->assertSame(CustomerSubscriptionStatus::PENDING, $subscription->status);
        $this->assertTrue($subscription->cancel_at_period_end);
        $this->assertNull($subscription->canceled_at);
    }

    public function test_immediate_cancel_marks_subscription_canceled(): void
    {
        [$merchant, $headers] = $this->merchantHeaders();
        $subscription = $this->subscriptionFor($merchant);

        $this->postJson('/api/v1/customer-subscriptions/' . $subscription->uuid . '/cancel', [
            'cancel_at_period_end' => false,
        ], $headers)
            ->assertOk()
            ->assertJsonPath('status', CustomerSubscriptionStatus::CANCELED->value);

        $this->assertSame(CustomerSubscriptionStatus::CANCELED, $subscription->refresh()->status);
        $this->assertNotNull($subscription->canceled_at);
    }

    public function test_list_only_returns_authenticated_merchants_subscriptions(): void
    {
        [$merchantA, $headersA] = $this->merchantHeaders();
        [$merchantB] = $this->merchantHeaders();
        $this->subscriptionFor($merchantA);
        $this->subscriptionFor($merchantB);

        $response = $this->getJson('/api/v1/customer-subscriptions', $headersA);

        $response->assertOk()->assertJsonPath('meta.total', 1);
    }

    private function merchantHeaders(array $extraHeaders = []): array
    {
        $merchant = User::factory()->create();
        $plain = 'sk_test_' . Str::random(32);
        ApiKey::factory()->for($merchant, 'user')->forPlainKey($plain)->create();

        return [$merchant, array_merge([
            'Authorization' => 'Bearer ' . $plain,
            'Accept' => 'application/json',
        ], $extraHeaders)];
    }

    private function fakeChargeService(User $merchant, int $expectedCalls = 1): void
    {
        $mock = Mockery::mock(ChargeService::class);
        $mock->shouldReceive('create')
            ->times($expectedCalls)
            ->andReturnUsing(function (User $user, float $amount, string $paymentMethod, array $customerData) use ($merchant) {
                $this->assertSame($merchant->id, $user->id);

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
                    'status' => 'waiting_payment',
                    'expires_at' => now()->addDays(3),
                ]);
            });

        app()->instance(ChargeService::class, $mock);
    }

    private function fakeChargeServiceForAnyMerchant(int $expectedCalls = 1): void
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
                    'status' => 'waiting_payment',
                    'expires_at' => now()->addDays(3),
                ]);
            });

        app()->instance(ChargeService::class, $mock);
    }

    private function subscriptionFor(User $merchant): CustomerSubscription
    {
        $subscription = CustomerSubscription::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $merchant->id,
            'customer_name' => 'Customer',
            'customer_email' => 'customer@example.com',
            'status' => CustomerSubscriptionStatus::PENDING,
            'amount' => 100.00,
            'currency' => 'BRL',
            'payment_method' => 'pix',
            'interval' => SubscriptionInterval::MONTH,
            'interval_count' => 1,
            'start_at' => now(),
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
            'next_billing_at' => now()->addMonth(),
            'metadata' => [],
        ]);

        $subscription->invoices()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $merchant->id,
            'status' => SubscriptionInvoiceStatus::OPEN,
            'period_start' => $subscription->current_period_start,
            'period_end' => $subscription->current_period_end,
            'amount_due' => $subscription->amount,
            'amount_paid' => 0,
            'currency' => 'BRL',
        ]);

        return $subscription->refresh();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'customer' => [
                'name' => 'Cliente Recorrente',
                'email' => 'cliente@example.com',
                'document' => '12345678909',
            ],
            'amount' => 100.00,
            'currency' => 'BRL',
            'payment_method' => 'pix',
            'interval' => 'month',
            'interval_count' => 1,
            'description' => 'Assinatura mensal',
            'start_at' => '2026-01-31T10:00:00-03:00',
            'metadata' => ['external_reference' => 'sub_ext_123'],
        ], $overrides);
    }
}
