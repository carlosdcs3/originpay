<?php

namespace Tests\Feature;

use App\Support\Observability\Metrics\LocalMetricsCollector;
use App\Support\Observability\Metrics\MetricsStore;
use App\Support\Observability\Metrics\NoOpMetricsStore;
use App\Support\Observability\Metrics\RedisMetricsStore;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class R610RedisMetricsInfrastructureTest extends TestCase
{
    public function test_unavailable_redis_remains_fail_open_with_safe_fallback(): void
    {
        config(['observability.metrics_baseline.backend' => 'redis']);
        $this->app->forgetInstance(MetricsStore::class);
        $store = $this->app->make(MetricsStore::class);

        if (! class_exists(\Redis::class)) {
            $this->assertInstanceOf(NoOpMetricsStore::class, $store);
        }

        $collector = new LocalMetricsCollector($store);
        $collector->increment('fail_open_total', ['result' => 'success']);
        $this->assertIsArray($collector->snapshot());
    }

    public function test_real_redis_persistence_ttl_atomicity_namespace_reconnect_and_basic_concurrency(): void
    {
        $redis = $this->realRedisOrSkip();
        $suffix = bin2hex(random_bytes(6));
        $namespaceA = 'originpay:test:a:'.$suffix;
        $namespaceB = 'originpay:test:b:'.$suffix;
        $metric = 'counter_total';

        $first = new RedisMetricsStore($redis, 30, $namespaceA);
        $second = new RedisMetricsStore($redis, 30, $namespaceA);
        $isolated = new RedisMetricsStore($redis, 30, $namespaceB);

        try {
            $first->increment($metric, ['result' => 'success']);
            $second->increment($metric, ['result' => 'success'], 2);
            $isolated->increment($metric, ['result' => 'success'], 7);

            $this->assertSame(3.0, $second->snapshot()['counters'][$metric]['result=success']);
            $this->assertSame(7.0, $isolated->snapshot()['counters'][$metric]['result=success']);
            $this->assertGreaterThan(0, (int) $redis->ttl($namespaceA.':counter:'.$metric));
            $this->assertLessThanOrEqual(30, (int) $redis->ttl($namespaceA.':counter:'.$metric));

            for ($i = 0; $i < 100; $i++) {
                ($i % 2 === 0 ? $first : $second)->increment($metric, ['result' => 'success']);
            }
            $this->assertSame(103.0, $first->snapshot()['counters'][$metric]['result=success']);

            $redis->disconnect();
            $second->increment($metric, ['result' => 'success']);
            $this->assertSame(104.0, $second->snapshot()['counters'][$metric]['result=success']);
        } finally {
            try {
                $first->reset();
                $isolated->reset();
            } catch (\Throwable) {
            }
        }
    }

    private function realRedisOrSkip(): object
    {
        if (! class_exists(\Redis::class)) {
            $this->markTestSkipped('Extensão Redis indisponível no PHP canônico.');
        }

        try {
            $redis = Redis::connection(config('observability.metrics_baseline.redis_connection'));
            $redis->ping();

            return $redis;
        } catch (\Throwable) {
            $this->markTestSkipped('Servidor Redis indisponível.');
        }
    }
}
