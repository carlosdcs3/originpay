<?php

namespace App\Support\Observability;

use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;

class QueueOperationalContext
{
    /**
     * @return array<string, mixed>
     */
    public static function contextFor(object $job): array
    {
        if (method_exists($job, 'operationalContext')) {
            return $job->operationalContext();
        }

        return [
            'correlation_id' => (string) Str::uuid(),
            'job_id' => (string) Str::uuid(),
        ];
    }

    public static function restore(object $job, ?string $queue = null, ?int $attempt = null): void
    {
        self::clear();

        foreach (self::contextFor($job) as $key => $value) {
            if ($value !== null && $value !== '') {
                Context::add($key, $value);
            }
        }

        if ($queue !== null && $queue !== '') {
            Context::add('queue', $queue);
        }

        if ($attempt !== null) {
            Context::add('attempt', $attempt);
        }
    }

    public static function clear(): void
    {
        foreach (['correlation_id', 'tenant_id', 'merchant_id', 'user_id', 'api_key_id', 'payment_id', 'gateway', 'webhook_event_id', 'job_id', 'queue', 'attempt'] as $key) {
            Context::forget($key);
        }
    }
}
