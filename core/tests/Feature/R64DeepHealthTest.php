<?php

namespace Tests\Feature;

use App\Models\WebhookDeadLetter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class R64DeepHealthTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'configured-monitor-token';

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('app.monitor_token', self::TOKEN);
        Config::set('queue.default', 'database');
        Config::set('queue.connections.database.table', 'jobs');
        Cache::put('originpay:scheduler:last_heartbeat_at', now()->toISOString(), 600);
    }

    public function test_deep_health_rejects_invalid_monitor_token(): void
    {
        $response = $this->getJson('/api/health/deep', [
            'X-Monitor-Token' => 'wrong-token',
        ]);

        $response->assertStatus(401)
            ->assertJson(['error' => 'Unauthorized'])
            ->assertJsonMissing([self::TOKEN]);
    }

    public function test_deep_health_response_is_sanitized_and_reports_scheduler_heartbeat(): void
    {
        $response = $this->getJson('/api/health/deep', [
            'X-Monitor-Token' => self::TOKEN,
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'UP')
            ->assertJsonPath('service', 'originpay')
            ->assertJsonPath('checks.failed_jobs', 'OK')
            ->assertJsonPath('checks.queue_backlog', 'OK')
            ->assertJsonPath('checks.dlq', 'OK')
            ->assertJsonPath('checks.scheduler_freshness', 'OK')
            ->assertJsonStructure([
                'status',
                'service',
                'checked_at',
                'checks' => [
                    'failed_jobs',
                    'queue_backlog',
                    'dlq',
                    'scheduler_freshness',
                ],
                'details' => [
                    'failed_jobs' => ['count'],
                    'queue_backlog' => ['count'],
                    'dlq' => ['pending_count', 'oldest_pending_age_seconds'],
                ],
            ]);

        $body = $response->getContent();

        $this->assertStringNotContainsString('select ', strtolower($body));
        $this->assertStringNotContainsString('exception', strtolower($body));
        $this->assertStringNotContainsString('payload', strtolower($body));
        $this->assertStringNotContainsString('secret', strtolower($body));
        $this->assertStringNotContainsString(base_path(), $body);
    }

    public function test_failed_jobs_are_counted_without_returning_payload_or_exception_text(): void
    {
        DB::table('failed_jobs')->insert([
            'uuid' => 'failed-job-1',
            'connection' => 'database',
            'queue' => 'default',
            'payload' => '{"tenant":"sensitive","secret":"hidden"}',
            'exception' => 'Stack trace /very/secret/path SQL select * from users',
            'failed_at' => now(),
        ]);

        $response = $this->getJson('/api/health/deep', [
            'X-Monitor-Token' => self::TOKEN,
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'DEGRADED')
            ->assertJsonPath('checks.failed_jobs', 'WARN')
            ->assertJsonPath('details.failed_jobs.count', 1)
            ->assertJsonMissing(['tenant', 'sensitive', 'Stack trace', '/very/secret/path']);
    }

    public function test_queue_backlog_is_read_without_modifying_queue_or_dispatching_jobs(): void
    {
        Bus::fake();

        DB::table('jobs')->insert([
            'queue' => 'default',
            'payload' => '{"displayName":"SensitiveJob"}',
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => now()->timestamp,
            'created_at' => now()->timestamp,
        ]);

        $before = DB::table('jobs')->count();

        $response = $this->getJson('/api/health/deep', [
            'X-Monitor-Token' => self::TOKEN,
        ]);

        $response->assertOk()
            ->assertJsonPath('checks.queue_backlog', 'OK')
            ->assertJsonPath('details.queue_backlog.count', 1)
            ->assertJsonMissing(['SensitiveJob']);

        $this->assertSame($before, DB::table('jobs')->count());
        Bus::assertNothingDispatched();
    }

    public function test_dlq_returns_only_sanitized_count_and_oldest_age(): void
    {
        WebhookDeadLetter::create([
            'gateway_code' => 'tenant-gateway',
            'payload' => ['tenant_id' => 'tenant-123', 'secret' => 'hidden'],
            'headers' => ['Authorization' => 'Bearer hidden'],
            'signature' => 'signature-secret',
            'error_message' => 'raw provider error with tenant data',
            'status' => 'pending',
            'received_at' => now()->subMinutes(5),
        ]);

        $response = $this->getJson('/api/health/deep', [
            'X-Monitor-Token' => self::TOKEN,
        ]);

        $response->assertOk()
            ->assertJsonPath('checks.dlq', 'WARN')
            ->assertJsonPath('details.dlq.pending_count', 1)
            ->assertJsonMissing(['tenant-gateway', 'tenant-123', 'signature-secret', 'raw provider error']);

        $this->assertIsInt($response->json('details.dlq.oldest_pending_age_seconds'));
    }

    public function test_unavailable_queue_dependency_returns_down_without_internal_details(): void
    {
        Config::set('queue.default', 'missing-deep-health-backend');

        $response = $this->getJson('/api/health/deep', [
            'X-Monitor-Token' => self::TOKEN,
        ]);

        $response->assertStatus(503)
            ->assertJsonPath('status', 'DOWN')
            ->assertJsonPath('checks.queue_backlog', 'ERROR')
            ->assertJsonMissing(['missing-deep-health-backend']);
    }
}
