<?php

namespace Tests\Feature;

use App\Models\Charge;
use App\Models\Transaction;
use App\Models\WebhookDeadLetter;
use App\Services\Observability\SchedulerHeartbeat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class R65DeepHealthThresholdsAndSchedulerTest extends TestCase
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

    public function test_default_thresholds_are_applied(): void
    {
        $this->insertFailedJobs(1);

        $response = $this->deepHealth();

        $response->assertOk()
            ->assertJsonPath('status', 'DEGRADED')
            ->assertJsonPath('checks.failed_jobs', 'WARN')
            ->assertJsonPath('checks.scheduler_freshness', 'OK')
            ->assertJsonMissing(['ORIGINPAY_DEEP_HEALTH_FAILED_JOBS_WARN', 'originpay:scheduler:last_heartbeat_at']);
    }

    public function test_thresholds_can_be_overridden_by_config(): void
    {
        Config::set('observability.deep_health.thresholds.failed_jobs.warn', 3);
        Config::set('observability.deep_health.thresholds.failed_jobs.error', 5);
        $this->insertFailedJobs(2);

        $response = $this->deepHealth();

        $response->assertOk()
            ->assertJsonPath('checks.failed_jobs', 'OK');
    }

    public function test_failed_jobs_changes_from_ok_to_warn_and_error_by_threshold(): void
    {
        Config::set('observability.deep_health.thresholds.failed_jobs.warn', 2);
        Config::set('observability.deep_health.thresholds.failed_jobs.error', 4);

        $this->deepHealth()->assertJsonPath('checks.failed_jobs', 'OK');

        $this->insertFailedJobs(2);
        $this->deepHealth()->assertJsonPath('checks.failed_jobs', 'WARN');

        $this->insertFailedJobs(2, 2);
        $this->deepHealth()->assertJsonPath('checks.failed_jobs', 'ERROR');
    }

    public function test_queue_backlog_changes_from_ok_to_warn_and_error_by_threshold(): void
    {
        Config::set('observability.deep_health.thresholds.queue_backlog.warn', 2);
        Config::set('observability.deep_health.thresholds.queue_backlog.error', 4);

        $this->deepHealth()->assertJsonPath('checks.queue_backlog', 'OK');

        $this->insertQueuedJobs(2);
        $this->deepHealth()->assertJsonPath('checks.queue_backlog', 'WARN');

        $this->insertQueuedJobs(2, 2);
        $this->deepHealth()->assertJsonPath('checks.queue_backlog', 'ERROR');
    }

    public function test_dlq_count_and_oldest_age_respect_thresholds(): void
    {
        Config::set('observability.deep_health.thresholds.dlq_count.warn', 2);
        Config::set('observability.deep_health.thresholds.dlq_count.error', 4);
        Config::set('observability.deep_health.thresholds.dlq_oldest_age_seconds.warn', 60);
        Config::set('observability.deep_health.thresholds.dlq_oldest_age_seconds.error', 120);

        WebhookDeadLetter::create([
            'gateway_code' => 'efi',
            'payload' => ['secret' => 'hidden'],
            'status' => 'pending',
            'received_at' => now()->subSeconds(70),
        ]);

        $this->deepHealth()
            ->assertJsonPath('checks.dlq', 'WARN')
            ->assertJsonPath('details.dlq.pending_count', 1)
            ->assertJsonMissing(['hidden']);

        WebhookDeadLetter::query()->delete();
        WebhookDeadLetter::create([
            'gateway_code' => 'efi',
            'payload' => ['secret' => 'hidden'],
            'status' => 'pending',
            'received_at' => now()->subSeconds(130),
        ]);

        $this->deepHealth()->assertJsonPath('checks.dlq', 'ERROR');
    }

    public function test_current_scheduler_heartbeat_returns_ok(): void
    {
        app(SchedulerHeartbeat::class)->record();

        $this->deepHealth()
            ->assertOk()
            ->assertJsonPath('checks.scheduler_freshness', 'OK');
    }

    public function test_late_scheduler_heartbeat_returns_warn(): void
    {
        Config::set('observability.deep_health.thresholds.scheduler_freshness_seconds.warn', 60);
        Config::set('observability.deep_health.thresholds.scheduler_freshness_seconds.error', 120);
        Cache::put('originpay:scheduler:last_heartbeat_at', now()->subSeconds(70)->toISOString(), 600);

        $this->deepHealth()
            ->assertOk()
            ->assertJsonPath('status', 'DEGRADED')
            ->assertJsonPath('checks.scheduler_freshness', 'WARN');
    }

    public function test_very_late_or_missing_scheduler_heartbeat_returns_error(): void
    {
        Config::set('observability.deep_health.thresholds.scheduler_freshness_seconds.warn', 60);
        Config::set('observability.deep_health.thresholds.scheduler_freshness_seconds.error', 120);
        Cache::put('originpay:scheduler:last_heartbeat_at', now()->subSeconds(130)->toISOString(), 600);

        $this->deepHealth()
            ->assertOk()
            ->assertJsonPath('status', 'DEGRADED')
            ->assertJsonPath('checks.scheduler_freshness', 'ERROR');

        Cache::forget('originpay:scheduler:last_heartbeat_at');

        $this->deepHealth()
            ->assertOk()
            ->assertJsonPath('status', 'DEGRADED')
            ->assertJsonPath('checks.scheduler_freshness', 'ERROR');
    }

    public function test_heartbeat_execution_does_not_create_financial_operation(): void
    {
        $chargesBefore = Charge::query()->count();
        $transactionsBefore = Transaction::query()->count();

        app(SchedulerHeartbeat::class)->record();

        $this->assertSame($chargesBefore, Charge::query()->count());
        $this->assertSame($transactionsBefore, Transaction::query()->count());
    }

    public function test_cache_failure_is_sanitized_and_does_not_expose_internal_details(): void
    {
        Config::set('observability.deep_health.scheduler_heartbeat.store', 'missing-cache-store');

        $response = $this->deepHealth();

        $response->assertOk()
            ->assertJsonPath('status', 'DEGRADED')
            ->assertJsonPath('checks.scheduler_freshness', 'ERROR')
            ->assertJsonMissing(['missing-cache-store', 'originpay:scheduler:last_heartbeat_at', 'InvalidArgumentException']);
    }

    private function deepHealth(): TestResponse
    {
        return $this->getJson('/api/health/deep', [
            'X-Monitor-Token' => self::TOKEN,
        ]);
    }

    private function insertFailedJobs(int $count, int $start = 0): void
    {
        for ($i = $start; $i < $start + $count; $i++) {
            DB::table('failed_jobs')->insert([
                'uuid' => 'failed-job-r65-'.$i,
                'connection' => 'database',
                'queue' => 'default',
                'payload' => '{"secret":"hidden"}',
                'exception' => 'Sensitive exception text',
                'failed_at' => now(),
            ]);
        }
    }

    private function insertQueuedJobs(int $count, int $start = 0): void
    {
        for ($i = $start; $i < $start + $count; $i++) {
            DB::table('jobs')->insert([
                'queue' => 'default',
                'payload' => '{"displayName":"SensitiveJob"}',
                'attempts' => 0,
                'reserved_at' => null,
                'available_at' => now()->timestamp,
                'created_at' => now()->timestamp,
            ]);
        }
    }
}
