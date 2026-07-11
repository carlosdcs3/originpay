<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Services\TransactionService;
use App\Enums\TrxStatus;
use App\Enums\TrxType;
use App\Enums\MethodType;
use App\Services\Security\TenantBypass;
use App\Services\TransactionNotifierService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TransactionImmutabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(TransactionNotifierService::class, function ($mock) {
            $mock->shouldReceive('toUser')->zeroOrMoreTimes();
            $mock->shouldReceive('toAdmins')->zeroOrMoreTimes();
        });

        // System wallet setup for ledger tests
        $user = User::factory()->create(['email' => 'system_refund@ledger.internal']);
        Wallet::factory()->create([
            'user_id' => $user->id,
            'uuid' => \App\Enums\SystemWalletUUID::SYSTEM_REFUND->value,
            'balance' => 1000000
        ]);
        Wallet::factory()->create([
            'user_id' => $user->id,
            'uuid' => 'SYSTEM-GENERAL',
            'balance' => 1000000
        ]);
    }

    public function test_transaction_delete_throws_exception()
    {
        $user = User::factory()->create();
        $transaction = Transaction::factory()->create(['user_id' => $user->id, 'status' => TrxStatus::PENDING]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('excluídas fisicamente');

        $transaction->delete();
    }

    public function test_transaction_amount_update_throws_exception()
    {
        $user = User::factory()->create();
        $transaction = Transaction::factory()->create(['user_id' => $user->id, 'amount' => 100]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('imutável');

        $transaction->update(['amount' => 500]);
    }

    public function test_transaction_status_cannot_change_from_completed_to_canceled()
    {
        $user = User::factory()->create();
        $transaction = Transaction::factory()->create(['user_id' => $user->id, 'status' => TrxStatus::COMPLETED]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('após sair de PENDING');

        $transaction->update(['status' => TrxStatus::CANCELED]);
    }

    public function test_transaction_status_can_change_from_pending_to_completed()
    {
        $user = User::factory()->create();
        $transaction = Transaction::factory()->create(['user_id' => $user->id, 'status' => TrxStatus::PENDING]);

        $transaction->update(['status' => TrxStatus::COMPLETED]);

        $this->assertEquals(TrxStatus::COMPLETED, $transaction->refresh()->status);
    }

    public function test_cancellation_creates_new_transaction_and_preserves_original()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id]);
        $transaction = Transaction::factory()->create([
            'user_id' => $user->id, 
            'status' => TrxStatus::PENDING, 
            'wallet_reference' => $wallet->uuid,
            'amount' => 100,
            'payable_amount' => 100
        ]);

        $service = app(TransactionService::class);
        $service->cancelTransaction($transaction->trx_id, 'User requested cancel');

        $transaction->refresh();
        $this->assertEquals(TrxStatus::FAILED, $transaction->status);
        $this->assertTrue($transaction->trx_data['is_cancelled']);
        $this->assertEquals('cancelled', $transaction->derived_status);

        $cancellationTrx = TenantBypass::run(fn () => Transaction::where('trx_type', TrxType::CANCELLATION)
            ->where('trx_reference', $transaction->trx_id)
            ->first());

        $this->assertNotNull($cancellationTrx);
        $this->assertEquals(100, $cancellationTrx->amount);
    }

    public function test_refund_creates_new_transaction_and_supports_partial_via_system()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id, 'balance' => 50]);
        $transaction = Transaction::factory()->create([
            'user_id' => $user->id, 
            'status' => TrxStatus::COMPLETED, 
            'wallet_reference' => $wallet->uuid,
            'amount' => 100,
            'payable_amount' => 100
        ]);

        $service = app(TransactionService::class);
        
        // Partial Refund 40
        $service->refundTransaction($transaction->trx_id, 40, 'Partial refund', \App\Enums\FinancialSourceType::SYSTEM);

        $transaction->refresh();
        $this->assertEquals(TrxStatus::COMPLETED, $transaction->status); // Status is unchanged
        $this->assertEquals(40, $transaction->trx_data['refund_amount']);
        $this->assertEquals('partially_refunded', $transaction->derived_status);

        $refundTrx = TenantBypass::run(fn () => Transaction::where('trx_type', TrxType::REFUND)->first());
        $this->assertNotNull($refundTrx);
        $this->assertEquals(40, $refundTrx->amount);

        // Wallet should receive funds back
        $this->assertEquals(90, $wallet->refresh()->balance);

        // Remaining Refund 60
        $service->refundTransaction($transaction->trx_id, 60, 'Remaining refund', \App\Enums\FinancialSourceType::SYSTEM);
        
        $transaction->refresh();
        $this->assertEquals(100, $transaction->trx_data['refund_amount']);
        $this->assertEquals('refunded', $transaction->derived_status);
    }

    public function test_duplicate_refund_is_blocked()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id]);
        $transaction = Transaction::factory()->create([
            'user_id' => $user->id, 
            'status' => TrxStatus::COMPLETED, 
            'wallet_reference' => $wallet->uuid,
            'amount' => 100
        ]);

        $service = app(TransactionService::class);
        $service->refundTransaction($transaction->trx_id, 50, 'First refund', \App\Enums\FinancialSourceType::SYSTEM, null, 'GW_REF_1');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Duplicate refund detected');

        // Trying exactly the same refund amount and ID
        $service->refundTransaction($transaction->trx_id, 50, 'Second refund', \App\Enums\FinancialSourceType::SYSTEM, null, 'GW_REF_1');
    }

    public function test_refund_via_merchant_fails_if_insufficient_balance()
    {
        $merchant = User::factory()->create();
        $merchantWallet = Wallet::factory()->create(['user_id' => $merchant->id, 'balance' => 0]); // no balance
        
        $payer = User::factory()->create();
        $payerWallet = Wallet::factory()->create(['user_id' => $payer->id, 'balance' => 100]);

        // Mock a ledger entry showing merchant received the money
        $transaction = Transaction::factory()->create([
            'user_id' => $payer->id, 
            'status' => TrxStatus::COMPLETED, 
            'wallet_reference' => $payerWallet->uuid,
            'amount' => 100
        ]);

        \App\Models\LedgerEntry::factory()->create([
            'transaction_id' => $transaction->id,
            'wallet_id' => $merchantWallet->id,
            'direction' => 'credit',
            'amount' => 100,
        ]);

        $service = app(TransactionService::class);
        
        // This should throw NotifyErrorException inside LedgerService because merchant has 0 balance
        $this->expectException(\App\Exceptions\NotifyErrorException::class);
        $this->expectExceptionMessage('Insufficient available balance');

        $service->refundTransaction($transaction->trx_id, 100, 'Refund from merchant', \App\Enums\FinancialSourceType::MERCHANT);
    }

    public function test_chargeback_creates_new_transaction_via_gateway_holding()
    {
        $user = User::factory()->create();
        $payerWallet = Wallet::factory()->create(['user_id' => $user->id, 'balance' => 0]);
        $holdingWallet = Wallet::factory()->create(['user_id' => $user->id, 'uuid' => 'GATEWAY_STRIPE_HOLDING', 'balance' => 500]);
        Wallet::factory()->create(['user_id' => $user->id, 'uuid' => \App\Enums\SystemWalletUUID::SYSTEM_CHARGEBACK->value, 'balance' => 0]);

        $transaction = Transaction::factory()->create([
            'user_id' => $user->id, 
            'status' => TrxStatus::COMPLETED, 
            'wallet_reference' => $payerWallet->uuid,
            'amount' => 100
        ]);

        $service = app(TransactionService::class);
        $service->chargebackTransaction($transaction->trx_id, 100, 'Fraud dispute', \App\Enums\FinancialSourceType::GATEWAY, 'GATEWAY_STRIPE_HOLDING', 'DISP_123');

        $transaction->refresh();
        $this->assertEquals(TrxStatus::COMPLETED, $transaction->status);
        $this->assertTrue($transaction->trx_data['is_charged_back']);
        $this->assertEquals('charged_back', $transaction->derived_status);

        $chargebackTrx = TenantBypass::run(fn () => Transaction::where('trx_type', TrxType::CHARGEBACK)->first());
        $this->assertNotNull($chargebackTrx);
        $this->assertEquals(100, $chargebackTrx->amount);
        
        $this->assertEquals(400, $holdingWallet->refresh()->balance);
    }
}
