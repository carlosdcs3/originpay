<?php

use Illuminate\Support\Facades\File;

function r8RollbackEvidence(array $overrides = []): array
{
    $completed = now()->subHour();

    return array_replace_recursive([
        'schema_version' => '1.0',
        'executed_at' => $completed->toIso8601String(),
        'environment' => 'staging',
        'release_version' => '8.6.0',
        'previous_version' => '8.5.0',
        'rollback_started_at' => $completed->copy()->subMinutes(10)->toIso8601String(),
        'rollback_completed_at' => $completed->toIso8601String(),
        'duration_seconds' => 600,
        'application_rollback' => ['attempted' => true, 'completed' => true, 'version_restored' => true, 'config_restored' => true, 'assets_restored' => true, 'errors_count' => 0],
        'database_rollback' => ['strategy' => 'forward_compatible', 'attempted' => true, 'completed' => true, 'schema_compatible' => true, 'migrations_reversible' => true, 'backup_reference' => 'BACKUP-R8-001', 'restore_tested' => true, 'data_loss_detected' => false, 'errors_count' => 0],
        'workers_rollback' => ['attempted' => true, 'completed' => true, 'old_workers_stopped' => true, 'target_workers_started' => true, 'queue_compatible' => true, 'jobs_lost' => 0, 'jobs_duplicated' => 0, 'errors_count' => 0],
        'health_validation' => ['liveness_ok' => true, 'readiness_ok' => true, 'deep_health_ok' => true, 'critical_dependencies_ok' => true],
        'financial_integrity' => ['ledger_balanced' => true, 'duplicate_transactions' => 0, 'missing_transactions' => 0, 'amount_mismatch_count' => 0, 'settlement_mismatch_count' => 0, 'unresolved_difference_amount' => 0.0, 'currency' => 'BRL'],
        'evidence_reference' => 'ROLLBACK-R8-001',
        'approved_by' => 'Release Operations',
        'result' => 'PASS',
    ], $overrides);
}

function runR8RollbackReadiness($test, ?array $evidence, ?string $raw = null)
{
    $directory = storage_path('framework/testing/r8-rollback');
    File::ensureDirectoryExists($directory);
    $checklist = $directory.'/checklist.json';
    $evidencePath = $directory.'/rollback.json';
    File::put($checklist, json_encode([
        'scope_frozen' => false, 'full_suite' => false, 'load_soak' => false, 'pentest' => false,
        'sandbox_reconciliation' => false, 'merchant_uat' => false, 'admin_uat' => false,
        'rollback' => true, 'open_critical_findings' => 0, 'open_high_findings' => 0,
        'production_checklist_signed' => false,
    ], JSON_THROW_ON_ERROR));
    if ($raw !== null) {
        File::put($evidencePath, $raw);
    } elseif ($evidence !== null) {
        File::put($evidencePath, json_encode($evidence, JSON_THROW_ON_ERROR));
    } else {
        File::delete($evidencePath);
    }

    return $test->artisan('release:readiness', ['--checklist' => $checklist, '--rollback-evidence' => $evidencePath]);
}

it('valid evidence releases only rollback and keeps other gates independent', function () {
    runR8RollbackReadiness($this, r8RollbackEvidence())->expectsOutputToContain('rollback: OK')->expectsOutputToContain('load_soak: PENDENTE')->assertExitCode(1);
});

it('blocks absent malformed incompatible expired or production rollback evidence', function (?array $evidence, ?string $raw) {
    runR8RollbackReadiness($this, $evidence, $raw)->expectsOutputToContain('rollback: PENDENTE')->assertExitCode(1);
})->with([
    'absent' => [null, null], 'invalid json' => [null, '{'], 'version' => [['schema_version' => '2.0'], null],
    'expired' => [r8RollbackEvidence(['executed_at' => now()->subDays(8)->toIso8601String()]), null],
    'production' => [r8RollbackEvidence(['environment' => 'production']), null],
]);

it('blocks invalid application rollback', function (array $override) {
    runR8RollbackReadiness($this, r8RollbackEvidence(['application_rollback' => $override]))->expectsOutputToContain('rollback: PENDENTE')->assertExitCode(1);
})->with(array_map(fn (array $case) => [$case], [
    ['attempted' => false], ['completed' => false], ['version_restored' => false], ['config_restored' => false], ['assets_restored' => false], ['errors_count' => 1],
]));

it('blocks invalid database rollback', function (array $override) {
    runR8RollbackReadiness($this, r8RollbackEvidence(['database_rollback' => $override]))->expectsOutputToContain('rollback: PENDENTE')->assertExitCode(1);
})->with(array_map(fn (array $case) => [$case], [
    ['schema_compatible' => false], ['migrations_reversible' => false, 'strategy' => ''], ['backup_reference' => ''], ['restore_tested' => false], ['data_loss_detected' => true], ['errors_count' => 1],
]));

it('blocks invalid workers rollback', function (array $override) {
    runR8RollbackReadiness($this, r8RollbackEvidence(['workers_rollback' => $override]))->expectsOutputToContain('rollback: PENDENTE')->assertExitCode(1);
})->with(array_map(fn (array $case) => [$case], [
    ['completed' => false], ['old_workers_stopped' => false], ['target_workers_started' => false], ['queue_compatible' => false], ['jobs_lost' => 1], ['jobs_duplicated' => 1],
]));

it('blocks invalid health financial approval timestamps versions and PASS alone', function (array $evidence) {
    runR8RollbackReadiness($this, $evidence)->expectsOutputToContain('rollback: PENDENTE')->assertExitCode(1);
})->with(array_map(fn (array $case) => [$case], [
    r8RollbackEvidence(['health_validation' => ['deep_health_ok' => false]]),
    r8RollbackEvidence(['financial_integrity' => ['ledger_balanced' => false]]),
    r8RollbackEvidence(['financial_integrity' => ['duplicate_transactions' => 1]]),
    r8RollbackEvidence(['financial_integrity' => ['missing_transactions' => 1]]),
    r8RollbackEvidence(['financial_integrity' => ['amount_mismatch_count' => 1]]),
    r8RollbackEvidence(['financial_integrity' => ['settlement_mismatch_count' => 1]]),
    r8RollbackEvidence(['financial_integrity' => ['unresolved_difference_amount' => 0.01]]),
    r8RollbackEvidence(['approved_by' => '']), r8RollbackEvidence(['evidence_reference' => '']),
    r8RollbackEvidence(['release_version' => '']), r8RollbackEvidence(['previous_version' => '']),
    r8RollbackEvidence(['duration_seconds' => 1]),
    ['schema_version' => '1.0', 'result' => 'PASS'],
]));
