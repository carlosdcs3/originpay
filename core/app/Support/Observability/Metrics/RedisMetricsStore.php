<?php

namespace App\Support\Observability\Metrics;

class RedisMetricsStore implements MetricsStore
{
    public function __construct(
        private readonly object $redis,
        private readonly int $ttlSeconds = 7776000,
        private readonly string $namespace = 'originpay:metrics',
    ) {
        if (! preg_match('/^[A-Za-z0-9:_-]+$/', $this->namespace)) {
            throw new \InvalidArgumentException('Invalid metrics namespace.');
        }
    }

    public function increment(string $metric, array $labels, int|float $value = 1): void
    {
        $key = $this->key('counter', $metric);
        $this->redis->hincrbyfloat($key, $this->labelKey($labels), (float) $value);
        $this->track($key);
    }

    public function observe(string $metric, array $labels, int|float $value): void
    {
        $key = $this->key('distribution', $metric);
        $field = $this->labelKey($labels);
        $current = $this->redis->hgetall($key);
        $values = isset($current[$field]) ? json_decode((string) $current[$field], true) : null;
        $float = (float) $value;
        $values = is_array($values) ? $values : ['count' => 0, 'sum' => 0.0, 'min' => null, 'max' => null];
        $values['count']++;
        $values['sum'] += $float;
        $values['min'] = $values['min'] === null ? $float : min($values['min'], $float);
        $values['max'] = $values['max'] === null ? $float : max($values['max'], $float);
        $this->redis->hset($key, $field, json_encode($values, JSON_THROW_ON_ERROR));
        $this->track($key);
    }

    public function gauge(string $metric, array $labels, int|float $value): void
    {
        $key = $this->key('gauge', $metric);
        $this->redis->hset($key, $this->labelKey($labels), (float) $value);
        $this->track($key);
    }

    public function snapshot(): array
    {
        $snapshot = ['counters' => [], 'distributions' => [], 'gauges' => []];
        foreach ($this->redis->smembers($this->indexKey()) as $key) {
            $prefix = preg_quote($this->namespace, '/');
            if (! preg_match('/^'.$prefix.':(counter|distribution|gauge):(.+)$/', $key, $matches)) {
                continue;
            }
            $bucket = ['counter' => 'counters', 'distribution' => 'distributions', 'gauge' => 'gauges'][$matches[1]];
            foreach ($this->redis->hgetall($key) as $field => $value) {
                $snapshot[$bucket][$matches[2]][$field] = $matches[1] === 'distribution'
                    ? json_decode((string) $value, true, flags: JSON_THROW_ON_ERROR)
                    : (float) $value;
            }
        }

        return $snapshot;
    }

    public function reset(): void
    {
        $keys = $this->redis->smembers($this->indexKey());
        if ($keys !== []) {
            $this->redis->del($keys);
        }
        $this->redis->del($this->indexKey());
    }

    private function track(string $key): void
    {
        $this->redis->sadd($this->indexKey(), $key);
        $this->redis->expire($key, max(1, $this->ttlSeconds));
        $this->redis->expire($this->indexKey(), max(1, $this->ttlSeconds));
    }

    private function key(string $type, string $metric): string
    {
        return "{$this->namespace}:{$type}:{$metric}";
    }

    private function indexKey(): string
    {
        return $this->namespace.':index';
    }

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
