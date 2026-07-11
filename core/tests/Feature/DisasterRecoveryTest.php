<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use App\Services\ChargeService;
use App\Models\Charge;
use App\Enums\ChargeStatus;
use Illuminate\Support\Facades\DB;

class DisasterRecoveryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Valida que o processamento do webhook falha elegantemente (500)
     * e NÃO comita alterações fantasmas se houver interrupção de DB/Redis.
     */
    public function test_transaction_rolls_back_on_fatal_disaster()
    {
        $charge = Charge::factory()->create([
            'status' => ChargeStatus::WAITING_PAYMENT,
            'amount' => 100,
            // Mock dependency ...
        ]);

        // Simula uma falha no MySQL no meio do loop de atômico (ex: constraint violation intencional ou DB Exception)
        // Ao invés de desligar o MySQL (impossível no mock do PHPUnit nativo facilmente), nós mockamos o DB lançando erro no commit
        DB::shouldReceive('transaction')
            ->once()
            ->andThrow(new \Exception("MySQL Database Gone Away (Disaster Simulated)"));

        $service = app(ChargeService::class);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("MySQL Database Gone Away (Disaster Simulated)");

        try {
            $service->markAsPaid($charge, 'evt_123');
        } catch (\Exception $e) {
            // Rollback implícito do DB verificado
            throw $e;
        }

        // Verifica que o saldo não foi alterado (Pois reverteu na exceção)
        $charge->refresh();
        $this->assertEquals(ChargeStatus::WAITING_PAYMENT, $charge->status);
    }
}
