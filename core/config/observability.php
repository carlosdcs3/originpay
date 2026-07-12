<?php

return [
    'metrics_baseline' => [
        'enabled' => env('ORIGINPAY_METRICS_BASELINE_ENABLED', true),
        'backend' => env('ORIGINPAY_METRICS_BASELINE_BACKEND', 'redis'),
        'redis_connection' => env('ORIGINPAY_METRICS_REDIS_CONNECTION'),
        'redis_namespace' => env('ORIGINPAY_METRICS_REDIS_NAMESPACE', 'originpay:metrics'),
        'ttl_seconds' => (int) env('ORIGINPAY_METRICS_TTL_SECONDS', 7776000),
        'max_series_per_metric' => (int) env('ORIGINPAY_METRICS_BASELINE_MAX_SERIES', 100),
        'allowed_labels' => [
            'route_name',
            'method',
            'status_class',
            'gateway',
            'result',
            'queue',
            'operation',
            'reason',
        ],
        'retention' => [
            'minimum_window_days' => 30,
            'granularity' => '1 minute aggregates for recent local inspection; daily rollups for historical comparison when persisted in a future increment',
            'expires_after_days' => 90,
            'reset_policy' => 'local baseline can be reset between environments or after schema/policy changes; never treat reset as loss of production observability',
            'limitations' => 'local/backend-neutral baseline only; not final production observability and not a replacement for external metrics or incident tooling',
        ],
    ],

    'deep_health' => [
        'thresholds' => [
            'failed_jobs' => [
                'warn' => (int) env('ORIGINPAY_DEEP_HEALTH_FAILED_JOBS_WARN', 1),
                'error' => (int) env('ORIGINPAY_DEEP_HEALTH_FAILED_JOBS_ERROR', 10),
            ],
            'queue_backlog' => [
                'warn' => (int) env('ORIGINPAY_DEEP_HEALTH_QUEUE_BACKLOG_WARN', 100),
                'error' => (int) env('ORIGINPAY_DEEP_HEALTH_QUEUE_BACKLOG_ERROR', 1000),
            ],
            'dlq_count' => [
                'warn' => (int) env('ORIGINPAY_DEEP_HEALTH_DLQ_COUNT_WARN', 1),
                'error' => (int) env('ORIGINPAY_DEEP_HEALTH_DLQ_COUNT_ERROR', 10),
            ],
            'dlq_oldest_age_seconds' => [
                'warn' => (int) env('ORIGINPAY_DEEP_HEALTH_DLQ_OLDEST_AGE_WARN_SECONDS', 900),
                'error' => (int) env('ORIGINPAY_DEEP_HEALTH_DLQ_OLDEST_AGE_ERROR_SECONDS', 3600),
            ],
            'scheduler_freshness_seconds' => [
                'warn' => (int) env('ORIGINPAY_DEEP_HEALTH_SCHEDULER_WARN_SECONDS', 120),
                'error' => (int) env('ORIGINPAY_DEEP_HEALTH_SCHEDULER_ERROR_SECONDS', 300),
            ],
        ],
        'scheduler_heartbeat' => [
            'store' => env('ORIGINPAY_SCHEDULER_HEARTBEAT_CACHE_STORE'),
            'key' => env('ORIGINPAY_SCHEDULER_HEARTBEAT_CACHE_KEY', 'originpay:scheduler:last_heartbeat_at'),
        ],
    ],
];
