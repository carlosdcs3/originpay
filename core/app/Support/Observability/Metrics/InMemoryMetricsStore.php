<?php

namespace App\Support\Observability\Metrics;

class InMemoryMetricsStore implements MetricsStore
{
    /** @var array<string, array<string, int|float>> */
    private array $counters = [];

    /** @var array<string, array<string, array{count: int, sum: float, min: float|null, max: float|null}>> */
    private array $distributions = [];

    /** @var array<string, array<string, int|float>> */
    private array $gauges = [];

    public function increment(string $metric, array $labels, int|float $value = 1): void
    {
        $key = $this->labelKey($labels);
        $this->counters[$metric][$key] = ($this->counters[$metric][$key] ?? 0) + $value;
    }

    public function observe(string $metric, array $labels, int|float $value): void
    {
        $key = $this->labelKey($labels);
        $current = $this->distributions[$metric][$key] ?? [
            'count' => 0,
            'sum' => 0.0,
            'min' => null,
            'max' => null,
        ];

        $floatValue = (float) $value;
        $current['count']++;
        $current['sum'] += $floatValue;
        $current['min'] = $current['min'] === null ? $floatValue : min($current['min'], $floatValue);
        $current['max'] = $current['max'] === null ? $floatValue : max($current['max'], $floatValue);

        $this->distributions[$metric][$key] = $current;
    }

    public function gauge(string $metric, array $labels, int|float $value): void
    {
        $this->gauges[$metric][$this->labelKey($labels)] = $value;
    }

    public function snapshot(): array
    {
        return [
            'counters' => $this->counters,
            'distributions' => $this->distributions,
            'gauges' => $this->gauges,
        ];
    }

    public function reset(): void
    {
        $this->counters = [];
        $this->distributions = [];
        $this->gauges = [];
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
}
