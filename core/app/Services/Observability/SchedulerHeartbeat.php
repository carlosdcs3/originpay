<?php

namespace App\Services\Observability;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;

class SchedulerHeartbeat
{
    public function record(): void
    {
        $this->cacheStore()->put($this->cacheKey(), now()->toISOString(), $this->ttlSeconds());
    }

    public function lastHeartbeatAt(): ?string
    {
        $value = $this->cacheStore()->get($this->cacheKey());

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function cacheStore(): Repository
    {
        $store = config('observability.deep_health.scheduler_heartbeat.store');

        return is_string($store) && $store !== ''
            ? Cache::store($store)
            : Cache::store();
    }

    private function cacheKey(): string
    {
        $key = config('observability.deep_health.scheduler_heartbeat.key');

        return is_string($key) && $key !== ''
            ? $key
            : 'originpay:scheduler:last_heartbeat_at';
    }

    private function ttlSeconds(): int
    {
        $errorSeconds = (int) config('observability.deep_health.thresholds.scheduler_freshness_seconds.error', 300);

        return max($errorSeconds * 3, 900);
    }
}
