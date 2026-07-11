<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\PlatformFeeRule;
use App\Models\PlatformFeeRuleAudit;
use App\Models\User;
use App\Services\Fees\PlatformFeeCalculator;
use App\Services\Fees\PlatformFeeResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformFeeAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_creates_global_platform_fee_rule(): void
    {
        $admin = Admin::factory()->create();

        $response = $this->actingAs($admin, 'admin')
            ->postJson(route('admin.platform-fees.global.store'), $this->payload([
                'payment_method' => 'pix',
                'percentage_fee' => 1.99,
                'fixed_fee' => 0.30,
            ]));

        $response->assertCreated();

        $this->assertDatabaseHas('platform_fee_rules', [
            'scope' => PlatformFeeRule::SCOPE_GLOBAL,
            'user_id' => null,
            'payment_method' => 'pix',
            'status' => PlatformFeeRule::STATUS_ACTIVE,
        ]);
    }

    public function test_admin_creates_individual_merchant_override(): void
    {
        $admin = Admin::factory()->create();
        $merchant = User::factory()->create();

        $response = $this->actingAs($admin, 'admin')
            ->postJson(route('admin.platform-fees.merchant.store'), $this->payload([
                'user_id' => $merchant->id,
                'payment_method' => 'card',
                'percentage_fee' => 2.75,
                'fixed_fee' => 0.49,
            ]));

        $response->assertCreated();

        $this->assertDatabaseHas('platform_fee_rules', [
            'scope' => PlatformFeeRule::SCOPE_MERCHANT,
            'user_id' => $merchant->id,
            'payment_method' => 'card',
            'status' => PlatformFeeRule::STATUS_ACTIVE,
        ]);
    }

    public function test_customer_cannot_access_admin_platform_fee_panel(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('admin.platform-fees.index'));

        $response->assertStatus(302);
        $this->assertFalse(auth('admin')->check());
    }

    public function test_platform_fee_rule_change_writes_audit_log(): void
    {
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin')
            ->postJson(route('admin.platform-fees.global.store'), $this->payload([
                'reason' => 'Ajuste comercial auditado',
            ]))
            ->assertCreated();

        $rule = PlatformFeeRule::firstOrFail();

        $this->assertDatabaseHas('platform_fee_rule_audits', [
            'platform_fee_rule_id' => $rule->id,
            'admin_id' => $admin->id,
            'action' => 'created',
            'reason' => 'Ajuste comercial auditado',
        ]);

        $this->assertSame(1, PlatformFeeRuleAudit::count());
    }

    public function test_inactive_rule_is_not_resolved_as_applicable(): void
    {
        $admin = Admin::factory()->create();
        $merchant = User::factory()->create();

        $this->actingAs($admin, 'admin')
            ->postJson(route('admin.platform-fees.global.store'), $this->payload([
                'payment_method' => 'pix',
                'status' => PlatformFeeRule::STATUS_INACTIVE,
            ]))
            ->assertCreated();

        $result = app(PlatformFeeResolver::class)->resolve($merchant, 'pix', 100.00);

        $this->assertSame('fallback', $result->source);
        $this->assertNull($result->ruleId);
    }

    public function test_simulator_matches_platform_fee_calculator(): void
    {
        $admin = Admin::factory()->create();
        $rule = PlatformFeeRule::create([
            'scope' => PlatformFeeRule::SCOPE_GLOBAL,
            'user_id' => null,
            'payment_method' => 'boleto',
            'currency' => 'BRL',
            'fixed_fee' => 1.99,
            'percentage_fee' => 0,
            'minimum_fee' => 0,
            'maximum_fee' => null,
            'settlement_delay_days' => 2,
            'reserve_percentage' => 0,
            'status' => PlatformFeeRule::STATUS_ACTIVE,
            'starts_at' => now()->subDay(),
        ]);

        $expected = app(PlatformFeeCalculator::class)->calculate(150.00, $rule, $rule->scope);

        $response = $this->actingAs($admin, 'admin')
            ->postJson(route('admin.platform-fees.simulate'), [
                'amount' => 150.00,
                'payment_method' => 'boleto',
                'currency' => 'BRL',
                'rule_id' => $rule->id,
            ]);

        $response->assertOk()
            ->assertJsonPath('platform_fee_amount', $expected->platformFeeAmount)
            ->assertJsonPath('net_amount', $expected->netAmount)
            ->assertJsonPath('rule_id', $rule->id);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'payment_method' => 'pix',
            'currency' => 'BRL',
            'fixed_fee' => 0.30,
            'percentage_fee' => 2.00,
            'minimum_fee' => 0,
            'maximum_fee' => null,
            'settlement_delay_days' => 1,
            'reserve_percentage' => 0,
            'status' => PlatformFeeRule::STATUS_ACTIVE,
            'starts_at' => now()->subDay()->format('Y-m-d H:i:s'),
            'ends_at' => null,
            'reason' => 'Teste de taxa da plataforma',
        ], $overrides);
    }
}
