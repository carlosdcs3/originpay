<?php

namespace Tests\Feature;

use App\Enums\ChargeStatus;
use App\Enums\CustomerSubscriptionStatus;
use App\Enums\SubscriptionInterval;
use App\Enums\SubscriptionInvoiceStatus;
use App\Events\ChargePaidEvent;
use App\Models\ApiKey;
use App\Models\Charge;
use App\Models\CustomerSubscription;
use App\Models\SubscriptionInvoice;
use App\Models\User;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use App\Services\ChargeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class CustomerSubscriptionWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_is_sent_when_subscription_is_created(): void
    {
        Http::fake(['https://merchant.test/webhook' => Http::response(['ok' => true], 200)]);
        [$merchant, $headers] = $this->merchantHeaders();
        $this->webhookEndpointFor($merchant, ['*']);
        $this->fakeChargeService();

        $response = $this->postJson('/api/v1/customer-subscriptions', $this->payload(), $headers);

        $response->assertCreated();
        $created = WebhookDelivery::where('event_type', 'customer_subscription.created')->firstOrFail();
        $invoiceCreated = WebhookDelivery::where('event_type', 'subscription_invoice.created')->firstOrFail();

        $this->assertSame($response->json('id'), $created->payload['data']['subscription']['id']);
        $this->assertSame($response->json('latest_invoice.id'), $created->payload['data']['invoice']['id']);
        $this->assertSame($response->json('latest_invoice.charge_uuid'), $created->payload['data']['charge']['id']);
        $this->assertSame('cliente@example.com', $created->payload['data']['customer']['email']);
        $this->assertSame($response->json('latest_invoice.id'), $invoiceCreated->payload['data']['invoice']['id']);
    }

    public function test_webhook_is_sent_when_invoice_is_paid(): void
    {
        Queue::fake();
        Http::fake(['https://merchant.test/webhook' => Http::response(['ok' => true], 200)]);
        [$subscription, $invoice, $charge] = $this->subscriptionInvoiceAndCharge();
        $this->webhookEndpointFor($subscription->user, ['subscription_invoice.paid', 'customer_subscription.activated']);

        Event::dispatch(new ChargePaidEvent($charge, 100.00));

        $paid = WebhookDelivery::where('event_type', 'subscription_invoice.paid')->firstOrFail();
        $activated = WebhookDelivery::where('event_type', 'customer_subscription.activated')->firstOrFail();

        $this->assertSame($subscription->uuid, $paid->payload['data']['subscription']['id']);
        $this->assertSame($invoice->uuid, $paid->payload['data']['invoice']['id']);
        $this->assertSame($charge->uuid, $paid->payload['data']['charge']['id']);
        $this->assertSame('active', $activated->payload['data']['subscription']['status']);
    }

    public function test_webhook_is_sent_when_invoice_fails(): void
    {
        Http::fake(['https://merchant.test/webhook' => Http::response(['ok' => true], 200)]);
        [$subscription, $invoice, $charge] = $this->subscriptionInvoiceAndCharge(CustomerSubscriptionStatus::ACTIVE);
        $this->webhookEndpointFor($subscription->user, ['subscription_invoice.failed', 'customer_subscription.past_due']);

        $charge->update(['status' => ChargeStatus::EXPIRED]);

        $failed = WebhookDelivery::where('event_type', 'subscription_invoice.failed')->firstOrFail();
        $pastDue = WebhookDelivery::where('event_type', 'customer_subscription.past_due')->firstOrFail();

        $this->assertSame($invoice->uuid, $failed->payload['data']['invoice']['id']);
        $this->assertSame('failed', $failed->payload['data']['invoice']['status']);
        $this->assertSame('past_due', $pastDue->payload['data']['subscription']['status']);
    }

    public function test_webhook_is_sent_when_subscription_is_canceled(): void
    {
        Http::fake(['https://merchant.test/webhook' => Http::response(['ok' => true], 200)]);
        [$merchant, $headers] = $this->merchantHeaders();
        $subscription = $this->subscriptionFor($merchant);
        $this->webhookEndpointFor($merchant, ['customer_subscription.canceled']);

        $response = $this->postJson('/api/v1/customer-subscriptions/' . $subscription->uuid . '/cancel', [], $headers);

        $response->assertOk();
        $delivery = WebhookDelivery::where('event_type', 'customer_subscription.canceled')->firstOrFail();
        $this->assertSame($subscription->uuid, $delivery->payload['data']['subscription']['id']);
        $this->assertSame('canceled', $delivery->payload['data']['subscription']['status']);
    }

    public function test_duplicate_events_do_not_duplicate_webhook_delivery(): void
    {
        Queue::fake();
        Http::fake(['https://merchant.test/webhook' => Http::response(['ok' => true], 200)]);
        [$subscription, $invoice, $charge] = $this->subscriptionInvoiceAndCharge();
        $this->webhookEndpointFor($subscription->user, ['subscription_invoice.paid']);

        Event::dispatch(new ChargePaidEvent($charge, 100.00));
        Event::dispatch(new ChargePaidEvent($charge, 100.00));

        $this->assertSame(1, WebhookDelivery::where('event_type', 'subscription_invoice.paid')->count());
        $this->assertSame(1, WebhookDelivery::where('idempotency_key', 'customer_subscription:subscription_invoice.paid:' . $subscription->id . ':' . $invoice->id)->count());
    }

    public function test_payload_does_not_leak_data_from_another_merchant(): void
    {
        Queue::fake();
        Http::fake([
            'https://merchant-a.test/webhook' => Http::response(['ok' => true], 200),
            'https://merchant-b.test/webhook' => Http::response(['ok' => true], 200),
        ]);
        [$subscriptionA, $invoiceA, $chargeA] = $this->subscriptionInvoiceAndCharge();
        $merchantB = User::factory()->create(['email' => 'merchant-b@example.com']);
        $this->webhookEndpointFor($subscriptionA->user, ['subscription_invoice.paid'], 'https://merchant-a.test/webhook');
        $this->webhookEndpointFor($merchantB, ['subscription_invoice.paid'], 'https://merchant-b.test/webhook');

        Event::dispatch(new ChargePaidEvent($chargeA, 100.00));

        $this->assertSame(1, WebhookDelivery::count());
        $delivery = WebhookDelivery::firstOrFail();
        $this->assertSame($invoiceA->uuid, $delivery->payload['data']['invoice']['id']);
        $this->assertSame('cliente@example.com', $delivery->payload['data']['customer']['email']);
        $this->assertStringNotContainsString('merchant-b@example.com', json_encode($delivery->payload));
        $this->assertSame($subscriptionA->user->webhookEndpoints()->first()->id, $delivery->webhook_endpoint_id);
    }

    private function merchantHeaders(): array
    {
        $merchant = User::factory()->create();
        $plain = 'sk_test_' . Str::random(32);
        ApiKey::factory()->for($merchant, 'user')->forPlainKey($plain)->create();

        return [$merchant, [
            'Authorization' => 'Bearer ' . $plain,
            'Accept' => 'application/json',
        ]];
    }

    private function webhookEndpointFor(User $merchant, array $events, string $url = 'https://merchant.test/webhook'): WebhookEndpoint
    {
        return WebhookEndpoint::create([
            'user_id' => $merchant->id,
            'url' => $url,
            'secret' => 'whsec_' . Str::random(32),
            'events' => $events,
            'environment' => 'live',
            'status' => true,
        ]);
    }

    private function fakeChargeService(): void
    {
        $mock = Mockery::mock(ChargeService::class);
        $mock->shouldReceive('create')
            ->once()
            ->andReturnUsing(function (User $user, float $amount, string $paymentMethod, array $customerData) {
                return $this->chargeFor($user, [
                    'payment_method' => $paymentMethod,
                    'amount' => $amount,
                    'net_amount' => $amount,
                    'idempotency_key' => $customerData['idempotency_key'] ?? Str::uuid()->toString(),
                    'customer_name' => $customerData['name'] ?? null,
                    'customer_email' => $customerData['email'] ?? null,
                    'customer_document' => $customerData['document'] ?? null,
                ]);
            });

        app()->instance(ChargeService::class, $mock);
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
            'customer_document' => '12345678909',
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
            'metadata' => ['external_reference' => 'sub_ext_123'],
        ]);
    }

    private function chargeFor(User $merchant, array $overrides = []): Charge
    {
        return Charge::create(array_merge([
            'uuid' => (string) Str::uuid(),
            'correlation_id' => (string) Str::uuid(),
            'idempotency_key' => (string) Str::uuid(),
            'user_id' => $merchant->id,
            'payment_method' => 'pix',
            'amount' => 100,
            'platform_fee' => 0,
            'gateway_fee' => 0,
            'net_amount' => 100,
            'customer_name' => 'Cliente Recorrente',
            'customer_email' => 'cliente@example.com',
            'customer_document' => '12345678909',
            'status' => ChargeStatus::WAITING_PAYMENT,
            'expires_at' => now()->addDays(3),
        ], $overrides));
    }

    private function payload(): array
    {
        return [
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
        ];
    }
}
