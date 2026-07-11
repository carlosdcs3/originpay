<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\FinancialAnomaly;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

class AnomalyScannerTest extends TestCase
{
    use RefreshDatabase;

    public function test_scanner_deduplicates_by_fingerprint()
    {
        // First scan
        Artisan::call('anomalies:scan');
        $initialCount = FinancialAnomaly::count();

        // Second scan should not duplicate anomalies
        Artisan::call('anomalies:scan');
        $newCount = FinancialAnomaly::count();

        $this->assertEquals($initialCount, $newCount, "Anomalies should be deduplicated by fingerprint.");
    }

    public function test_resolving_anomaly_preserves_history()
    {
        $anomaly = FinancialAnomaly::create([
            'type' => 'test',
            'severity' => 'LOW',
            'entity_type' => 'global',
            'entity_id' => '1',
            'fingerprint' => 'test:global:1',
            'description' => 'Test',
            'detected_at' => now(),
        ]);

        $this->assertNull($anomaly->resolved_at);

        $anomaly->resolved_at = now();
        $anomaly->resolved_by = 1;
        $anomaly->resolution_notes = 'Fixed manually';
        $anomaly->save();

        $this->assertNotNull($anomaly->resolved_at);
        $this->assertEquals('Fixed manually', $anomaly->resolution_notes);
        
        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);

        FinancialAnomaly::create([
            'type' => 'test',
            'severity' => 'LOW',
            'entity_type' => 'global',
            'entity_id' => '1',
            'fingerprint' => 'test:global:1',
            'description' => 'Test 2',
            'detected_at' => now(),
        ]);
    }

    public function test_score_never_goes_below_zero()
    {
        // Force multiple critical anomalies
        for ($i=0; $i<10; $i++) {
            FinancialAnomaly::create([
                'type' => 'critical_test',
                'severity' => 'CRITICAL',
                'entity_type' => 'test',
                'entity_id' => $i,
                'fingerprint' => "crit:{$i}",
                'description' => 'Test',
                'detected_at' => now(),
            ]);
        }

        $service = app(\App\Services\FinancialHealthScoreService::class);
        $result = $service->calculateScore();

        $this->assertGreaterThanOrEqual(0, $result['score'], "Score should not be negative.");
    }
}
