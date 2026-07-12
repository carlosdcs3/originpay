<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class R6HealthCheckTest extends TestCase
{
    public function test_readiness_fails_closed_when_monitor_token_is_not_configured(): void
    {
        Config::set('app.monitor_token', null);

        $response = $this->getJson('/api/health/ready', [
            'X-Monitor-Token' => 'default-secret-token',
        ]);

        $response->assertStatus(503)
            ->assertJson([
                'status' => 'DOWN',
                'checks' => [
                    'monitor_token' => 'ERROR',
                ],
            ])
            ->assertJsonMissing(['default-secret-token']);
    }

    public function test_readiness_rejects_invalid_monitor_token_without_leaking_expected_value(): void
    {
        Config::set('app.monitor_token', 'configured-monitor-token');

        $response = $this->getJson('/api/health/ready', [
            'X-Monitor-Token' => 'wrong-token',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Unauthorized',
            ])
            ->assertJsonMissing(['configured-monitor-token']);
    }

    public function test_readiness_reports_queue_backend_configuration_state(): void
    {
        Config::set('app.monitor_token', 'configured-monitor-token');
        Config::set('queue.default', 'missing-readiness-connection');

        $response = $this->getJson('/api/health/ready', [
            'X-Monitor-Token' => 'configured-monitor-token',
        ]);

        $response->assertStatus(503)
            ->assertJsonPath('status', 'DOWN')
            ->assertJsonPath('checks.queue', 'ERROR')
            ->assertJsonMissing(['missing-readiness-connection']);
    }

    public function test_readiness_reports_migrations_repository_state(): void
    {
        Config::set('app.monitor_token', 'configured-monitor-token');
        Config::set('database.migrations.table', 'missing_readiness_migrations_table');

        $response = $this->getJson('/api/health/ready', [
            'X-Monitor-Token' => 'configured-monitor-token',
        ]);

        $response->assertStatus(503)
            ->assertJsonPath('status', 'DOWN')
            ->assertJsonPath('checks.migrations', 'ERROR')
            ->assertJsonMissing(['missing_readiness_migrations_table']);
    }

    public function test_readiness_reports_missing_application_key_without_exposing_secret_names_or_values(): void
    {
        Config::set('app.monitor_token', 'configured-monitor-token');
        Config::set('app.key', null);

        $response = $this->getJson('/api/health/ready', [
            'X-Monitor-Token' => 'configured-monitor-token',
        ]);

        $response->assertStatus(503)
            ->assertJsonPath('status', 'DOWN')
            ->assertJsonPath('checks.app_key', 'ERROR')
            ->assertJsonStructure([
                'status',
                'checks',
                'service',
                'checked_at',
            ])
            ->assertJsonMissing(['APP_KEY']);
    }

    public function test_readiness_does_not_fail_globally_when_optional_efi_gateway_config_is_missing(): void
    {
        Config::set('app.monitor_token', 'configured-monitor-token');
        Config::set('app.key', 'base64:'.base64_encode(str_repeat('r', 32)));
        Config::set('services.efi.client_id', null);
        Config::set('services.efi.client_secret', null);
        Config::set('services.efi.pix_key', null);
        Config::set('services.efi.certificate_path', null);
        Redis::shouldReceive('connection->ping')->once()->andReturn('PONG');

        $response = $this->getJson('/api/health/ready', [
            'X-Monitor-Token' => 'configured-monitor-token',
        ]);

        $response->assertJsonMissingPath('checks.gateway_efi')
            ->assertJsonMissing(['EFI_CLIENT_SECRET'])
            ->assertJsonMissing(['client_secret'])
            ->assertJsonMissing(['pix_key'])
            ->assertJsonMissing(['certificate_path']);
    }
}
