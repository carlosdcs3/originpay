<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Wallet;
use App\Models\PaymentGateway;
use App\Models\WalletBalance;
use App\Models\Currency;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class GatewayLedgerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->currency = Currency::factory()->create(['currency_code' => 'BRL']);
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        $this->wallet = Wallet::factory()->create([
            'user_id' => $this->user->id, 
            'balance' => 0,
            'currency_id' => $this->currency->id
        ]);
        
        $this->gateway1 = PaymentGateway::factory()->create([
            'name' => 'Gateway A',
            'status' => true,
            'operations' => ['PIX_CHARGE', 'PIX_WITHDRAW']
        ]);
        
        $this->gateway2 = PaymentGateway::factory()->create([
            'name' => 'Gateway B',
            'status' => true,
            'operations' => ['PIX_CHARGE'] // Sem saque
        ]);
    }

    public function test_credit_in_specific_gateway()
    {
        $this->wallet->creditGateway($this->gateway1->id, 100);
        
        $this->assertEquals(100, $this->wallet->fresh()->balance);
        
        $balance = WalletBalance::where('wallet_id', $this->wallet->id)
            ->where('gateway_id', $this->gateway1->id)->first();
            
        $this->assertNotNull($balance);
        $this->assertEquals(100, $balance->available);
    }

    public function test_debit_in_specific_gateway()
    {
        $this->wallet->creditGateway($this->gateway1->id, 150);
        
        $success = $this->wallet->debitGateway($this->gateway1->id, 50);
        
        $this->assertTrue($success);
        $this->assertEquals(100, $this->wallet->fresh()->balance);
        
        $balance = WalletBalance::where('wallet_id', $this->wallet->id)
            ->where('gateway_id', $this->gateway1->id)->first();
            
        $this->assertEquals(100, $balance->available);
    }

    public function test_withdrawal_attempt_with_insufficient_gateway_balance()
    {
        $this->wallet->creditGateway($this->gateway1->id, 50);
        $this->wallet->creditGateway($this->gateway2->id, 100);
        
        // Total balance is 150, but gateway1 only has 50
        $this->assertEquals(150, $this->wallet->fresh()->balance);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Saldo insuficiente no provedor selecionado para realizar esta operação.');
        
        // Try to debit 100 from gateway 1
        $this->wallet->debitGateway($this->gateway1->id, 100);
    }
    
    public function test_legacy_migration_idempotency()
    {
        // Setup legacy state
        $this->wallet->balance = 500;
        $this->wallet->save();
        
        // Execute migration first time
        Artisan::call('wallets:migrate-gateway-balances');
        
        $balanceRecord = WalletBalance::where('wallet_id', $this->wallet->id)->first();
        $this->assertNotNull($balanceRecord);
        $this->assertEquals(500, $balanceRecord->available);
        $this->assertEquals($this->gateway1->id, $balanceRecord->gateway_id); // Since it's the first active
        
        $this->assertEquals(500, $this->wallet->fresh()->balance);
        
        // Execute migration second time
        Artisan::call('wallets:migrate-gateway-balances');
        
        // Balance should still be 500, no duplication
        $balanceRecord = WalletBalance::where('wallet_id', $this->wallet->id)->first();
        $this->assertEquals(500, $balanceRecord->available);
        
        // Ensure no new records were created
        $this->assertEquals(1, WalletBalance::where('wallet_id', $this->wallet->id)->count());
        $this->assertEquals(500, $this->wallet->fresh()->balance);
    }

    public function test_concurrent_debits()
    {
        $this->wallet->creditGateway($this->gateway1->id, 100);
        
        // Simulate concurrent transactions using process forks or just multiple transactions
        // In PHPUnit, real concurrency requires pcntl or parallel runner. 
        // We will simulate DB transaction locks by verifying the logic inside the method.
        
        DB::beginTransaction();
        
        $walletLock = Wallet::where('id', $this->wallet->id)->lockForUpdate()->first();
        $this->assertNotNull($walletLock);
        
        // Another connection would block here
        $this->wallet->debitGateway($this->gateway1->id, 20);
        
        DB::commit();
        
        $this->assertEquals(80, $this->wallet->fresh()->balance);
        $this->assertEquals(80, WalletBalance::where('wallet_id', $this->wallet->id)->first()->available);
    }
}
