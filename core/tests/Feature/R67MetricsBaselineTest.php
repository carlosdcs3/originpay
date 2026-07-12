<?php

namespace Tests\Feature;

use App\Support\Observability\Metrics\InMemoryMetricsStore;
use App\Support\Observability\Metrics\LocalMetricsCollector;
use RuntimeException;
use Tests\TestCase;

class R67MetricsBaselineTest extends TestCase
{
    public function test_collects_requests_by_status_class(): void
    {
        $collector = new LocalMetricsCollector(new InMemoryMetricsStore, $this->config());

        $collector->recordRequest('api.charges.store', 'POST', 201, 125.5, ['correlation_id' => 'abc']);
        $collector->recordRequest('api.charges.store', 'POST', 422, 10.0);
        $collector->recordRequest('api.health.ready', 'GET', 503, 3.0);

        $snapshot = $collector->snapshot();

        $this->assertSame(1, $snapshot['counters']['http_requests_total']['route_name=api.charges.store,method=POST,status_class=2xx']);
        $this->assertSame(1, $snapshot['counters']['http_requests_total']['route_name=api.charges.store,method=POST,status_class=4xx']);
        $this->assertSame(1, $snapshot['counters']['http_requests_total']['route_name=api.health.ready,method=GET,status_class=5xx']);
    }

    public function test_duration_is_aggregated_without_storing_correlation_id(): void
    {
        $collector = new LocalMetricsCollector(new InMemoryMetricsStore, $this->config());

        $collector->recordRequest('api.charges.store', 'POST', 201, 120.25, ['correlation_id' => 'd3ed4b1f-7aaa-4e93-8d84-a8c0f006bbf5']);
        $collector->recordRequest('api.charges.store', 'POST', 200, 80.75, ['correlation_id' => 'another']);

        $snapshot = $collector->snapshot();
        $encoded = json_encode($snapshot, JSON_THROW_ON_ERROR);

        $this->assertSame(2, $snapshot['distributions']['http_request_duration_ms']['route_name=api.charges.store,method=POST,status_class=2xx']['count']);
        $this->assertSame(201.0, $snapshot['distributions']['http_request_duration_ms']['route_name=api.charges.store,method=POST,status_class=2xx']['sum']);
        $this->assertStringNotContainsString('correlation_id', $encoded);
        $this->assertStringNotContainsString('d3ed4b1f', $encoded);
    }

    public function test_financial_events_are_aggregated_without_personal_or_financial_ids(): void
    {
        $collector = new LocalMetricsCollector(new InMemoryMetricsStore, $this->config());

        $collector->recordFinancialEvent('charge_creation', 'stripe', 'success', [
            'payment_id' => 'pay_123',
            'merchant_id' => 'merchant_123',
            'user_id' => 'user_123',
        ]);
        $collector->recordFinancialEvent('webhook_processing', 'stripe', 'failed', ['webhook_event_id' => 'evt_123']);
        $collector->recordFinancialEvent('payment_confirmation', 'cryptomus', 'success', ['correlation_id' => 'cid']);
        $collector->recordFinancialEvent('settlement', 'manual', 'success', ['settlement_id' => 'set_123']);

        $snapshot = $collector->snapshot();
        $encoded = json_encode($snapshot, JSON_THROW_ON_ERROR);

        $this->assertSame(1, $snapshot['counters']['financial_events_total']['gateway=stripe,result=success,operation=charge_creation']);
        $this->assertSame(1, $snapshot['counters']['financial_events_total']['gateway=stripe,result=failed,operation=webhook_processing']);
        $this->assertSame(1, $snapshot['counters']['financial_events_total']['gateway=cryptomus,result=success,operation=payment_confirmation']);
        $this->assertSame(1, $snapshot['counters']['financial_events_total']['gateway=manual,result=success,operation=settlement']);
        $this->assertStringNotContainsString('pay_123', $encoded);
        $this->assertStringNotContainsString('merchant_123', $encoded);
        $this->assertStringNotContainsString('user_123', $encoded);
        $this->assertStringNotContainsString('evt_123', $encoded);
    }

    public function test_labels_outside_allowlist_are_removed(): void
    {
        $collector = new LocalMetricsCollector(new InMemoryMetricsStore, $this->config());

        $collector->increment('authorization_failures_total', [
            'operation' => 'admin_settlement',
            'user_id' => '1',
            'ip' => '127.0.0.1',
            'exception_message' => 'sensitive failure',
        ]);

        $snapshot = $collector->snapshot();
        $encoded = json_encode($snapshot, JSON_THROW_ON_ERROR);

        $this->assertSame(1, $snapshot['counters']['authorization_failures_total']['operation=admin_settlement']);
        $this->assertStringNotContainsString('user_id', $encoded);
        $this->assertStringNotContainsString('127.0.0.1', $encoded);
        $this->assertStringNotContainsString('sensitive failure', $encoded);
    }

    public function test_no_secret_is_stored(): void
    {
        $collector = new LocalMetricsCollector(new InMemoryMetricsStore, $this->config());

        $collector->increment('api_key_failures_total', [
            'operation' => 'api_auth',
            'api_key' => 'sk_live_secret',
            'authorization' => 'Bearer token',
            'client_secret' => 'secret-value',
        ]);

        $encoded = json_encode($collector->snapshot(), JSON_THROW_ON_ERROR);

        $this->assertStringNotContainsString('sk_live_secret', $encoded);
        $this->assertStringNotContainsString('Bearer token', $encoded);
        $this->assertStringNotContainsString('secret-value', $encoded);
    }

    public function test_cardinality_is_limited(): void
    {
        $collector = new LocalMetricsCollector(new InMemoryMetricsStore, $this->config(maxSeries: 3));

        $collector->gauge('queue_backlog', ['queue' => 'default'], 1);
        $collector->gauge('queue_backlog', ['queue' => 'webhooks'], 1);
        $collector->gauge('queue_backlog', ['queue' => 'settlements'], 1);
        $collector->gauge('queue_backlog', ['queue' => 'overflow'], 1);

        $snapshot = $collector->snapshot();

        $this->assertCount(3, $snapshot['gauges']['queue_backlog']);
        $this->assertSame(1, $snapshot['counters']['metrics_dropped_total']['reason=cardinality_limit']);
    }

    public function test_collection_failure_does_not_break_main_flow(): void
    {
        $collector = new LocalMetricsCollector(new class extends InMemoryMetricsStore
        {
            public function increment(string $metric, array $labels, int|float $value = 1): void
            {
                throw new RuntimeException('store unavailable');
            }
        }, $this->config());

        $collector->increment('rate_limit_events_total', ['route_name' => 'api.charges.store', 'method' => 'POST']);

        $this->assertTrue(true);
    }

    private function config(int $maxSeries = 50): array
    {
        return [
            'allowed_labels' => ['route_name', 'method', 'status_class', 'gateway', 'result', 'queue', 'operation', 'reason'],
            'max_series_per_metric' => $maxSeries,
        ];
    }
}
