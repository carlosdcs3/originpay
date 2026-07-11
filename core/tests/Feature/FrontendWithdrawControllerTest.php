<?php

namespace Tests\Feature;

use App\Enums\KycStatus;
use App\Enums\MethodType;
use App\Facades\PaymentFacade as Payment;
use App\Models\FeatureFlag;
use App\Models\PaymentGateway;
use App\Models\PixKey;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WithdrawalSetting;
use App\Models\WithdrawMethod;
use App\Models\WithdrawSchedule;
use App\Services\TransactionPasswordService;
use App\Services\Treasury\LiquidityProtectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrontendWithdrawControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();

        WithdrawalSetting::query()->delete();
        WithdrawalSetting::create([
            'withdraw_enabled' => true,
            'auto_approve_enabled' => false,
            'minimum_amount' => 10,
            'maximum_amount' => 10000,
            'daily_amount_limit' => 50000,
            'daily_count_limit' => 5,
        ]);

        FeatureFlag::updateOrCreate(
            ['key' => 'withdrawals_enabled'],
            ['is_active' => true, 'description' => 'System-wide withdrawal kill switch']
        );

        $this->mock(LiquidityProtectionService::class, function ($mock) {
            $mock->shouldReceive('evaluateLiquidity')->andReturn('GREEN');
        });
    }

    public function test_withdraw_request_is_not_blocked_by_processing_schedule_when_platform_and_gateway_allow(): void
    {
        $user = $this->approvedUser();
        $pixKey = $this->seedWithdrawContext($user);

        WithdrawSchedule::create([
            'day' => now()->englishDayOfWeek,
            'status' => false,
        ]);

        Payment::shouldReceive('withdrawMoney')->once();

        $this->actingAs($user)
            ->from(route('user.withdraw.create'))
            ->post(route('user.withdraw.store'), [
                'pix_key_id' => $pixKey->id,
                'amount' => 50,
                'transaction_password' => '1234',
            ])
            ->assertRedirect(route('user.transaction.index'));
    }

    public function test_withdraw_request_is_blocked_when_platform_disables_withdrawals(): void
    {
        $user = $this->approvedUser();
        $pixKey = $this->seedWithdrawContext($user);

        WithdrawalSetting::firstOrFail()->update(['withdraw_enabled' => false]);

        Payment::shouldReceive('withdrawMoney')->never();

        $this->actingAs($user)
            ->from(route('user.withdraw.create'))
            ->post(route('user.withdraw.store'), [
                'pix_key_id' => $pixKey->id,
                'amount' => 50,
                'transaction_password' => '1234',
            ])
            ->assertRedirect(route('user.withdraw.create'))
            ->assertSessionHas('notifyevs', fn ($notify) => $notify['message'] === 'Saques estão temporariamente desativados pela plataforma.');
    }

    public function test_withdraw_request_is_blocked_when_user_has_not_completed_kyc(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'kyc_status' => KycStatus::PENDING,
        ]);
        $pixKey = $this->seedWithdrawContext($user);

        Payment::shouldReceive('withdrawMoney')->never();

        $this->actingAs($user)
            ->from(route('user.withdraw.create'))
            ->post(route('user.withdraw.store'), [
                'pix_key_id' => $pixKey->id,
                'amount' => 50,
                'transaction_password' => '1234',
            ])
            ->assertRedirect(route('user.withdraw.create'))
            ->assertSessionHas('notifyevs', fn ($notify) => $notify['message'] === 'Conclua sua verificação para sacar.');
    }

    public function test_withdraw_request_is_blocked_when_liquidity_is_critical(): void
    {
        $user = $this->approvedUser();
        $pixKey = $this->seedWithdrawContext($user);

        $this->mock(LiquidityProtectionService::class, function ($mock) {
            $mock->shouldReceive('evaluateLiquidity')->andReturn('CRITICAL');
        });

        Payment::shouldReceive('withdrawMoney')->never();

        $this->actingAs($user)
            ->from(route('user.withdraw.create'))
            ->post(route('user.withdraw.store'), [
                'pix_key_id' => $pixKey->id,
                'amount' => 50,
                'transaction_password' => '1234',
            ])
            ->assertRedirect(route('user.withdraw.create'))
            ->assertSessionHas('notifyevs', fn ($notify) => $notify['message'] === 'Saque indisponível no momento por validação operacional.');
    }

    private function approvedUser(): User
    {
        return User::factory()->create([
            'email_verified_at' => now(),
            'kyc_status' => KycStatus::APPROVED,
        ]);
    }

    private function seedWithdrawContext(User $user): PixKey
    {
        $gateway = PaymentGateway::factory()->create([
            'name' => 'EFI',
            'code' => 'efi',
            'status' => true,
            'is_withdraw' => true,
            'supports_withdrawal' => true,
        ]);

        WithdrawMethod::create([
            'payment_gateway_id' => $gateway->id,
            'name' => 'PIX Saque',
            'type' => MethodType::MANUAL,
            'code' => 'pix_saque',
            'currency' => 'BRL',
            'currency_symbol' => 'R$',
            'min_withdraw' => 10,
            'max_withdraw' => 1000,
            'conversion_rate_live' => false,
            'conversion_rate' => 1,
            'charge_type' => 'fixed',
            'charge' => 0,
            'status' => true,
            'fields' => [],
        ]);

        Wallet::factory()->create([
            'user_id' => $user->id,
            'balance' => 500,
            'available_balance' => 500,
        ]);

        app(TransactionPasswordService::class)->createPassword($user, '1234');

        return PixKey::create([
            'user_id' => $user->id,
            'key_type' => 'cpf',
            'pix_key' => '12345678901',
            'verified' => true,
            'verified_at' => now(),
            'is_primary' => true,
        ]);
    }
}
