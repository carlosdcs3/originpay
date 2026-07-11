<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Enums\TrxStatus;
use App\Enums\TrxType;
use App\Enums\ProviderType;
use App\Payment\Modern\DTO\WebhookDTO;
use App\Services\TransactionService;
use App\Services\Security\TenantBypass;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewProviderEndToEndTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $systemUser = User::factory()->create(['email' => 'system_revenue@ledger.internal']);
        Wallet::factory()->create(['user_id' => $systemUser->id, 'uuid' => \App\Enums\SystemWalletUUID::SYSTEM_REVENUE->value, 'balance' => 0]);
        Wallet::factory()->create(['user_id' => $systemUser->id, 'uuid' => \App\Enums\SystemWalletUUID::SYSTEM_REFUND->value, 'balance' => 10000]);
        Wallet::factory()->create(['user_id' => $systemUser->id, 'uuid' => \App\Enums\SystemWalletUUID::SYSTEM_CHARGEBACK->value, 'balance' => 0]);
        Wallet::factory()->create(['user_id' => $systemUser->id, 'uuid' => 'GATEWAY_MANUAL_HOLDING', 'balance' => 50000]);
    }

    public function test_process_webhook_paid_deposits_money_and_updates_status()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id, 'balance' => 0]);

        $transaction = Transaction::factory()->create([
            'trx_id' => 'EXT_DEP_123',
            'user_id' => $user->id,
            'status' => TrxStatus::PENDING,
            'wallet_reference' => $wallet->uuid,
            'amount' => 100,
            'fee' => 2,
            'provider' => ProviderType::MANUAL->value
        ]);

        $dto = new WebhookDTO(
            providerTransactionId: 'EXT_DEP_123',
            externalReference: null,
            status: 'PAID',
            amount: 100,
            currency: 'USD'
        );

        $service = app(TransactionService::class);
        $service->processModernWebhook($dto, ProviderType::MANUAL);

        $transaction->refresh();
        $this->assertEquals(TrxStatus::COMPLETED, $transaction->status);

        // Wallet receives the net amount after platform fee.
        $this->assertEquals(98, $wallet->refresh()->balance);

        // System Revenue received 2
        $revenueWallet = TenantBypass::run(fn () => Wallet::where('uuid', \App\Enums\SystemWalletUUID::SYSTEM_REVENUE->value)->first());
        $this->assertEquals(2, $revenueWallet->balance);
    }

    public function test_duplicate_paid_webhook_is_idempotent()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id, 'balance' => 0]);

        $transaction = Transaction::factory()->create([
            'trx_id' => 'EXT_DEP_456',
            'user_id' => $user->id,
            'status' => TrxStatus::PENDING,
            'wallet_reference' => $wallet->uuid,
            'amount' => 50,
            'fee' => 0,
        ]);

        $dto = new WebhookDTO('EXT_DEP_456', null, 'PAID', 50, 'USD');
        $service = app(TransactionService::class);
        
        // First execution
        $service->processModernWebhook($dto, ProviderType::MANUAL);
        $this->assertEquals(50, $wallet->refresh()->balance);

        // Second execution (duplicate)
        $service->processModernWebhook($dto, ProviderType::MANUAL);
        $this->assertEquals(50, $wallet->refresh()->balance); // unchanged
    }

    public function test_refund_webhook_generates_refund_transaction_idempotently()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id, 'balance' => 100]);

        $transaction = Transaction::factory()->create([
            'trx_id' => 'EXT_DEP_REF',
            'user_id' => $user->id,
            'status' => TrxStatus::COMPLETED,
            'wallet_reference' => $wallet->uuid,
            'amount' => 100,
            'fee' => 0,
        ]);

        $dto = new WebhookDTO('EXT_DEP_REF', null, 'REFUNDED', 100, 'USD', null, ['refund_id' => 'REF_111']);
        
        $service = app(TransactionService::class);
        $service->processModernWebhook($dto, ProviderType::MANUAL);

        $refundCount = TenantBypass::run(fn () => Transaction::where('trx_type', TrxType::REFUND)->count());
        $this->assertEquals(1, $refundCount);

        // Second duplicate webhook
        $service->processModernWebhook($dto, ProviderType::MANUAL);
        
        $refundCountAfter = TenantBypass::run(fn () => Transaction::where('trx_type', TrxType::REFUND)->count());
        $this->assertEquals(1, $refundCountAfter); // No duplicates
    }

    public function test_chargeback_webhook_generates_chargeback_idempotently()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id, 'balance' => 0]);

        $transaction = Transaction::factory()->create([
            'trx_id' => 'EXT_DEP_CHG',
            'user_id' => $user->id,
            'status' => TrxStatus::COMPLETED,
            'wallet_reference' => $wallet->uuid,
            'amount' => 100,
            'fee' => 0,
        ]);

        $dto = new WebhookDTO('EXT_DEP_CHG', null, 'CHARGEBACK', 100, 'USD', null, ['dispute_id' => 'DISP_111']);
        
        $service = app(TransactionService::class);
        $service->processModernWebhook($dto, ProviderType::MANUAL);

        $chgCount = TenantBypass::run(fn () => Transaction::where('trx_type', TrxType::CHARGEBACK)->count());
        $this->assertEquals(1, $chgCount);

        // Second duplicate webhook
        $service->processModernWebhook($dto, ProviderType::MANUAL);
        
        $chgCountAfter = TenantBypass::run(fn () => Transaction::where('trx_type', TrxType::CHARGEBACK)->count());
        $this->assertEquals(1, $chgCountAfter); // No duplicates
    }
}
