<?php

namespace App\Support\Observability\Metrics;

class NoOpMetricsStore implements MetricsStore
{
    public function increment(string $metric, array $labels, int|float $value = 1): void {}

    public function observe(string $metric, array $labels, int|float $value): void {}

    public function gauge(string $metric, array $labels, int|float $value): void {}

    public function snapshot(): array
    {
        return ['counters' => [], 'distributions' => [], 'gauges' => []];
    }

    public function reset(): void {}
}
