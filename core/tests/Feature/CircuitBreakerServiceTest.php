<?php

namespace Tests\Feature;

use App\Services\CircuitBreakerService;
use App\Services\GatewayHealthScoreService;
use App\Services\GatewayMetricsService;
use App\Services\PlatformAlertService;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class CircuitBreakerServiceTest extends TestCase
{
    protected CircuitBreakerService $service;
    protected string $gateway = 'test_gateway';

    protected function setUp(): void
    {
        parent::setUp();

        app()->instance('redis', new CircuitBreakerRedisFake());
        Facade::clearResolvedInstance('redis');
        Redis::connection()->flushdb();

        $alertService = $this->mock(PlatformAlertService::class);
        $alertService->shouldReceive('createAlert')->andReturn(true);

        $metricsService = $this->mock(GatewayMetricsService::class);
        $metricsService->shouldReceive('increment')->andReturn(true);

        $healthScoreService = $this->mock(GatewayHealthScoreService::class);
        $healthScoreService->shouldIgnoreMissing();

        $this->service = new CircuitBreakerService($alertService, $metricsService, $healthScoreService);
    }

    public function test_circuit_starts_closed(): void
    {
        $this->assertEquals(CircuitBreakerService::STATE_CLOSED, $this->service->getState($this->gateway));
    }

    public function test_nineteen_failures_keep_circuit_closed(): void
    {
        for ($i = 0; $i < 19; $i++) {
            $this->service->recordFailure($this->gateway, new \Exception('Simulated error'));
        }

        $this->assertEquals(CircuitBreakerService::STATE_CLOSED, $this->service->getState($this->gateway));
    }

    public function test_twentieth_failure_opens_circuit(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $this->service->recordFailure($this->gateway, new \Exception('Simulated error'));
        }

        $this->assertEquals(CircuitBreakerService::STATE_OPEN, $this->service->getState($this->gateway));
    }

    public function test_cooldown_allows_half_open(): void
    {
        $this->openCircuitAndExpireCooldown();

        $this->assertEquals(CircuitBreakerService::STATE_HALF_OPEN, $this->service->getState($this->gateway));
        $this->assertTrue($this->service->attemptRequest($this->gateway));
    }

    public function test_success_in_half_open_closes_circuit(): void
    {
        $this->openCircuitAndExpireCooldown();

        $this->assertTrue($this->service->attemptRequest($this->gateway));
        $this->service->recordSuccess($this->gateway);

        $this->assertEquals(CircuitBreakerService::STATE_CLOSED, $this->service->getState($this->gateway));
    }

    public function test_failure_in_half_open_reopens_circuit(): void
    {
        $this->openCircuitAndExpireCooldown();

        $this->assertTrue($this->service->attemptRequest($this->gateway));
        $this->service->recordFailure($this->gateway, new \Exception('Half open failure'));

        $this->assertEquals(CircuitBreakerService::STATE_OPEN, $this->service->getState($this->gateway));
        $this->assertFalse($this->service->attemptRequest($this->gateway));
    }

    private function openCircuitAndExpireCooldown(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $this->service->recordFailure($this->gateway, new \Exception('Simulated error'));
        }

        $this->assertEquals(CircuitBreakerService::STATE_OPEN, $this->service->getState($this->gateway));

        Redis::connection()->del("gateway:circuit:{$this->gateway}:open");
    }
}

class CircuitBreakerRedisFake
{
    private array $values = [];
    private array $sets = [];

    public function connection(): self
    {
        return $this;
    }

    public function flushdb(): void
    {
        $this->values = [];
        $this->sets = [];
    }

    public function exists(string $key): bool
    {
        return array_key_exists($key, $this->values) || array_key_exists($key, $this->sets);
    }

    public function incr(string $key): int
    {
        $this->values[$key] = (int) ($this->values[$key] ?? 0) + 1;

        return $this->values[$key];
    }

    public function expire(string $key, int $seconds): bool
    {
        return true;
    }

    public function get(string $key): mixed
    {
        return $this->values[$key] ?? null;
    }

    public function setex(string $key, int $seconds, string $value): bool
    {
        $this->values[$key] = $value;

        return true;
    }

    public function del(string $key): int
    {
        unset($this->values[$key], $this->sets[$key]);

        return 1;
    }

    public function zadd(string $key, int $score, string $member): int
    {
        $this->sets[$key][$member] = $score;

        return 1;
    }

    public function zremrangebyscore(string $key, string|int $min, string|int $max): int
    {
        return 0;
    }

    public function zcount(string $key, string|int $min, string|int $max): int
    {
        return count($this->sets[$key] ?? []);
    }

    public function zrange(string $key, int $start, int $stop): array
    {
        return array_keys($this->sets[$key] ?? []);
    }
}
