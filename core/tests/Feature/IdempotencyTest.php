<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Wallet;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Models\ProcessedEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use App\Services\WalletService;

class IdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_duplicado_nao_credita_duas_vezes()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id, 'balance' => 0, 'pending_balance' => 0, 'available_balance' => 0]);
        $walletService = app(WalletService::class);
        $idempotencyKey = 'wh_evt_12345';
        $correlationId = 'charge_uuid_999';

        // Simula worker 1
        DB::transaction(function () use ($wallet, $walletService, $idempotencyKey, $correlationId) {
            $processed = ProcessedEvent::where('idempotency_key', $idempotencyKey)->lockForUpdate()->first();
            if (!$processed) {
                ProcessedEvent::create([
                    'idempotency_key' => $idempotencyKey,
                    'event_type' => 'payment.paid',
                    'status' => 'processed'
                ]);
                $walletService->creditPending($wallet, 100, 'Test', null, $correlationId, $idempotencyKey);
            }
        });

        // Simula worker 2 (Webhook duplicado)
        DB::transaction(function () use ($wallet, $walletService, $idempotencyKey, $correlationId) {
            $processed = ProcessedEvent::where('idempotency_key', $idempotencyKey)->lockForUpdate()->first();
            if (!$processed) {
                ProcessedEvent::create([
                    'idempotency_key' => $idempotencyKey,
                    'event_type' => 'payment.paid',
                    'status' => 'processed'
                ]);
                $walletService->creditPending($wallet, 100, 'Test', null, $correlationId, $idempotencyKey);
            }
        });

        $wallet->refresh();
        $this->assertEquals(100, $wallet->pending_balance);
        $this->assertEquals(1, ProcessedEvent::where('idempotency_key', $idempotencyKey)->count());
        $this->assertEquals(1, WalletTransaction::where('idempotency_key', $idempotencyKey)->count());
    }

    public function test_ledger_immutable()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id, 'balance' => 0, 'pending_balance' => 0, 'available_balance' => 0]);
        
        $tx = WalletTransaction::create([
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'type' => 'charge',
            'amount' => 100,
            'balance_before' => 0,
            'balance_after' => 100,
            'description' => 'Test',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Ledger Violation');

        $tx->amount = 200;
        $tx->save(); // Vai explodir
    }
}
