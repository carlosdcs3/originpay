<?php

use Illuminate\Support\Facades\File;

function r8Checklist(array $overrides = []): array
{
    return array_replace([
        'scope_frozen' => false,
        'full_suite' => false,
        'load_soak' => true,
        'pentest' => false,
        'sandbox_reconciliation' => false,
        'merchant_uat' => false,
        'admin_uat' => false,
        'rollback' => false,
        'open_critical_findings' => 0,
        'open_high_findings' => 0,
        'production_checklist_signed' => false,
    ], $overrides);
}

function r8LoadEvidence(array $overrides = []): array
{
    return array_replace([
        'schema_version' => '1.0',
        'executed_at' => now()->subHour()->toIso8601String(),
        'environment' => 'staging',
        'duration_seconds' => 3600,
        'requests_total' => 36000,
        'error_rate' => 0.001,
        'p95_ms' => 400,
        'p99_ms' => 800,
        'throughput_rps' => 10.0,
        'functional_failures' => 0,
        'thresholds' => [
            'max_age_hours' => 168,
            'min_duration_seconds' => 3600,
            'max_error_rate' => 0.01,
            'max_p95_ms' => 500,
            'max_p99_ms' => 1000,
            'min_throughput_rps' => 1,
        ],
        'result' => 'PASS',
    ], $overrides);
}

function runR8Readiness($test, ?array $evidence, ?string $rawEvidence = null)
{
    $directory = storage_path('framework/testing/r8-load-soak');
    File::ensureDirectoryExists($directory);
    $checklist = $directory.'/checklist.json';
    $evidencePath = $directory.'/evidence.json';
    File::put($checklist, json_encode(r8Checklist(), JSON_THROW_ON_ERROR));
    if ($rawEvidence !== null) {
        File::put($evidencePath, $rawEvidence);
    } elseif ($evidence !== null) {
        File::put($evidencePath, json_encode($evidence, JSON_THROW_ON_ERROR));
    } else {
        File::delete($evidencePath);
    }

    return $test->artisan('release:readiness', [
        '--checklist' => $checklist,
        '--load-soak-evidence' => $evidencePath,
    ]);
}

it('valid evidence releases only load soak while other R8 gates remain blocked', function () {
    runR8Readiness($this, r8LoadEvidence())
        ->expectsOutputToContain('load_soak: OK')
        ->expectsOutputToContain('pentest: PENDENTE')
        ->expectsOutputToContain('RELEASE CANDIDATE BLOQUEADA')
        ->assertExitCode(1);
});

it('blocks absent load soak evidence', function () {
    runR8Readiness($this, null)->expectsOutputToContain('load_soak: PENDENTE')->assertExitCode(1);
});

it('blocks expired evidence', function () {
    runR8Readiness($this, r8LoadEvidence(['executed_at' => now()->subDays(8)->toIso8601String()]))
        ->expectsOutputToContain('load_soak: PENDENTE')->assertExitCode(1);
});

it('blocks insufficient duration', function () {
    runR8Readiness($this, r8LoadEvidence(['duration_seconds' => 3599]))
        ->expectsOutputToContain('load_soak: PENDENTE')->assertExitCode(1);
});

it('blocks error rate above threshold', function () {
    runR8Readiness($this, r8LoadEvidence(['error_rate' => 0.011]))
        ->expectsOutputToContain('load_soak: PENDENTE')->assertExitCode(1);
});

it('blocks latency above threshold', function (array $overrides) {
    runR8Readiness($this, r8LoadEvidence($overrides))
        ->expectsOutputToContain('load_soak: PENDENTE')->assertExitCode(1);
})->with([
    'p95' => [['p95_ms' => 501]],
    'p99' => [['p99_ms' => 1001]],
]);

it('blocks functional failures', function () {
    runR8Readiness($this, r8LoadEvidence(['functional_failures' => 1]))
        ->expectsOutputToContain('load_soak: PENDENTE')->assertExitCode(1);
});

it('blocks invalid evidence JSON', function () {
    runR8Readiness($this, null, '{')->expectsOutputToContain('load_soak: PENDENTE')->assertExitCode(1);
});

it('does not trust PASS without complete metrics', function () {
    runR8Readiness($this, ['schema_version' => '1.0', 'result' => 'PASS'])
        ->expectsOutputToContain('load_soak: PENDENTE')->assertExitCode(1);
});

it('blocks evidence whose declared thresholds differ from configured provisional thresholds', function () {
    $evidence = r8LoadEvidence();
    $evidence['thresholds']['max_error_rate'] = 1;
    runR8Readiness($this, $evidence)->expectsOutputToContain('load_soak: PENDENTE')->assertExitCode(1);
});
