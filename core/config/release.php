<?php

return [
    'load_soak' => [
        'schema_version' => '1.0',
        'max_age_hours' => (int) env('RELEASE_LOAD_SOAK_MAX_AGE_HOURS', 168),
        'min_duration_seconds' => (int) env('RELEASE_LOAD_SOAK_MIN_DURATION_SECONDS', 3600),
        'max_error_rate' => (float) env('RELEASE_LOAD_SOAK_MAX_ERROR_RATE', 0.01),
        'max_p95_ms' => (float) env('RELEASE_LOAD_SOAK_MAX_P95_MS', 500),
        'max_p99_ms' => (float) env('RELEASE_LOAD_SOAK_MAX_P99_MS', 1000),
        'min_throughput_rps' => (float) env('RELEASE_LOAD_SOAK_MIN_THROUGHPUT_RPS', 1),
    ],
    'pentest' => [
        'schema_version' => '1.0',
        'max_age_hours' => (int) env('RELEASE_PENTEST_MAX_AGE_HOURS', 168),
        'required_scope' => [
            'authentication', 'authorization_rbac', 'tenant_isolation', 'idor', 'api_keys_scopes',
            'webhooks', 'uploads', 'ssrf', 'xss', 'rate_limiting', 'mass_assignment', 'queues_jobs',
            'administrative_endpoints', 'secret_exposure', 'production_configuration',
        ],
    ],
    'sandbox_reconciliation' => [
        'schema_version' => '1.0',
        'max_age_hours' => (int) env('RELEASE_RECONCILIATION_MAX_AGE_HOURS', 168),
        'min_sample_size' => (int) env('RELEASE_RECONCILIATION_MIN_SAMPLE_SIZE', 100),
    ],
    'rollback' => [
        'schema_version' => '1.0',
        'max_age_hours' => (int) env('RELEASE_ROLLBACK_MAX_AGE_HOURS', 168),
        'safe_database_strategies' => ['forward_compatible', 'backup_restore'],
    ],
    'uat' => [
        'schema_version' => '1.0',
        'max_age_hours' => (int) env('RELEASE_UAT_MAX_AGE_HOURS', 168),
        'merchant_required_scope' => ['login_session', 'charge_create_view', 'payments_states', 'webhooks', 'refunds_if_exposed', 'settlement_balance', 'api_keys', 'own_tenant_isolation', 'error_messages', 'dashboard_main_flow'],
        'admin_required_scope' => ['login_session', 'rbac', 'merchant_management', 'balance_fees_limits', 'settlement', 'gateways_credentials', 'admin_webhook', 'dlq', 'audit', 'operator_denied_actions'],
    ],
];
