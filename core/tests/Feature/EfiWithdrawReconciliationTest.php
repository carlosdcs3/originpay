<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use App\Models\Transaction;
use App\Models\FinancialAnomaly;
use App\Enums\TrxType;
use App\Enums\TrxStatus;

class EfiWithdrawReconciliationTest extends TestCase
{
    use RefreshDatabase;

    public function test_detects_stuck_withdraw()
    {
        Transaction::factory()->create([
            'trx_id' => 'STUCK_WD_123',
            'trx_type' => TrxType::WITHDRAW,
            'status' => TrxStatus::PENDING,
            'created_at' => now()->subMinutes(15) // Older than 10 mins
        ]);

        Artisan::call('reconcile:efi-withdraws');

        $anomaly = FinancialAnomaly::where('type', 'withdraw_stuck')->first();
        
        $this->assertNotNull($anomaly);
        $this->assertStringContainsString('STUCK_WD_123', $anomaly->description);
    }
}
