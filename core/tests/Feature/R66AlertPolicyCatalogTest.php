<?php

namespace Tests\Feature;

use Tests\TestCase;

class R66AlertPolicyCatalogTest extends TestCase
{
    public function test_alert_catalog_has_required_fields_and_valid_severities(): void
    {
        $alerts = config('alerts.catalog');

        $this->assertIsArray($alerts);
        $this->assertNotEmpty($alerts);

        $required = [
            'name',
            'sli',
            'signal',
            'condition',
            'severity',
            'window',
            'owner',
            'first_action',
            'runbook',
            'close_condition',
            'external_integration',
        ];
        $validSeverities = ['SEV1', 'SEV2', 'SEV3', 'SEV4'];

        foreach ($alerts as $key => $alert) {
            foreach ($required as $field) {
                $this->assertArrayHasKey($field, $alert, "Alert {$key} missing {$field}");
                $this->assertNotSame('', $alert[$field], "Alert {$key} has empty {$field}");
            }

            $this->assertContains($alert['severity'], $validSeverities, "Alert {$key} has invalid severity");
            $this->assertStringStartsWith('docs/operations/', $alert['runbook'], "Alert {$key} must reference an operations runbook");
            $this->assertFalse($alert['external_integration'], "Alert {$key} must not enable external integration in R6.6");
        }
    }

    public function test_catalog_covers_required_operational_alerts(): void
    {
        $alerts = config('alerts.catalog');

        foreach ([
            'api_5xx_increase',
            'api_latency_degraded',
            'charge_creation_failure',
            'webhook_processing_failure',
            'queue_backlog_high',
            'failed_jobs_high',
            'dlq_growth',
            'scheduler_delayed',
            'gateway_degraded',
            'settlement_inconsistent',
            'database_unavailable',
            'redis_unavailable',
            'api_key_abuse',
            'backup_restore_failure_future',
        ] as $alertKey) {
            $this->assertArrayHasKey($alertKey, $alerts);
        }
    }

    public function test_observability_thresholds_are_not_marked_as_definitive_slos(): void
    {
        $thresholds = config('observability.deep_health.thresholds');
        $sloBindings = config('alerts.slo_bindings');

        $this->assertIsArray($thresholds);
        $this->assertSame('provisional_operational_signal', config('alerts.threshold_policy'));

        foreach (array_keys($thresholds) as $thresholdName) {
            $this->assertArrayHasKey($thresholdName, $sloBindings);
            $this->assertFalse($sloBindings[$thresholdName]['is_definitive_slo']);
            $this->assertSame('decision_pending', $sloBindings[$thresholdName]['status']);
        }
    }

    public function test_alert_configuration_does_not_contain_secrets(): void
    {
        $encoded = json_encode(config('alerts'), JSON_THROW_ON_ERROR);

        foreach (['password=', 'secret=', 'private_key=', 'bearer '] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, strtolower($encoded));
        }
    }
}
