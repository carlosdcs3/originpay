<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\GatewayFeeConfig;
use App\Services\Payment\GatewayFeeService;
use App\Models\Transaction;
use App\Enums\TrxType;
use App\Enums\TrxStatus;
use App\Enums\MethodType;
use App\Services\TransactionService;
use App\Models\User;

class GatewayFeeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        GatewayFeeConfig::create([
            'provider' => 'EFI',
            'transaction_fee_type' => 'fixed_plus_percent',
            'transaction_fixed_fee' => 1.00,
            'transaction_percent_fee' => 2.00,
            'withdraw_fee_type' => 'fixed',
            'withdraw_fixed_fee' => 1.50,
            'withdraw_percent_fee' => 0.00,
            'provider_fee_mode' => 'estimated',
            'provider_fixed_fee' => 0.99,
            'provider_percent_fee' => 0.00,
            'currency' => 'BRL',
            'is_active' => true,
        ]);
    }

    public function test_fee_calculation_fixed_and_percent()
    {
        $service = app(GatewayFeeService::class);
        $result = $service->calculateForDeposit(100.00, 'EFI');

        // Platform: R$1 + 2% of 100 = R$3.00
        // Provider: R$0.99
        // Net: 100 - 3.00 - 0.99 = 96.01

        $this->assertEquals(3.00, $result->platform_fee_amount);
        $this->assertEquals(0.99, $result->provider_fee_amount);
        $this->assertEquals(96.01, $result->net_amount);
        $this->assertEquals(100.00, $result->gross_amount);
    }

    public function test_fee_calculation_withdraw()
    {
        $service = app(GatewayFeeService::class);
        $result = $service->calculateForWithdraw(100.00, 'EFI');

        // Withdraw: 1.50 fixed platform
        // Provider: 0.99
        // Net: 100 - 1.50 - 0.99 = 97.51

        $this->assertEquals(1.50, $result->platform_fee_amount);
        $this->assertEquals(0.99, $result->provider_fee_amount);
        $this->assertEquals(97.51, $result->net_amount);
        $this->assertEquals(100.00, $result->gross_amount);
    }

    public function test_negative_net_amount_throws_exception()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Fee calculation resulted in negative net amount");

        $service = app(GatewayFeeService::class);
        $service->calculateForDeposit(2.00, 'EFI'); // 1+2% + 0.99 = 2.03. 2.00 - 2.03 < 0
    }

    public function test_snapshot_is_created_on_transaction()
    {
        $user = User::factory()->create();
        $txData = new \App\Data\TransactionData(
            user_id: $user->id,
            trx_type: TrxType::DEPOSIT,
            amount: 100.00,
            provider: 'EFI',
            processing_type: MethodType::AUTOMATIC,
            status: TrxStatus::PENDING,
        );

        $service = app(TransactionService::class);
        $transaction = $service->create($txData);

        $this->assertNotNull($transaction->trx_data['fee_snapshot']);
        $this->assertEquals(3.00, $transaction->trx_data['fee_snapshot']['platform_fee_amount']);
        $this->assertEquals(0.99, $transaction->trx_data['fee_snapshot']['provider_fee_amount']);
    }

    public function test_snapshot_immutability()
    {
        $user = User::factory()->create();
        $txData = new \App\Data\TransactionData(
            user_id: $user->id,
            trx_type: TrxType::DEPOSIT,
            amount: 100.00,
            provider: 'EFI',
            processing_type: MethodType::AUTOMATIC,
            status: TrxStatus::PENDING,
        );

        $service = app(TransactionService::class);
        $transaction = $service->create($txData);

        // Admin changes config
        $config = GatewayFeeConfig::first();
        $config->transaction_fixed_fee = 50.00;
        $config->save();

        $transaction->refresh();
        $this->assertEquals(3.00, $transaction->trx_data['fee_snapshot']['platform_fee_amount']);
    }
}
