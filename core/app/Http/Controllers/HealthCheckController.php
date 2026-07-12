<?php

namespace App\Http\Controllers;

use App\Models\WebhookDeadLetter;
use App\Services\Observability\SchedulerHeartbeat;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

class HealthCheckController extends Controller
{
    /**
     * Liveness probe: Checks if the application is up and running.
     * Fast and light, no external dependencies checked.
     */
    public function live()
    {
        return response()->json(['status' => 'UP']);
    }

    /**
     * Readiness probe: Checks if the application is ready to handle traffic.
     * Verifies critical dependencies like DB, Redis and Storage.
     * Protected by X-Monitor-Token header.
     */
    public function ready(Request $request)
    {
        $token = $request->header('X-Monitor-Token');
        $expectedToken = config('app.monitor_token');

        if (! is_string($expectedToken) || $expectedToken === '') {
            return response()->json([
                'status' => 'DOWN',
                'checks' => [
                    'monitor_token' => 'ERROR',
                ],
            ], 503);
        }

        if (! $this->hasValidMonitorToken($request)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $status = 'UP';
        $checks = [];

        // 1. Check critical application key configuration
        try {
            $appKey = config('app.key');

            if (! is_string($appKey) || $appKey === '') {
                throw new \RuntimeException('Application key is not configured.');
            }

            $checks['app_key'] = 'OK';
        } catch (Throwable $e) {
            $status = 'DOWN';
            $checks['app_key'] = 'ERROR';
        }

        // 2. Check Database
        try {
            DB::connection()->getPdo();
            $checks['database'] = 'OK';
        } catch (Throwable $e) {
            $status = 'DOWN';
            $checks['database'] = 'ERROR';
        }

        // 4. Check Redis
        try {
            Redis::connection()->ping();
            $checks['redis'] = 'OK';
        } catch (Throwable $e) {
            $status = 'DOWN';
            $checks['redis'] = 'ERROR';
        }

        // 5. Check Queue backend configuration
        try {
            $queueConnection = config('queue.default');
            $queueConnections = config('queue.connections', []);

            if (! is_string($queueConnection) || ! array_key_exists($queueConnection, $queueConnections)) {
                throw new \RuntimeException('Queue backend is not configured.');
            }

            $checks['queue'] = 'OK';
        } catch (Throwable $e) {
            $status = 'DOWN';
            $checks['queue'] = 'ERROR';
        }

        // 6. Check migrations repository table
        try {
            $migrationTable = config('database.migrations.table', 'migrations');

            if (! is_string($migrationTable) || ! Schema::hasTable($migrationTable)) {
                throw new \RuntimeException('Migrations table is not available.');
            }

            $checks['migrations'] = 'OK';
        } catch (Throwable $e) {
            $status = 'DOWN';
            $checks['migrations'] = 'ERROR';
        }

        // 7. Check Storage (local)
        try {
            $disk = Storage::disk('local');
            $testFile = 'health_check_test.txt';
            $disk->put($testFile, 'test');
            $disk->delete($testFile);
            $checks['storage'] = 'OK';
        } catch (Throwable $e) {
            $status = 'DOWN';
            $checks['storage'] = 'ERROR';
        }

        $statusCode = $status === 'UP' ? 200 : 503;

        return response()->json([
            'status' => $status,
            'service' => 'originpay',
            'checked_at' => now()->toISOString(),
            'checks' => $checks,
        ], $statusCode);
    }

    /**
     * Deep operational health: read-only protected checks for operational queues.
     */
    public function deep(Request $request)
    {
        if (! $this->isMonitorTokenConfigured()) {
            return response()->json([
                'status' => 'DOWN',
                'service' => 'originpay',
                'checked_at' => now()->toISOString(),
                'checks' => [
                    'failed_jobs' => 'ERROR',
                    'queue_backlog' => 'ERROR',
                    'dlq' => 'ERROR',
                    'scheduler_freshness' => 'ERROR',
                ],
            ], 503);
        }

        if (! $this->hasValidMonitorToken($request)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $checks = [
            'failed_jobs' => 'OK',
            'queue_backlog' => 'OK',
            'dlq' => 'OK',
            'scheduler_freshness' => 'OK',
        ];
        $dependencyDown = false;
        $details = [
            'failed_jobs' => ['count' => null],
            'queue_backlog' => ['count' => null],
            'dlq' => [
                'pending_count' => null,
                'oldest_pending_age_seconds' => null,
            ],
        ];

        try {
            $failedJobsTable = config('queue.failed.table', 'failed_jobs');
            if (! is_string($failedJobsTable) || ! Schema::hasTable($failedJobsTable)) {
                throw new \RuntimeException('Failed jobs table unavailable.');
            }

            $failedJobsCount = DB::table($failedJobsTable)->count();
            $details['failed_jobs']['count'] = $failedJobsCount;
            $checks['failed_jobs'] = $this->classifyThreshold($failedJobsCount, 'failed_jobs');
        } catch (Throwable $e) {
            $checks['failed_jobs'] = 'ERROR';
            $dependencyDown = true;
        }

        try {
            $queueBacklogCount = $this->readQueueBacklogCount();
            $details['queue_backlog']['count'] = $queueBacklogCount;
            $checks['queue_backlog'] = $this->classifyThreshold($queueBacklogCount, 'queue_backlog');
        } catch (Throwable $e) {
            $checks['queue_backlog'] = 'ERROR';
            $dependencyDown = true;
        }

        try {
            if (! Schema::hasTable('webhook_dead_letters')) {
                throw new \RuntimeException('Webhook DLQ table unavailable.');
            }

            $pendingDlq = WebhookDeadLetter::query()->where('status', 'pending');
            $pendingCount = (clone $pendingDlq)->count();
            $oldestReceivedAt = (clone $pendingDlq)
                ->whereNotNull('received_at')
                ->min('received_at');

            $details['dlq']['pending_count'] = $pendingCount;
            $details['dlq']['oldest_pending_age_seconds'] = $oldestReceivedAt === null
                ? null
                : (int) max(0, now()->diffInSeconds($oldestReceivedAt, true));
            $checks['dlq'] = $this->worstCheckStatus([
                $this->classifyThreshold($pendingCount, 'dlq_count'),
                $details['dlq']['oldest_pending_age_seconds'] === null
                    ? 'OK'
                    : $this->classifyThreshold($details['dlq']['oldest_pending_age_seconds'], 'dlq_oldest_age_seconds'),
            ]);
        } catch (Throwable $e) {
            $checks['dlq'] = 'ERROR';
            $dependencyDown = true;
        }

        try {
            $lastHeartbeatAt = app(SchedulerHeartbeat::class)->lastHeartbeatAt();
            $checks['scheduler_freshness'] = $lastHeartbeatAt === null
                ? 'ERROR'
                : $this->classifyThreshold(
                    (int) max(0, Carbon::parse($lastHeartbeatAt)->diffInSeconds(now(), true)),
                    'scheduler_freshness_seconds'
                );
        } catch (Throwable $e) {
            $checks['scheduler_freshness'] = 'ERROR';
        }

        $status = $this->deepStatusFromChecks($checks, $dependencyDown);

        return response()->json([
            'status' => $status,
            'service' => 'originpay',
            'checked_at' => now()->toISOString(),
            'checks' => $checks,
            'details' => $details,
        ], $this->deepStatusCode($status));
    }

    private function isMonitorTokenConfigured(): bool
    {
        $expectedToken = config('app.monitor_token');

        return is_string($expectedToken) && $expectedToken !== '';
    }

    private function hasValidMonitorToken(Request $request): bool
    {
        $expectedToken = config('app.monitor_token');

        return is_string($expectedToken)
            && $expectedToken !== ''
            && hash_equals($expectedToken, (string) $request->header('X-Monitor-Token'));
    }

    private function readQueueBacklogCount(): int
    {
        $connectionName = config('queue.default');
        $connections = config('queue.connections', []);

        if (! is_string($connectionName) || ! array_key_exists($connectionName, $connections)) {
            throw new \RuntimeException('Queue connection unavailable.');
        }

        $connection = $connections[$connectionName];
        $driver = $connection['driver'] ?? null;

        if ($driver === 'sync' || $driver === 'null') {
            return 0;
        }

        if ($driver === 'database') {
            $table = $connection['table'] ?? 'jobs';
            if (! is_string($table) || ! Schema::hasTable($table)) {
                throw new \RuntimeException('Queue table unavailable.');
            }

            return DB::table($table)->count();
        }

        if ($driver === 'redis') {
            $queue = $connection['queue'] ?? 'default';
            $redisConnection = $connection['connection'] ?? 'default';
            if (! is_string($queue) || ! is_string($redisConnection)) {
                throw new \RuntimeException('Redis queue configuration unavailable.');
            }

            return (int) Redis::connection($redisConnection)->llen('queues:'.$queue);
        }

        throw new \RuntimeException('Queue backend not supported by deep health.');
    }

    private function classifyThreshold(int $value, string $thresholdName): string
    {
        $warn = $this->threshold($thresholdName, 'warn');
        $error = $this->threshold($thresholdName, 'error');

        if ($value >= $error) {
            return 'ERROR';
        }

        if ($value >= $warn) {
            return 'WARN';
        }

        return 'OK';
    }

    private function threshold(string $thresholdName, string $level): int
    {
        $value = config("observability.deep_health.thresholds.{$thresholdName}.{$level}");

        return is_numeric($value) ? max(0, (int) $value) : 0;
    }

    private function worstCheckStatus(array $statuses): string
    {
        if (in_array('ERROR', $statuses, true)) {
            return 'ERROR';
        }

        if (in_array('WARN', $statuses, true)) {
            return 'WARN';
        }

        return 'OK';
    }

    private function deepStatusCode(string $status): int
    {
        return $status === 'DOWN' ? 503 : 200;
    }

    private function deepStatusFromChecks(array $checks, bool $dependencyDown): string
    {
        if ($dependencyDown) {
            return 'DOWN';
        }

        foreach ($checks as $check) {
            if ($check === 'ERROR') {
                return 'DEGRADED';
            }
        }

        foreach ($checks as $check) {
            if ($check === 'WARN') {
                return 'DEGRADED';
            }
        }

        return 'UP';
    }
}
