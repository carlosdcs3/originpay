<?php

namespace App\Support\Observability\Metrics;

interface MetricsStore
{
    /** @param array<string, string> $labels */
    public function increment(string $metric, array $labels, int|float $value = 1): void;

    /** @param array<string, string> $labels */
    public function observe(string $metric, array $labels, int|float $value): void;

    /** @param array<string, string> $labels */
    public function gauge(string $metric, array $labels, int|float $value): void;

    /** @return array{counters: array<string, array<string, int|float>>, distributions: array<string, array<string, array{count: int, sum: float, min: float|null, max: float|null}>>, gauges: array<string, array<string, int|float>>} */
    public function snapshot(): array;

    public function reset(): void;
}
