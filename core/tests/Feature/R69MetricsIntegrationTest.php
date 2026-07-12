<?php

namespace Tests\Feature;

use App\Http\Middleware\RecordHttpMetrics;
use App\Support\Observability\Metrics\InMemoryMetricsStore;
use App\Support\Observability\Metrics\LocalMetricsCollector;
use App\Support\Observability\Metrics\MetricsStore;
use App\Support\Observability\Metrics\NoOpMetricsStore;
use App\Support\Observability\Metrics\RedisMetricsStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class R69MetricsIntegrationTest extends TestCase
{
    public function test_unavailable_redis_and_invalid_driver_resolve_safe_fallback(): void
    {
        foreach (['redis', 'invalid'] as $backend) {
            config(['observability.metrics_baseline.backend' => $backend]);
            $this->app->forgetInstance(MetricsStore::class);
            $this->app->forgetInstance(LocalMetricsCollector::class);

            $this->assertInstanceOf(NoOpMetricsStore::class, $this->app->make(MetricsStore::class));
            $this->assertInstanceOf(LocalMetricsCollector::class, $this->app->make(LocalMetricsCollector::class));
        }
    }

    public function test_application_and_request_continue_without_redis(): void
    {
        config(['observability.metrics_baseline.backend' => 'redis']);
        $this->app->forgetInstance(MetricsStore::class);
        $this->app->forgetInstance(LocalMetricsCollector::class);

        $this->get('/up')->assertOk();
    }

    public function test_container_binds_configured_store_and_collector(): void
    {
        config(['observability.metrics_baseline.backend' => 'memory']);
        $this->app->forgetInstance(MetricsStore::class);
        $this->assertInstanceOf(InMemoryMetricsStore::class, $this->app->make(MetricsStore::class));
        $this->assertInstanceOf(LocalMetricsCollector::class, $this->app->make(LocalMetricsCollector::class));

        config(['observability.metrics_baseline.backend' => 'redis']);
        $this->app->instance('redis', new class
        {
            public function connection(?string $name = null): object
            {
                return new class {};
            }
        });
        $this->app->forgetInstance(MetricsStore::class);
        $expected = class_exists(\Redis::class) ? RedisMetricsStore::class : NoOpMetricsStore::class;
        $this->assertInstanceOf($expected, $this->app->make(MetricsStore::class));
    }

    public function test_http_request_records_total_status_and_duration_without_secrets(): void
    {
        $store = new InMemoryMetricsStore;
        $collector = new LocalMetricsCollector($store, config('observability.metrics_baseline'));
        $request = Request::create('/health-test?token=secret', 'GET');
        $request->setRouteResolver(fn () => new class
        {
            public function getName(): string
            {
                return 'health.test';
            }
        });

        (new RecordHttpMetrics($collector))->handle($request, fn () => new Response('ok', 201));

        $encoded = json_encode($store->snapshot(), JSON_THROW_ON_ERROR);
        $this->assertStringContainsString('http_requests_total', $encoded);
        $this->assertStringContainsString('status_class=2xx', $encoded);
        $this->assertStringContainsString('http_request_duration_ms', $encoded);
        $this->assertStringNotContainsString('secret', $encoded);
        $this->assertStringNotContainsString('token', $encoded);
    }

    public function test_real_redis_persists_between_instances_and_applies_ttl(): void
    {
        try {
            $redis = Redis::connection(config('observability.metrics_baseline.redis_connection'));
            $redis->ping();
        } catch (\Throwable) {
            $this->markTestSkipped('Redis local indisponível.');
        }

        $metric = 'r69_real_'.bin2hex(random_bytes(4));
        $first = new RedisMetricsStore($redis, 30);
        $second = new RedisMetricsStore($redis, 30);
        $first->increment($metric, ['result' => 'success']);

        $this->assertSame(1.0, $second->snapshot()['counters'][$metric]['result=success']);
        $ttl = (int) $redis->ttl('originpay:metrics:counter:'.$metric);
        $this->assertGreaterThan(0, $ttl);
        $this->assertLessThanOrEqual(30, $ttl);
        $first->reset();
    }
}
