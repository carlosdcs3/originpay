<?php

namespace Tests\Feature;

use App\Enums\KycStatus;
use App\Enums\MethodType;
use App\Enums\SystemWalletUUID;
use App\Enums\TrxType;
use App\Exceptions\NotifyErrorException;
use App\Models\PaymentGateway;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletBalance;
use App\Models\WalletTransaction;
use App\Models\WithdrawAccount;
use App\Models\WithdrawMethod;
use App\Services\Handlers\WithdrawHandler;
use App\Services\PaymentService;
use App\Services\TransactionNotifierService;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class LegacyPaymentWithdrawalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(TransactionNotifierService::class, function ($mock) {
            $mock->shouldReceive('toUser')->zeroOrMoreTimes();
            $mock->shouldReceive('toAdmins')->zeroOrMoreTimes();
        });
    }

    public function test_legacy_withdrawal_service_accepts_gateway_with_active_pix_withdraw_flags(): void
    {
        [$user, $wallet, $account, $gateway] = $this->buildWithdrawalScenario(supportsWithdraw: true);

        $this->actingAs($user);
        $this->mockSubmittedHandlerOnly();

        app(PaymentService::class)->withdrawMoney($account, $wallet, 50);

        $this->assertDatabaseHas('wallet_balances', [
            'wallet_id' => $wallet->id,
            'gateway_id' => $gateway->id,
            'available' => 50,
            'blocked' => 50,
        ]);

        $wallet->refresh();
        $this->assertSame(100.0, (float) $wallet->balance);
        $this->assertSame(50.0, (float) $wallet->available_balance);
        $this->assertSame(50.0, (float) $wallet->reserved_balance);
    }

    public function test_legacy_withdrawal_service_returns_specific_message_when_gateway_cannot_process_pix_withdraw(): void
    {
        [$user, $wallet, $account] = $this->buildWithdrawalScenario(supportsWithdraw: false);

        $this->actingAs($user);
        $this->mockSubmittedHandlerOnly();

        $this->expectException(NotifyErrorException::class);
        $this->expectExceptionMessage('Seu saldo disponível não está alocado em um gateway com PIX Saque ativo para este valor.');

        app(PaymentService::class)->withdrawMoney($account, $wallet, 50);
    }

    public function test_legacy_withdrawal_request_reduces_only_available_balance(): void
    {
        [$user, $wallet, $account, $gateway] = $this->buildWithdrawalScenario(supportsWithdraw: true, balance: 300);

        $this->actingAs($user);
        $this->mockSubmittedHandlerOnly();

        app(PaymentService::class)->withdrawMoney($account, $wallet, 50);

        $wallet->refresh();
        $gatewayBalance = WalletBalance::where('wallet_id', $wallet->id)->where('gateway_id', $gateway->id)->firstOrFail();

        $this->assertSame(300.0, (float) $wallet->balance);
        $this->assertSame(250.0, (float) $wallet->available_balance);
        $this->assertSame(50.0, (float) $wallet->reserved_balance);
        $this->assertSame(250.0, (float) $gatewayBalance->available);
        $this->assertSame(50.0, (float) $gatewayBalance->blocked);
    }

    public function test_legacy_withdrawal_approval_debits_balance_once(): void
    {
        [$user, $wallet, $account, $gateway] = $this->buildWithdrawalScenario(supportsWithdraw: true, balance: 300);
        $this->createSystemRevenueWallet();

        $this->actingAs($user);
        app(PaymentService::class)->withdrawMoney($account, $wallet, 50);

        $transaction = Transaction::where('trx_type', TrxType::WITHDRAW)->firstOrFail();

        app(TransactionService::class)->completeTransaction($transaction->trx_id);

        $wallet->refresh();
        $gatewayBalance = WalletBalance::where('wallet_id', $wallet->id)->where('gateway_id', $gateway->id)->firstOrFail();

        $this->assertSame(250.0, (float) $wallet->balance);
        $this->assertSame(250.0, (float) $wallet->available_balance);
        $this->assertSame(0.0, (float) $wallet->reserved_balance);
        $this->assertSame(50.0, (float) $wallet->withdrawn_balance);
        $this->assertSame(250.0, (float) $gatewayBalance->available);
        $this->assertSame(0.0, (float) $gatewayBalance->blocked);
    }

    public function test_legacy_withdrawal_rejection_restores_only_available_balance(): void
    {
        [$user, $wallet, $account, $gateway] = $this->buildWithdrawalScenario(supportsWithdraw: true, balance: 300);

        $this->actingAs($user);
        $this->mockSubmittedHandlerOnly();
        app(PaymentService::class)->withdrawMoney($account, $wallet, 50);

        $transaction = Transaction::where('trx_type', TrxType::WITHDRAW)->firstOrFail();

        app(TransactionService::class)->cancelTransaction($transaction->trx_id, 'Rejected', true);

        $wallet->refresh();
        $gatewayBalance = WalletBalance::where('wallet_id', $wallet->id)->where('gateway_id', $gateway->id)->firstOrFail();

        $this->assertSame(300.0, (float) $wallet->balance);
        $this->assertSame(300.0, (float) $wallet->available_balance);
        $this->assertSame(0.0, (float) $wallet->reserved_balance);
        $this->assertSame(300.0, (float) $gatewayBalance->available);
        $this->assertSame(0.0, (float) $gatewayBalance->blocked);
    }

    public function test_legacy_withdrawal_fails_when_available_balance_is_insufficient(): void
    {
        [$user, $wallet, $account] = $this->buildWithdrawalScenario(supportsWithdraw: true, balance: 30);

        $this->actingAs($user);
        $this->mockSubmittedHandlerOnly();

        $this->expectException(NotifyErrorException::class);
        $this->expectExceptionMessage('Insufficient available balance to process this payment.');

        app(PaymentService::class)->withdrawMoney($account, $wallet, 50);
    }

    public function test_legacy_withdrawal_approval_is_idempotent(): void
    {
        [$user, $wallet, $account] = $this->buildWithdrawalScenario(supportsWithdraw: true, balance: 300);
        $this->createSystemRevenueWallet();

        $this->actingAs($user);
        app(PaymentService::class)->withdrawMoney($account, $wallet, 50);

        $transaction = Transaction::where('trx_type', TrxType::WITHDRAW)->firstOrFail();
        $service = app(TransactionService::class);

        $service->completeTransaction($transaction->trx_id);
        $service->completeTransaction($transaction->trx_id);

        $wallet->refresh();

        $this->assertSame(250.0, (float) $wallet->balance);
        $this->assertSame(250.0, (float) $wallet->available_balance);
        $this->assertSame(1, WalletTransaction::where('type', 'legacy_withdraw_settlement')->count());
    }

    private function buildWithdrawalScenario(bool $supportsWithdraw, float $balance = 100): array
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'kyc_status' => KycStatus::APPROVED,
        ]);

        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'balance' => $balance,
            'available_balance' => $balance,
            'reserved_balance' => 0,
            'withdrawn_balance' => 0,
        ]);

        $gateway = PaymentGateway::factory()->create([
            'name' => 'EFI',
            'code' => 'efi',
            'status' => true,
            'is_withdraw' => $supportsWithdraw,
            'supports_withdrawal' => $supportsWithdraw,
        ]);

        $method = WithdrawMethod::create([
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

        $account = WithdrawAccount::create([
            'user_id' => $user->id,
            'withdraw_method_id' => $method->id,
            'name' => '12345678901',
            'credentials' => [
                ['name' => 'pix_key', 'type' => 'text', 'value' => '12345678901'],
            ],
        ]);

        WalletBalance::create([
            'wallet_id' => $wallet->id,
            'gateway_id' => $gateway->id,
            'available' => $balance,
            'pending' => 0,
            'blocked' => 0,
        ]);

        return [$user, $wallet, $account, $gateway];
    }

    private function mockSubmittedHandlerOnly(): void
    {
        $mock = Mockery::mock(WithdrawHandler::class);
        $mock->shouldReceive('handleSubmitted')->andReturnNull();
        $mock->shouldReceive('handleFail')->andReturnNull();

        app()->instance(WithdrawHandler::class, $mock);
    }

    private function createSystemRevenueWallet(): void
    {
        $systemUser = User::factory()->create([
            'email' => 'system_revenue@ledger.internal',
        ]);

        Wallet::factory()->create([
            'user_id' => $systemUser->id,
            'uuid' => SystemWalletUUID::SYSTEM_REVENUE->value,
            'balance' => 0,
            'available_balance' => 0,
        ]);
    }
}
