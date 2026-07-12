<?php

namespace Tests\Feature;

use App\Support\Observability\Metrics\LocalMetricsCollector;
use App\Support\Observability\Metrics\RedisMetricsStore;
use RuntimeException;
use Tests\TestCase;

class R68RedisMetricsStoreTest extends TestCase
{
    public function test_counter_persists_between_instances_and_has_ttl(): void
    {
        $redis = new FakeMetricsRedis;
        $first = new LocalMetricsCollector(new RedisMetricsStore($redis, 3600));
        $second = new LocalMetricsCollector(new RedisMetricsStore($redis, 3600));

        $first->increment('requests_total', ['method' => 'GET']);
        $second->increment('requests_total', ['method' => 'GET'], 2);

        $this->assertSame(3.0, $second->snapshot()['counters']['requests_total']['method=GET']);
        $this->assertSame(3600, $redis->ttls['originpay:metrics:counter:requests_total'] ?? null);
    }

    public function test_redis_failure_does_not_break_flow(): void
    {
        $collector = new LocalMetricsCollector(new RedisMetricsStore(new FailingMetricsRedis, 60));
        $collector->increment('requests_total', ['method' => 'GET']);

        $this->assertSame(['counters' => [], 'distributions' => [], 'gauges' => []], $collector->snapshot());
    }

    public function test_forbidden_labels_are_not_persisted(): void
    {
        $redis = new FakeMetricsRedis;
        $collector = new LocalMetricsCollector(new RedisMetricsStore($redis, 60));
        $collector->increment('authorization_failures_total', ['operation' => 'auth', 'user_id' => '42']);

        $encoded = json_encode($collector->snapshot(), JSON_THROW_ON_ERROR);
        $this->assertStringContainsString('operation=auth', $encoded);
        $this->assertStringNotContainsString('user_id', $encoded);
    }

    public function test_cardinality_limit_continues_working(): void
    {
        $redis = new FakeMetricsRedis;
        $collector = new LocalMetricsCollector(new RedisMetricsStore($redis, 60), [
            'allowed_labels' => ['queue', 'reason'],
            'max_series_per_metric' => 2,
        ]);

        $collector->increment('jobs_total', ['queue' => 'one']);
        $collector->increment('jobs_total', ['queue' => 'two']);
        $collector->increment('jobs_total', ['queue' => 'three']);

        $snapshot = $collector->snapshot();
        $this->assertCount(2, $snapshot['counters']['jobs_total']);
        $this->assertSame(1.0, $snapshot['counters']['metrics_dropped_total']['reason=cardinality_limit']);
    }
}

class FakeMetricsRedis
{
    public array $hashes = [];

    public array $sets = [];

    public array $ttls = [];

    public function hincrbyfloat(string $key, string $field, float $value): float
    {
        return $this->hashes[$key][$field] = ($this->hashes[$key][$field] ?? 0) + $value;
    }

    public function hset(string $key, string $field, float $value): void
    {
        $this->hashes[$key][$field] = $value;
    }

    public function hgetall(string $key): array
    {
        return $this->hashes[$key] ?? [];
    }

    public function sadd(string $key, string $member): void
    {
        $this->sets[$key][$member] = true;
    }

    public function smembers(string $key): array
    {
        return array_keys($this->sets[$key] ?? []);
    }

    public function expire(string $key, int $seconds): void
    {
        $this->ttls[$key] = $seconds;
    }

    public function del(array|string $keys): void
    {
        foreach ((array) $keys as $key) {
            unset($this->hashes[$key], $this->sets[$key], $this->ttls[$key]);
        }
    }
}

class FailingMetricsRedis
{
    public function __call(string $name, array $arguments): never
    {
        throw new RuntimeException('Redis unavailable');
    }
}
