<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Enums\TrxStatus;
use App\Enums\TrxType;
use App\Enums\SystemWalletUUID;
use App\Services\TransactionService;
use App\Services\WalletService;
use App\Services\PaymentService;
use App\Services\TransactionNotifierService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Exception;

class FinancialConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_duplicado_nao_credita_duas_vezes()
    {
        // Configuração
        $user = User::factory()->create();
        $systemUser = User::factory()->create(['email' => 'system_general@ledger.internal']);
        Wallet::factory()->create([
            'user_id' => $systemUser->id,
            'uuid' => SystemWalletUUID::SYSTEM_GENERAL->value,
            'balance' => 100000,
            'available_balance' => 100000,
        ]);
        $wallet = Wallet::factory()->create(['user_id' => $user->id, 'balance' => 100]);
        
        $transaction = Transaction::factory()->create([
            'user_id' => $user->id,
            'wallet_reference' => $wallet->uuid,
            'amount' => 50,
            'net_amount' => 50,
            'status' => TrxStatus::PENDING,
            'trx_id' => 'TXN12345',
            'trx_type' => TrxType::DEPOSIT,
        ]);

        $this->mock(TransactionNotifierService::class, function ($mock) {
            $mock->shouldReceive('toUser')->andReturnNull();
            $mock->shouldReceive('toAdmins')->andReturnNull();
        });

        $service = app(TransactionService::class);

        // Processamento 1 (Sucesso)
        $service->completeTransaction('TXN12345');
        
        // Processamento 2 (Webhook Duplicado)
        $service->completeTransaction('TXN12345');

        // Verificação: Saldo subiu apenas 50 (de 100 para 150)
        $wallet->refresh();
        $this->assertEquals(150, $wallet->balance);
    }

    public function test_saque_sem_saldo_falha_com_rollback()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id, 'balance' => 10]);

        $paymentService = app(PaymentService::class);

        // Mock withdraw account...
        $withdrawAccount = (object) [
            'credentials' => [],
            'withdrawMethod' => (object) [
                'type' => \App\Enums\MethodType::MANUAL,
                'charge' => 0,
                'charge_type' => \App\Constants\FixPctType::FIXED,
                'conversion_rate' => 1,
                'name' => 'Test Withdraw',
                'currency' => 'USD'
            ]
        ];

        try {
            // Tenta sacar 50 tendo apenas 10
            $paymentService->withdrawMoney($withdrawAccount, $wallet, 50);
            $this->fail('Deveria ter lançado exceção de saldo insuficiente.');
        } catch (\Exception $e) {
            $this->assertStringContainsString('Falha no processamento de retirada', $e->getMessage());
        }

        $wallet->refresh();
        $this->assertEquals(10, $wallet->balance);
    }
}
