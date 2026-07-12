<?php

namespace App\Support\Observability\Metrics;

use Throwable;

class LocalMetricsCollector
{
    /** @var list<string> */
    private array $allowedLabels;

    private int $maxSeriesPerMetric;

    /** @var array<string, array<string, true>> */
    private array $knownSeries = [];

    /** @param array{allowed_labels?: list<string>, max_series_per_metric?: int} $config */
    public function __construct(
        private readonly MetricsStore $store,
        array $config = []
    ) {
        $this->allowedLabels = $config['allowed_labels'] ?? [
            'route_name',
            'method',
            'status_class',
            'gateway',
            'result',
            'queue',
            'operation',
            'reason',
        ];
        $this->maxSeriesPerMetric = max(1, $config['max_series_per_metric'] ?? 100);
    }

    /** @param array<string, mixed> $labels */
    public function recordRequest(string $routeName, string $method, int $statusCode, int|float $durationMs, array $labels = []): void
    {
        $safeLabels = array_merge($labels, [
            'route_name' => $routeName,
            'method' => strtoupper($method),
            'status_class' => $this->statusClass($statusCode),
        ]);

        $this->increment('http_requests_total', $safeLabels);
        $this->observe('http_request_duration_ms', $safeLabels, max(0, $durationMs));
    }

    /** @param array<string, mixed> $labels */
    public function recordFinancialEvent(string $operation, string $gateway, string $result, array $labels = []): void
    {
        $safeLabels = array_merge($labels, [
            'operation' => $operation,
            'gateway' => $gateway,
            'result' => $result,
        ]);

        $this->increment('financial_events_total', $safeLabels);
    }

    /** @param array<string, mixed> $labels */
    public function increment(string $metric, array $labels = [], int|float $value = 1): void
    {
        $this->capture($metric, $labels, fn (array $safeLabels) => $this->store->increment($metric, $safeLabels, $value));
    }

    /** @param array<string, mixed> $labels */
    public function observe(string $metric, array $labels, int|float $value): void
    {
        $this->capture($metric, $labels, fn (array $safeLabels) => $this->store->observe($metric, $safeLabels, $value));
    }

    /** @param array<string, mixed> $labels */
    public function gauge(string $metric, array $labels, int|float $value): void
    {
        $this->capture($metric, $labels, fn (array $safeLabels) => $this->store->gauge($metric, $safeLabels, $value));
    }

    public function snapshot(): array
    {
        try {
            return $this->store->snapshot();
        } catch (Throwable) {
            return ['counters' => [], 'distributions' => [], 'gauges' => []];
        }
    }

    public function reset(): void
    {
        try {
            $this->knownSeries = [];
            $this->store->reset();
        } catch (Throwable) {
            // Metrics collection must never break the primary flow.
        }
    }

    /**
     * @param  array<string, mixed>  $labels
     * @param  callable(array<string, string>): void  $writer
     */
    private function capture(string $metric, array $labels, callable $writer): void
    {
        try {
            $safeLabels = $this->sanitizeLabels($labels);
            $seriesKey = $this->labelKey($safeLabels);

            if (! isset($this->knownSeries[$metric][$seriesKey])) {
                $currentSeries = count($this->knownSeries[$metric] ?? []);
                if ($currentSeries >= $this->maxSeriesPerMetric) {
                    $this->store->increment('metrics_dropped_total', ['reason' => 'cardinality_limit']);

                    return;
                }
                $this->knownSeries[$metric][$seriesKey] = true;
            }

            $writer($safeLabels);
        } catch (Throwable) {
            // Backend-neutral local metrics are best effort and must fail open.
        }
    }

    /** @param array<string, mixed> $labels @return array<string, string> */
    private function sanitizeLabels(array $labels): array
    {
        $safe = [];
        foreach ($this->allowedLabels as $allowedLabel) {
            if (! array_key_exists($allowedLabel, $labels)) {
                continue;
            }

            $safe[$allowedLabel] = $this->normalizeValue((string) $labels[$allowedLabel]);
        }

        return $safe;
    }

    private function normalizeValue(string $value): string
    {
        $value = preg_replace('/[^A-Za-z0-9_.:-]/', '_', $value) ?? 'unknown';
        $value = trim($value, '_');

        return substr($value === '' ? 'unknown' : $value, 0, 80);
    }

    /** @param array<string, string> $labels */
    private function labelKey(array $labels): string
    {
        if ($labels === []) {
            return '_';
        }

        $parts = [];
        foreach ($labels as $name => $value) {
            $parts[] = $name.'='.$value;
        }

        return implode(',', $parts);
    }

    private function statusClass(int $statusCode): string
    {
        $class = intdiv(max(100, min(599, $statusCode)), 100);

        return $class.'xx';
    }
}
