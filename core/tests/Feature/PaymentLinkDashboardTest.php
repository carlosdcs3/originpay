<?php

namespace Tests\Feature;

use App\Enums\ChargeStatus;
use App\Enums\CustomerSubscriptionStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentOperation;
use App\Http\Middleware\BlockIp;
use App\Http\Middleware\CheckTransactionPassword;
use App\Http\Middleware\CheckUserStatus;
use App\Http\Middleware\EnsureTwoFactorAuthenticated;
use App\Models\Charge;
use App\Models\CustomerSubscription;
use App\Models\PaymentMethodRoute;
use App\Models\PaymentLink;
use App\Models\User;
use App\Services\ChargeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class PaymentLinkDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([PaymentOperation::PIX_CHARGE, PaymentOperation::CARD_CREDIT, PaymentOperation::BOLETO] as $operation) {
            PaymentMethodRoute::updateOrCreate(
                ['payment_operation' => $operation->value],
                [
                    'payment_method' => $operation->paymentMethod()->value,
                    'enabled' => true,
                ]
            );
        }
    }

    public function test_charge_index_uses_choice_modal_for_new_charge_flow(): void
    {
        $merchant = User::factory()->create(['email_verified_at' => now()]);

        $this->withoutMiddleware($this->dashboardOnlyMiddleware())
            ->actingAs($merchant)
            ->get(route('user.charge.index'))
            ->assertOk()
            ->assertSee('Como deseja cobrar?')
            ->assertSee('Cobrança manual')
            ->assertSee('Link de pagamento')
            ->assertSee(route('user.charge.create'), false)
            ->assertSee(route('user.payment-links.create'), false)
            ->assertDontSee('Nova Cobrança Pix');
    }

    public function test_charge_index_filters_all_payment_methods_in_single_charges_page(): void
    {
        $merchant = User::factory()->create(['email_verified_at' => now()]);

        Charge::factory()->create([
            'user_id' => $merchant->id,
            'payment_method' => PaymentMethod::PIX,
            'status' => ChargeStatus::PAID,
            'customer_name' => 'Cliente Pix Hub',
            'net_amount' => 100,
        ]);
        Charge::factory()->create([
            'user_id' => $merchant->id,
            'payment_method' => PaymentMethod::CARD,
            'status' => ChargeStatus::WAITING_PAYMENT,
            'customer_name' => 'Cliente Cartao Hub',
        ]);
        Charge::factory()->create([
            'user_id' => $merchant->id,
            'payment_method' => PaymentMethod::BOLETO,
            'status' => ChargeStatus::PAID,
            'customer_name' => 'Cliente Boleto Hub',
            'net_amount' => 250,
        ]);

        $this->withoutMiddleware($this->dashboardOnlyMiddleware())
            ->actingAs($merchant)
            ->get(route('user.charge.index', ['method' => 'boleto']))
            ->assertOk()
            ->assertSee('Cobranças')
            ->assertSee('Recebidos Boleto')
            ->assertSee('Cobranças Boleto')
            ->assertSee('Cliente Boleto Hub')
            ->assertDontSee('Cliente Pix Hub')
            ->assertDontSee('Cliente Cartao Hub');
    }

    public function test_charge_index_searches_by_operational_references(): void
    {
        $merchant = User::factory()->create(['email_verified_at' => now()]);

        Charge::factory()->create([
            'user_id' => $merchant->id,
            'payment_method' => PaymentMethod::PIX,
            'customer_name' => 'Cliente Referencia Visivel',
            'gateway_charge_id' => 'gw-search-123',
            'gateway_reference' => 'ref-search-456',
            'payment_link' => 'https://checkout.local/pay/search-link',
            'description' => 'Descricao pesquisavel',
        ]);
        Charge::factory()->create([
            'user_id' => $merchant->id,
            'payment_method' => PaymentMethod::PIX,
            'customer_name' => 'Cliente Oculto',
            'description' => 'Outro pedido',
        ]);

        foreach (['gw-search-123', 'ref-search-456', 'search-link', 'Descricao pesquisavel'] as $term) {
            $this->withoutMiddleware($this->dashboardOnlyMiddleware())
                ->actingAs($merchant)
                ->get(route('user.charge.index', ['search' => $term]))
                ->assertOk()
                ->assertSee('Cliente Referencia Visivel')
                ->assertDontSee('Cliente Oculto');
        }
    }

    public function test_legacy_pix_and_card_urls_redirect_to_charges_with_method_filter(): void
    {
        $merchant = User::factory()->create(['email_verified_at' => now()]);

        $this->withoutMiddleware($this->dashboardOnlyMiddleware())
            ->actingAs($merchant)
            ->get(route('user.pix.redirect'))
            ->assertRedirect(route('user.charge.index', ['method' => 'pix']));

        $this->withoutMiddleware($this->dashboardOnlyMiddleware())
            ->actingAs($merchant)
            ->get(route('user.card.redirect'))
            ->assertRedirect(route('user.charge.index', ['method' => 'card']));
    }

    public function test_english_subscriptions_url_redirects_to_customer_subscriptions_dashboard(): void
    {
        $merchant = User::factory()->create(['email_verified_at' => now()]);

        $this->withoutMiddleware($this->dashboardOnlyMiddleware())
            ->actingAs($merchant)
            ->get(route('user.subscriptions.redirect'))
            ->assertRedirect(route('user.subscriptions.index'));
    }

    public function test_manual_charge_and_payment_link_create_pages_render_v2_flow_copy(): void
    {
        $merchant = User::factory()->create(['email_verified_at' => now()]);

        $this->withoutMiddleware($this->dashboardOnlyMiddleware())
            ->actingAs($merchant)
            ->get(route('user.charge.create'))
            ->assertOk()
            ->assertSee('Pagamentos')
            ->assertSee('Cobranças')
            ->assertSee('Nova cobrança')
            ->assertSee(route('user.charge.index'), false);

        $this->withoutMiddleware($this->dashboardOnlyMiddleware())
            ->actingAs($merchant)
            ->get(route('user.payment-links.create'))
            ->assertOk()
            ->assertSee('Links de pagamento')
            ->assertSee('Criar Link de Pagamento')
            ->assertSee('Nome do produto', false)
            ->assertSee('allowed_payment_methods', false);
    }

    public function test_dashboard_creates_payment_link_intent_without_charge(): void
    {
        $merchant = User::factory()->create(['email_verified_at' => now()]);

        $this->withoutMiddleware($this->dashboardOnlyMiddleware())
            ->actingAs($merchant)
            ->post(route('user.payment-links.charges.store'), $this->chargePayload([
                'allowed_payment_methods' => ['pix', 'boleto'],
            ]))
            ->assertRedirect(route('user.payment-links.index'));

        $link = PaymentLink::firstOrFail();
        $this->assertSame($merchant->id, $link->user_id);
        $this->assertSame(PaymentLink::TYPE_CHARGE, $link->type);
        $this->assertSame(PaymentLink::STATUS_PENDING, $link->status);
        $this->assertNull($link->charge_id);
        $this->assertSame(['pix', 'boleto'], $link->allowed_payment_methods);
        $this->assertSame(0, Charge::count());
    }

    public function test_dashboard_creates_subscription_link_intent_without_subscription_or_charge(): void
    {
        $merchant = User::factory()->create(['email_verified_at' => now()]);

        $this->withoutMiddleware($this->dashboardOnlyMiddleware())
            ->actingAs($merchant)
            ->post(route('user.payment-links.subscriptions.store'), $this->subscriptionPayload())
            ->assertRedirect(route('user.payment-links.index'));

        $link = PaymentLink::firstOrFail();
        $this->assertSame(PaymentLink::TYPE_SUBSCRIPTION, $link->type);
        $this->assertSame(PaymentLink::STATUS_PENDING, $link->status);
        $this->assertNull($link->charge_id);
        $this->assertNull($link->customer_subscription_id);
        $this->assertSame('month', $link->metadata['interval']);
        $this->assertSame(0, CustomerSubscription::count());
        $this->assertSame(0, Charge::count());
    }

    public function test_public_checkout_renders_customer_form_without_login(): void
    {
        $link = $this->intentLinkFor(User::factory()->create(), ['allowed_payment_methods' => ['pix']]);

        $this->get(route('payment-links.public.show', $link->slug))
            ->assertOk()
            ->assertSee($link->title)
            ->assertSee('Pagar R$')
            ->assertSee('name="customer_name"', false)
            ->assertSee('value="pix"', false);
    }

    public function test_customer_submits_checkout_and_creates_pix_charge(): void
    {
        $merchant = User::factory()->create();
        $link = $this->intentLinkFor($merchant, ['allowed_payment_methods' => ['pix']]);
        $this->fakeChargeService(expectedCalls: 1);

        $this->post(route('payment-links.public.submit', $link->slug), $this->checkoutPayload(['payment_method' => 'pix']))
            ->assertOk()
            ->assertSee('pix-copy-code');

        $link->refresh();
        $this->assertSame(PaymentLink::STATUS_AWAITING_PAYMENT, $link->status);
        $this->assertNotNull($link->charge_id);
        $this->assertSame(PaymentMethod::PIX, $link->charge->payment_method);
    }

    public function test_customer_submits_checkout_and_creates_boleto_charge(): void
    {
        $merchant = User::factory()->create();
        $link = $this->intentLinkFor($merchant, ['allowed_payment_methods' => ['boleto']]);
        $this->fakeChargeService(expectedCalls: 1);

        $this->post(route('payment-links.public.submit', $link->slug), $this->checkoutPayload(['payment_method' => 'boleto']))
            ->assertOk()
            ->assertSee('23790.00000');

        $this->assertSame(PaymentMethod::BOLETO, $link->refresh()->charge->payment_method);
    }

    public function test_customer_submits_checkout_and_creates_card_charge(): void
    {
        $merchant = User::factory()->create();
        $link = $this->intentLinkFor($merchant, ['allowed_payment_methods' => ['card']]);
        $this->fakeChargeService(expectedCalls: 1);

        $this->post(route('payment-links.public.submit', $link->slug), $this->checkoutPayload(['payment_method' => 'card']))
            ->assertOk()
            ->assertSee('https://checkout.example/card', false);

        $this->assertSame(PaymentMethod::CARD, $link->refresh()->charge->payment_method);
    }

    public function test_subscription_checkout_creates_subscription_and_first_charge(): void
    {
        $merchant = User::factory()->create();
        $link = $this->intentLinkFor($merchant, [
            'type' => PaymentLink::TYPE_SUBSCRIPTION,
            'allowed_payment_methods' => ['pix'],
            'metadata' => ['interval' => 'month', 'interval_count' => 1, 'start_at' => '2026-07-01'],
        ]);
        $this->fakeChargeService(expectedCalls: 1);

        $this->post(route('payment-links.public.submit', $link->slug), $this->checkoutPayload(['payment_method' => 'pix']))
            ->assertOk();

        $link->refresh();
        $this->assertNotNull($link->customer_subscription_id);
        $this->assertNotNull($link->charge_id);
        $this->assertSame(CustomerSubscriptionStatus::PENDING, $link->subscription->status);
    }

    public function test_method_not_allowed_is_blocked(): void
    {
        $link = $this->intentLinkFor(User::factory()->create(), ['allowed_payment_methods' => ['pix']]);
        $this->fakeChargeService(expectedCalls: 0);

        $this->post(route('payment-links.public.submit', $link->slug), $this->checkoutPayload(['payment_method' => 'boleto']))
            ->assertStatus(422);

        $this->assertNull($link->refresh()->charge_id);
    }

    public function test_expired_canceled_and_paid_links_block_new_charges(): void
    {
        $merchant = User::factory()->create();
        $expired = $this->intentLinkFor($merchant, ['expires_at' => now()->subMinute()]);
        $canceled = $this->intentLinkFor($merchant, ['slug' => 'cancel_' . Str::random(24), 'status' => PaymentLink::STATUS_CANCELED]);
        $paid = $this->intentLinkFor($merchant, ['slug' => 'paid_' . Str::random(24), 'status' => PaymentLink::STATUS_PAID, 'paid_at' => now()]);
        $this->fakeChargeService(expectedCalls: 0);

        foreach ([$expired, $canceled, $paid] as $link) {
            $this->post(route('payment-links.public.submit', $link->slug), $this->checkoutPayload())
                ->assertStatus(422);
        }
    }

    public function test_multiple_submits_do_not_create_duplicate_charges(): void
    {
        $link = $this->intentLinkFor(User::factory()->create(), ['allowed_payment_methods' => ['pix']]);
        $this->fakeChargeService(expectedCalls: 1);

        $this->post(route('payment-links.public.submit', $link->slug), $this->checkoutPayload(['payment_method' => 'pix']))
            ->assertOk();
        $this->post(route('payment-links.public.submit', $link->slug), $this->checkoutPayload(['payment_method' => 'pix']))
            ->assertOk();

        $this->assertSame(1, Charge::count());
        $this->assertNotNull($link->refresh()->charge_id);
    }

    public function test_charge_status_update_syncs_payment_link_without_opening_checkout(): void
    {
        $merchant = User::factory()->create();
        $charge = $this->chargeFor($merchant);
        $link = $this->intentLinkFor($merchant, [
            'charge_id' => $charge->id,
            'status' => PaymentLink::STATUS_AWAITING_PAYMENT,
        ]);

        $charge->update(['status' => ChargeStatus::PAID, 'paid_at' => now()]);

        $this->assertSame(PaymentLink::STATUS_PAID, $link->refresh()->status);
        $this->assertNotNull($link->paid_at);
    }

    public function test_merchant_cannot_cancel_another_merchants_payment_link(): void
    {
        $merchantA = User::factory()->create(['email_verified_at' => now()]);
        $merchantB = User::factory()->create(['email_verified_at' => now()]);
        $link = $this->intentLinkFor($merchantB);

        $this->withoutMiddleware($this->dashboardOnlyMiddleware())
            ->actingAs($merchantA)
            ->post(route('user.payment-links.cancel', $link))
            ->assertNotFound();

        $this->assertSame(PaymentLink::STATUS_PENDING, $link->refresh()->status);
    }

    public function test_generated_slugs_are_unique(): void
    {
        $merchant = User::factory()->create(['email_verified_at' => now()]);

        for ($i = 0; $i < 3; $i++) {
            $this->withoutMiddleware($this->dashboardOnlyMiddleware())
                ->actingAs($merchant)
                ->post(route('user.payment-links.charges.store'), $this->chargePayload(['title' => 'Pedido ' . $i]))
                ->assertRedirect(route('user.payment-links.index'));
        }

        $this->assertSame(3, PaymentLink::count());
        $this->assertSame(3, PaymentLink::distinct('slug')->count('slug'));
    }

    public function test_invalid_public_slug_returns_not_found_without_leaking_data(): void
    {
        $this->get('/pay/not-a-real-payment-link')
            ->assertNotFound()
            ->assertDontSee('OriginPay merchant')
            ->assertDontSee('stack trace', false);
    }

    private function fakeChargeService(int $expectedCalls): void
    {
        $mock = Mockery::mock(ChargeService::class);
        $mock->shouldReceive('create')->times($expectedCalls)->andReturnUsing(function (User $user, float $amount, string $paymentMethod, array $customerData) {
            return $this->chargeFor($user, [
                'payment_method' => PaymentMethod::from($paymentMethod),
                'amount' => $amount,
                'description' => $customerData['description'] ?? 'Cobranca',
                'customer_name' => $customerData['name'] ?? 'Cliente',
                'customer_email' => $customerData['email'] ?? 'cliente@example.com',
                'customer_document' => $customerData['document'] ?? '12345678909',
            ]);
        });

        app()->instance(ChargeService::class, $mock);
    }

    private function intentLinkFor(User $merchant, array $overrides = []): PaymentLink
    {
        return PaymentLink::create(array_merge([
            'uuid' => (string) Str::uuid(),
            'slug' => 'pay_' . Str::random(28),
            'user_id' => $merchant->id,
            'type' => PaymentLink::TYPE_CHARGE,
            'amount' => 100,
            'currency' => 'BRL',
            'payment_method' => 'multiple',
            'allowed_payment_methods' => ['pix', 'boleto', 'card'],
            'title' => 'Pedido Teste',
            'description' => 'Pedido Teste',
            'status' => PaymentLink::STATUS_PENDING,
            'expires_at' => now()->addDay(),
            'metadata' => [],
        ], $overrides));
    }

    private function chargeFor(User $merchant, array $overrides = []): Charge
    {
        $paymentMethod = $overrides['payment_method'] ?? PaymentMethod::PIX;
        $amount = $overrides['amount'] ?? 100;

        return Charge::create(array_merge([
            'uuid' => (string) Str::uuid(),
            'correlation_id' => (string) Str::uuid(),
            'idempotency_key' => 'panel_' . Str::uuid(),
            'user_id' => $merchant->id,
            'payment_method' => $paymentMethod,
            'amount' => $amount,
            'platform_fee' => 0,
            'gateway_fee' => 0,
            'net_amount' => $amount,
            'description' => 'Cobranca painel',
            'customer_name' => 'Cliente Painel',
            'customer_email' => 'cliente@example.com',
            'customer_document' => '12345678909',
            'status' => ChargeStatus::WAITING_PAYMENT,
            'expires_at' => now()->addDays(3),
            'payment_link' => $paymentMethod === PaymentMethod::CARD ? 'https://checkout.example/card' : null,
            'qr_code' => $paymentMethod === PaymentMethod::PIX ? 'data:image/png;base64,abc' : null,
            'pix_copy_paste' => $paymentMethod === PaymentMethod::PIX ? 'pix-copy-code' : null,
            'boleto_url' => $paymentMethod === PaymentMethod::BOLETO ? 'https://boleto.example/1' : null,
            'boleto_pdf_url' => $paymentMethod === PaymentMethod::BOLETO ? 'https://boleto.example/1.pdf' : null,
            'barcode' => $paymentMethod === PaymentMethod::BOLETO ? '1234567890' : null,
            'digitable_line' => $paymentMethod === PaymentMethod::BOLETO ? '23790.00000 00000.000000 00000.000000 1 00000000010000' : null,
            'metadata' => [],
        ], $overrides));
    }

    private function chargePayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Pedido Painel',
            'description' => 'Pedido Painel',
            'amount' => 100,
            'allowed_payment_methods' => ['pix'],
        ], $overrides);
    }

    private function subscriptionPayload(array $overrides = []): array
    {
        return array_merge($this->chargePayload(), [
            'title' => 'Assinatura Painel',
            'interval' => 'month',
            'interval_count' => 1,
            'start_at' => '2026-07-01',
        ], $overrides);
    }

    private function checkoutPayload(array $overrides = []): array
    {
        return array_merge([
            'payment_method' => 'pix',
            'customer_name' => 'Cliente Checkout',
            'customer_email' => 'checkout@example.com',
            'customer_document' => '12345678909',
            'customer_phone' => '11999999999',
        ], $overrides);
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
}
