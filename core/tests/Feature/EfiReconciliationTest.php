<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use App\Models\FinancialAnomaly;
use Illuminate\Support\Facades\Http;
use App\Models\Transaction;
use App\Enums\TrxStatus;

class EfiReconciliationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.efi.env' => 'sandbox',
            'services.efi.client_id' => 'fake_client',
            'services.efi.client_secret' => 'fake_secret',
            'services.efi.certificate_path' => 'storage/app/private/efi/test.pem',
        ]);
    }

    public function test_reconcile_command_detects_missing_local_transaction()
    {
        Http::fake([
            '*oauth/token' => Http::response(['access_token' => 'fake_token'], 200),
            '*v2/cob*' => Http::response([
                'cobs' => [
                    [
                        'txid' => 'GHOST_TXID',
                        'status' => 'CONCLUIDA',
                        'valor' => ['original' => '100.00']
                    ]
                ]
            ], 200),
        ]);

        Artisan::call('reconcile:efi');

        $anomaly = FinancialAnomaly::where('type', 'efi_missing_local')->first();
        $this->assertNotNull($anomaly);
        $this->assertEquals('HIGH', $anomaly->severity);
    }

    public function test_reconcile_command_detects_status_mismatch()
    {
        Transaction::factory()->create([
            'trx_id' => 'LOCAL_TXID',
            'status' => TrxStatus::PENDING,
            'amount' => 50.00
        ]);

        Http::fake([
            '*oauth/token' => Http::response(['access_token' => 'fake_token'], 200),
            '*v2/cob*' => Http::response([
                'cobs' => [
                    [
                        'txid' => 'LOCAL_TXID',
                        'status' => 'CONCLUIDA', // Paid on EFI
                        'valor' => ['original' => '50.00']
                    ]
                ]
            ], 200),
        ]);

        Artisan::call('reconcile:efi');

        $anomaly = FinancialAnomaly::where('type', 'efi_status_mismatch')->first();
        $this->assertNotNull($anomaly);
        $this->assertEquals('CRITICAL', $anomaly->severity);
    }
}
