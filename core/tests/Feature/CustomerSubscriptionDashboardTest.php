<?php

namespace Tests\Feature;

use App\Enums\CustomerSubscriptionStatus;
use App\Enums\SubscriptionInterval;
use App\Enums\ChargeStatus;
use App\Enums\SubscriptionInvoiceStatus;
use App\Http\Middleware\BlockIp;
use App\Http\Middleware\CheckTransactionPassword;
use App\Http\Middleware\CheckUserStatus;
use App\Http\Middleware\EnsureTwoFactorAuthenticated;
use App\Models\Charge;
use App\Models\CustomerSubscriptionItem;
use App\Models\CustomerSubscription;
use App\Models\SubscriptionInvoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CustomerSubscriptionDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_only_authenticated_merchants_subscriptions(): void
    {
        [$merchantA, $merchantB] = [User::factory()->create(['email_verified_at' => now()]), User::factory()->create(['email_verified_at' => now()])];
        $visible = $this->subscriptionFor($merchantA, ['customer_name' => 'Cliente Visivel']);
        $this->subscriptionFor($merchantB, ['customer_name' => 'Cliente Oculto']);

        $response = $this->withoutMiddleware($this->dashboardOnlyMiddleware())
            ->actingAs($merchantA)
            ->get(route('user.subscriptions.index'));

        $response->assertOk();
        $response->assertSee('Cliente Visivel');
        $response->assertSee($visible->uuid);
        $response->assertDontSee('Cliente Oculto');
    }

    public function test_show_blocks_another_merchants_subscription(): void
    {
        $merchantA = User::factory()->create(['email_verified_at' => now()]);
        $merchantB = User::factory()->create(['email_verified_at' => now()]);
        $subscription = $this->subscriptionFor($merchantB);

        $this->withoutMiddleware($this->dashboardOnlyMiddleware())
            ->actingAs($merchantA)
            ->get(route('user.subscriptions.show', $subscription->uuid))
            ->assertNotFound();
    }

    public function test_immediate_cancel_works(): void
    {
        $merchant = User::factory()->create(['email_verified_at' => now()]);
        $subscription = $this->subscriptionFor($merchant);

        $this->withoutMiddleware($this->dashboardOnlyMiddleware())
            ->actingAs($merchant)
            ->post(route('user.subscriptions.cancel', $subscription->uuid))
            ->assertRedirect();

        $subscription->refresh();
        $this->assertSame(CustomerSubscriptionStatus::CANCELED, $subscription->status);
        $this->assertNotNull($subscription->canceled_at);
    }

    public function test_cancel_at_period_end_works(): void
    {
        $merchant = User::factory()->create(['email_verified_at' => now()]);
        $subscription = $this->subscriptionFor($merchant);

        $this->withoutMiddleware($this->dashboardOnlyMiddleware())
            ->actingAs($merchant)
            ->post(route('user.subscriptions.cancel', $subscription->uuid), [
                'cancel_at_period_end' => true,
            ])
            ->assertRedirect();

        $subscription->refresh();
        $this->assertSame(CustomerSubscriptionStatus::ACTIVE, $subscription->status);
        $this->assertTrue($subscription->cancel_at_period_end);
        $this->assertNull($subscription->canceled_at);
    }

    public function test_filters_work(): void
    {
        $merchant = User::factory()->create(['email_verified_at' => now()]);
        $visible = $this->subscriptionFor($merchant, [
            'customer_name' => 'Cliente Pix Ativo',
            'customer_email' => 'ativo@example.com',
            'status' => CustomerSubscriptionStatus::ACTIVE,
            'payment_method' => 'pix',
            'next_billing_at' => '2026-07-10 00:00:00',
        ]);
        $this->itemFor($visible, ['description' => 'Plano Diamante']);
        $this->subscriptionFor($merchant, [
            'customer_name' => 'Cliente Cartao Pendente',
            'customer_email' => 'pendente@example.com',
            'status' => CustomerSubscriptionStatus::PENDING,
            'payment_method' => 'card',
            'next_billing_at' => '2026-08-10 00:00:00',
        ]);

        $response = $this->withoutMiddleware($this->dashboardOnlyMiddleware())
            ->actingAs($merchant)
            ->get(route('user.subscriptions.index', [
                'search' => 'ativo@example.com',
                'status' => CustomerSubscriptionStatus::ACTIVE->value,
                'payment_method' => 'pix',
                'date_from' => '2026-07-01',
                'date_to' => '2026-07-31',
            ]));

        $response->assertOk();
        $response->assertSee('Cliente Pix Ativo');
        $response->assertSee('Plano Diamante');
        $response->assertDontSee('Cliente Cartao Pendente');
    }

    public function test_kpis_are_calculated_from_real_customer_subscriptions(): void
    {
        $merchant = User::factory()->create(['email_verified_at' => now()]);
        $otherMerchant = User::factory()->create(['email_verified_at' => now()]);

        $this->subscriptionFor($merchant, [
            'status' => CustomerSubscriptionStatus::ACTIVE,
            'amount' => 100,
            'next_billing_at' => now()->toDateTimeString(),
        ]);
        $this->subscriptionFor($merchant, [
            'status' => CustomerSubscriptionStatus::ACTIVE,
            'amount' => 300,
            'next_billing_at' => now()->addDay()->toDateTimeString(),
        ]);
        $this->subscriptionFor($merchant, [
            'status' => CustomerSubscriptionStatus::PAST_DUE,
            'amount' => 200,
            'next_billing_at' => now()->toDateTimeString(),
        ]);
        $this->subscriptionFor($merchant, [
            'status' => CustomerSubscriptionStatus::CANCELED,
            'amount' => 999,
            'next_billing_at' => now()->toDateTimeString(),
        ]);
        $this->subscriptionFor($otherMerchant, [
            'status' => CustomerSubscriptionStatus::ACTIVE,
            'amount' => 900,
            'next_billing_at' => now()->toDateTimeString(),
        ]);

        $response = $this->withoutMiddleware($this->dashboardOnlyMiddleware())
            ->actingAs($merchant)
            ->get(route('user.subscriptions.index'));

        $response->assertOk();
        $response->assertViewHas('metrics', function (array $metrics) {
            return (float) $metrics['mrr'] === 600.0
                && (int) $metrics['active'] === 2
                && (int) $metrics['renewals_today'] === 2
                && (int) $metrics['past_due'] === 1
                && (int) $metrics['canceled'] === 1
                && (float) $metrics['average_ticket'] === 200.0;
        });
    }

    public function test_empty_state_uses_real_developer_documentation_links(): void
    {
        $merchant = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->withoutMiddleware($this->dashboardOnlyMiddleware())
            ->actingAs($merchant)
            ->get(route('user.subscriptions.index'));

        $response->assertOk();
        $response->assertSee('Nenhuma assinatura encontrada');
        $response->assertSee(route('user.developer.docs.index'), false);
        $response->assertSee(route('user.developer.api-keys.index'), false);
        $response->assertDontSee('href="#" class="v2-btn-primary"', false);
        $response->assertDontSee('href="#" class="v2-btn-secondary"', false);
    }

    public function test_pagination_preserves_filters_and_orders_latest_first(): void
    {
        $merchant = User::factory()->create(['email_verified_at' => now()]);

        for ($i = 1; $i <= 12; $i++) {
            $this->subscriptionFor($merchant, [
                'customer_name' => "Batch Cliente {$i}",
                'customer_email' => "batch{$i}@example.com",
                'status' => CustomerSubscriptionStatus::ACTIVE,
                'payment_method' => 'pix',
                'next_billing_at' => '2026-07-10 00:00:00',
                'created_at' => now()->addMinutes($i),
                'updated_at' => now()->addMinutes($i),
            ]);
        }

        $response = $this->withoutMiddleware($this->dashboardOnlyMiddleware())
            ->actingAs($merchant)
            ->get(route('user.subscriptions.index', [
                'search' => 'Batch',
                'status' => CustomerSubscriptionStatus::ACTIVE->value,
                'payment_method' => 'pix',
                'date_from' => '2026-07-01',
                'date_to' => '2026-07-31',
            ]));

        $response->assertOk();
        $response->assertSeeInOrder(['Batch Cliente 12', 'Batch Cliente 11']);
        $response->assertSee('page=2', false);
        $response->assertSee('search=Batch', false);
        $response->assertSee('status=active', false);
        $response->assertSee('payment_method=pix', false);
        $response->assertSee('date_from=2026-07-01', false);
        $response->assertSee('date_to=2026-07-31', false);
    }

    public function test_show_displays_invoice_and_charge_operational_details(): void
    {
        $merchant = User::factory()->create(['email_verified_at' => now()]);
        $subscription = $this->subscriptionFor($merchant);
        $this->itemFor($subscription, ['description' => 'Plano Operacional']);
        $charge = Charge::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $merchant->id,
            'payment_method' => 'pix',
            'amount' => 100,
            'platform_fee' => 1,
            'gateway_fee' => 0,
            'net_amount' => 99,
            'description' => 'Assinatura mensal',
            'customer_name' => 'Cliente Recorrente',
            'customer_email' => 'cliente@example.com',
            'customer_document' => '12345678909',
            'status' => ChargeStatus::PAID,
            'paid_at' => '2026-06-15 10:00:00',
        ]);
        $invoice = SubscriptionInvoice::create([
            'uuid' => (string) Str::uuid(),
            'customer_subscription_id' => $subscription->id,
            'user_id' => $merchant->id,
            'charge_id' => $charge->id,
            'status' => SubscriptionInvoiceStatus::PAID,
            'period_start' => '2026-06-01 00:00:00',
            'period_end' => '2026-07-01 00:00:00',
            'amount_due' => 100,
            'amount_paid' => 100,
            'currency' => 'BRL',
            'paid_at' => '2026-06-15 10:00:00',
        ]);

        $response = $this->withoutMiddleware($this->dashboardOnlyMiddleware())
            ->actingAs($merchant)
            ->get(route('user.subscriptions.show', $subscription->uuid));

        $response->assertOk();
        $response->assertSee('Plano Operacional');
        $response->assertSee($invoice->uuid);
        $response->assertSee($charge->uuid);
        $response->assertSee('Ultimo pagamento');
        $response->assertSee('Ultima tentativa');
    }

    public function test_menu_points_to_real_subscriptions_route(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $response = $this->withoutMiddleware($this->dashboardOnlyMiddleware())
            ->actingAs($user)
            ->get(route('user.subscriptions.index'));

        $response->assertOk();
        $response->assertSee(route('user.subscriptions.index'), false);
        $response->assertSee('Assinaturas');
    }

    private function dashboardOnlyMiddleware(): array
    {
        return [
            CheckUserStatus::class,
            EnsureTwoFactorAuthenticated::class,
            BlockIp::class,
            CheckTransactionPassword::class,
        ];
    }

    private function subscriptionFor(User $merchant, array $overrides = []): CustomerSubscription
    {
        return CustomerSubscription::create(array_merge([
            'uuid' => (string) Str::uuid(),
            'user_id' => $merchant->id,
            'customer_name' => 'Cliente Recorrente',
            'customer_email' => 'cliente@example.com',
            'customer_document' => '12345678909',
            'status' => CustomerSubscriptionStatus::ACTIVE,
            'amount' => 100,
            'currency' => 'BRL',
            'payment_method' => 'pix',
            'interval' => SubscriptionInterval::MONTH,
            'interval_count' => 1,
            'description' => 'Assinatura mensal',
            'start_at' => '2026-01-01 00:00:00',
            'current_period_start' => '2026-06-01 00:00:00',
            'current_period_end' => '2026-07-01 00:00:00',
            'next_billing_at' => '2026-07-01 00:00:00',
            'metadata' => [],
        ], $overrides));
    }

    private function itemFor(CustomerSubscription $subscription, array $overrides = []): CustomerSubscriptionItem
    {
        return CustomerSubscriptionItem::create(array_merge([
            'customer_subscription_id' => $subscription->id,
            'description' => 'Plano mensal',
            'quantity' => 1,
            'unit_amount' => $subscription->amount,
            'total_amount' => $subscription->amount,
            'metadata' => [],
        ], $overrides));
    }
}
