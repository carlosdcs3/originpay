<?php

namespace App\Services\Metrics;

use App\Contracts\Metrics\OperationalMetricsServiceInterface;

class NullMetricsDriver implements OperationalMetricsServiceInterface
{
    /**
     * Increment a metric counter (Silent operation).
     */
    public function increment(string $metric, int $value = 1, array $tags = []): void
    {
        // No-op for silent observability. Will be replaced by Datadog/Prometheus driver in Phase 6.
    }

    /**
     * Record a value in a histogram (Silent operation).
     */
    public function histogram(string $metric, float $value, array $tags = []): void
    {
        // No-op for silent observability. Will be replaced by Datadog/Prometheus driver in Phase 6.
    }
}
