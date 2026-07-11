<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Admin;
use App\Models\User;
use App\Models\WebhookDlq;
use App\Services\GatewayMetricsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;

class SystemObservabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_is_accessible_by_admin()
    {
        $this->actingAs(Admin::factory()->create(), 'admin');

        $response = $this->get(route('admin.system.health.index'));
        $response->assertStatus(200);
        $response->assertViewIs('backend.system.health');
    }

    public function test_metrics_are_incremented_in_cache_windows()
    {
        $metrics = new GatewayMetricsService();
        $metrics->increment('test_metric');

        $this->assertEquals(1, Cache::get('metrics:test_metric:5m'));
        $this->assertEquals(1, Cache::get('metrics:test_metric:15m'));
        $this->assertEquals(1, Cache::get('metrics:test_metric:24h'));
    }

    public function test_sentry_scrubber_masks_sensitive_data()
    {
        // We will simulate Sentry Event payload
        $payload = [
            'data' => [
                'api_key' => 'secret_12345',
                'cpf' => '12345678900',
                'public_info' => 'safe'
            ]
        ];

        // Sentry Event is a class from Sentry SDK. For test purposes we check the MaskHelper logic
        $maskedData = \App\Helpers\MaskHelper::maskSensitiveData($payload['data']);

        $this->assertEquals('***MASKED***', $maskedData['api_key']);
        $this->assertEquals('***MASKED***', $maskedData['cpf']);
        $this->assertEquals('safe', $maskedData['public_info']);
    }

    public function test_alert_disparado_quando_dlq_excede_limite()
    {
        // Mocking the Log to assert it was called
        Log::shouldReceive('channel')->with('gateway')->andReturnSelf();
        Log::shouldReceive('emergency')->once()->withArgs(function($msg) {
            return str_contains($msg, 'DLQ_OVERFLOW');
        });
        Log::shouldReceive('emergency')->withAnyArgs()->zeroOrMoreTimes();
        Log::shouldReceive('alert')->withAnyArgs()->zeroOrMoreTimes();
        
        Log::shouldReceive('info')->zeroOrMoreTimes();

        // Create 101 pending DLQ items
        WebhookDlq::factory()->count(101)->create(['resolved_at' => null]);

        $this->artisan('system:health-check')->assertExitCode(0);
    }
}
