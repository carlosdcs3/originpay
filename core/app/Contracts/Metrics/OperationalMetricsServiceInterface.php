<?php

namespace App\Contracts\Metrics;

interface OperationalMetricsServiceInterface
{
    /**
     * Increment a metric counter.
     *
     * @param string $metric The name of the metric
     * @param int $value The value to increment by (default 1)
     * @param array $tags Additional tags/dimensions for the metric
     */
    public function increment(string $metric, int $value = 1, array $tags = []): void;

    /**
     * Record a value in a histogram (e.g., latency, size).
     *
     * @param string $metric The name of the metric
     * @param float $value The value to record
     * @param array $tags Additional tags/dimensions for the metric
     */
    public function histogram(string $metric, float $value, array $tags = []): void;
}
