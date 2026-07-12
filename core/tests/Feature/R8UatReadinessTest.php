<?php

use Illuminate\Support\Facades\File;

function r8UatBlock(string $kind, array $overrides = []): array
{
    return array_replace([
        'scope' => config("release.uat.{$kind}_required_scope"),
        'scenarios_total' => 10,
        'scenarios_passed' => 10,
        'scenarios_failed' => 0,
        'blockers_open' => 0,
        'critical_open' => 0,
        'high_open' => 0,
        'acceptance_signed' => true,
        'signer' => ucfirst($kind).' Product Owner',
        'notes_reference' => 'UAT-'.strtoupper($kind).'-001',
    ], $overrides);
}

function r8UatEvidence(array $overrides = []): array
{
    return array_replace([
        'schema_version' => '1.0',
        'executed_at' => now()->subHour()->toIso8601String(),
        'environment' => 'staging',
        'merchant_uat' => r8UatBlock('merchant'),
        'admin_uat' => r8UatBlock('admin'),
        'evidence_reference' => 'UAT-R8-001',
        'approved_by' => 'Release Management',
        'result' => 'PASS',
    ], $overrides);
}

function runR8UatReadiness($test, ?array $evidence, ?string $raw = null, array $checklistOverrides = [])
{
    $directory = storage_path('framework/testing/r8-uat');
    File::ensureDirectoryExists($directory);
    $checklist = $directory.'/checklist.json';
    $evidencePath = $directory.'/uat.json';
    $base = [
        'scope_frozen' => false, 'full_suite' => false, 'load_soak' => false, 'pentest' => false,
        'sandbox_reconciliation' => false, 'merchant_uat' => true, 'admin_uat' => true,
        'rollback' => false, 'open_critical_findings' => 0, 'open_high_findings' => 0,
        'production_checklist_signed' => false,
    ];
    File::put($checklist, json_encode(array_replace($base, $checklistOverrides), JSON_THROW_ON_ERROR));
    if ($raw !== null) {
        File::put($evidencePath, $raw);
    } elseif ($evidence !== null) {
        File::put($evidencePath, json_encode($evidence, JSON_THROW_ON_ERROR));
    } else {
        File::delete($evidencePath);
    }

    return $test->artisan('release:readiness', ['--checklist' => $checklist, '--uat-evidence' => $evidencePath]);
}

it('valid evidence releases only both UAT gates and keeps other gates independent', function () {
    runR8UatReadiness($this, r8UatEvidence())->expectsOutputToContain('merchant_uat: OK')
        ->expectsOutputToContain('admin_uat: OK')->expectsOutputToContain('rollback: PENDENTE')->assertExitCode(1);
});

it('blocks absent invalid incompatible or expired UAT evidence', function (?array $evidence, ?string $raw) {
    runR8UatReadiness($this, $evidence, $raw)->expectsOutputToContain('merchant_uat: PENDENTE')
        ->expectsOutputToContain('admin_uat: PENDENTE')->assertExitCode(1);
})->with([
    'absent' => [null, null], 'invalid JSON' => [null, '{'],
    'version' => [['schema_version' => '2.0'], null],
    'expired' => [['schema_version' => '1.0', 'executed_at' => '2000-01-01T00:00:00Z'], null],
    'production' => [['schema_version' => '1.0', 'environment' => 'production'], null],
]);

it('keeps merchant and admin UAT evidence checks independent', function (string $missing, string $ok, string $blocked) {
    $evidence = r8UatEvidence();
    unset($evidence[$missing]);
    runR8UatReadiness($this, $evidence)->expectsOutputToContain("{$ok}: OK")
        ->expectsOutputToContain("{$blocked}: PENDENTE")->assertExitCode(1);
})->with([
    'merchant absent' => ['merchant_uat', 'admin_uat', 'merchant_uat'],
    'admin absent' => ['admin_uat', 'merchant_uat', 'admin_uat'],
]);

it('blocks invalid UAT outcome rules independently', function (string $kind, array $override) {
    $evidence = r8UatEvidence([$kind.'_uat' => r8UatBlock($kind, $override)]);
    runR8UatReadiness($this, $evidence)->expectsOutputToContain($kind.'_uat: PENDENTE')->assertExitCode(1);
})->with([
    'total zero' => ['merchant', ['scenarios_total' => 0, 'scenarios_passed' => 0]],
    'inconsistent total' => ['merchant', ['scenarios_passed' => 9]],
    'failed scenario' => ['merchant', ['scenarios_passed' => 9, 'scenarios_failed' => 1]],
    'blocker' => ['merchant', ['blockers_open' => 1]],
    'critical' => ['merchant', ['critical_open' => 1]],
    'high' => ['admin', ['high_open' => 1]],
    'merchant unsigned' => ['merchant', ['acceptance_signed' => false]],
    'admin unsigned' => ['admin', ['acceptance_signed' => false]],
    'signer absent' => ['admin', ['signer' => '']],
]);

it('does not trust PASS without the complete UAT contract', function () {
    runR8UatReadiness($this, ['schema_version' => '1.0', 'result' => 'PASS'])
        ->expectsOutputToContain('merchant_uat: PENDENTE')->expectsOutputToContain('admin_uat: PENDENTE')->assertExitCode(1);
});

it('requires the configured minimum scope for each UAT audience', function (string $kind) {
    $evidence = r8UatEvidence([$kind.'_uat' => r8UatBlock($kind, ['scope' => ['login_session']])]);
    runR8UatReadiness($this, $evidence)->expectsOutputToContain($kind.'_uat: PENDENTE')->assertExitCode(1);
})->with(['merchant', 'admin']);
