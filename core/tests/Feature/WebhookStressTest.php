<?php

namespace Tests\Feature;

use App\Enums\ChargeStatus;
use App\Models\Charge;
use App\Models\PaymentGateway;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\ChargeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookStressTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Simulates repeated ingestion of the same webhook event to prove DB idempotency.
     */
    public function test_webhook_ingestion_under_high_concurrency(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id, 'balance' => 0]);
        $gateway = PaymentGateway::factory()->create(['code' => 'PAGARME', 'status' => 1]);

        $charge = Charge::factory()->create([
            'user_id' => $user->id,
            'gateway_id' => $gateway->id,
            'amount' => 100,
            'status' => ChargeStatus::WAITING_PAYMENT,
            'wallet_id' => $wallet->id,
        ]);

        $successfulRequests = 0;
        $failedRequests = 0;
        $failureMessages = [];

        for ($i = 0; $i < 50; $i++) {
            try {
                app(ChargeService::class)->markAsPaid($charge, 'evt_12345_pagarme');
                $successfulRequests++;
            } catch (\Throwable $e) {
                $failedRequests++;
                $failureMessages[] = $e->getMessage();
            }
        }

        $charge->refresh();
        $wallet->refresh();
        $transactionsCount = WalletTransaction::where('wallet_id', $wallet->id)->count();

        $this->assertSame(0, $failedRequests, 'Duplicate webhooks must be ignored without fatal exceptions. Errors: ' . implode(' | ', array_unique($failureMessages)));
        $this->assertSame(50, $successfulRequests);
        $this->assertEquals(ChargeStatus::PAID, $charge->status);
        $this->assertEquals(2, $transactionsCount, 'Only the normal pending+settlement pair should exist.');
    }
}
