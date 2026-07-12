<?php

use Illuminate\Support\Facades\File;

function r8ReconciliationEvidence(array $overrides = []): array
{
    return array_replace([
        'schema_version' => '1.0',
        'executed_at' => now()->subHour()->toIso8601String(),
        'environment' => 'sandbox',
        'period_start' => now()->subHours(2)->toIso8601String(),
        'period_end' => now()->subHour()->toIso8601String(),
        'sample_size' => 100,
        'charges_checked' => 40,
        'payments_checked' => 30,
        'refunds_checked' => 10,
        'settlements_checked' => 20,
        'ledger_entries_checked' => 60,
        'matched_total' => 100,
        'unmatched_payments' => 0,
        'unmatched_ledger_entries' => 0,
        'duplicate_transactions' => 0,
        'amount_mismatch_count' => 0,
        'settlement_mismatch_count' => 0,
        'unresolved_difference_amount' => 0.0,
        'currency' => 'BRL',
        'retest_required' => false,
        'retest_completed' => false,
        'evidence_reference' => 'RECON-SANDBOX-2026-001',
        'approved_by' => 'Finance Operations',
        'result' => 'PASS',
    ], $overrides);
}

function r8ReconciliationChecklist(): array
{
    return [
        'scope_frozen' => false, 'full_suite' => false, 'load_soak' => false, 'pentest' => false,
        'sandbox_reconciliation' => true, 'merchant_uat' => false, 'admin_uat' => false,
        'rollback' => false, 'open_critical_findings' => 0, 'open_high_findings' => 0,
        'production_checklist_signed' => false,
    ];
}

function runR8ReconciliationReadiness($test, ?array $evidence, ?string $rawEvidence = null)
{
    $directory = storage_path('framework/testing/r8-reconciliation');
    File::ensureDirectoryExists($directory);
    $checklist = $directory.'/checklist.json';
    $evidencePath = $directory.'/reconciliation.json';
    File::put($checklist, json_encode(r8ReconciliationChecklist(), JSON_THROW_ON_ERROR));

    if ($rawEvidence !== null) {
        File::put($evidencePath, $rawEvidence);
    } elseif ($evidence !== null) {
        File::put($evidencePath, json_encode($evidence, JSON_THROW_ON_ERROR));
    } else {
        File::delete($evidencePath);
    }

    return $test->artisan('release:readiness', [
        '--checklist' => $checklist,
        '--sandbox-reconciliation-evidence' => $evidencePath,
    ]);
}

it('valid evidence releases only sandbox reconciliation while other gates remain independent', function () {
    runR8ReconciliationReadiness($this, r8ReconciliationEvidence())
        ->expectsOutputToContain('sandbox_reconciliation: OK')
        ->expectsOutputToContain('load_soak: PENDENTE')
        ->expectsOutputToContain('merchant_uat: PENDENTE')
        ->expectsOutputToContain('RELEASE CANDIDATE BLOQUEADA')
        ->assertExitCode(1);
});

it('blocks absent or invalid reconciliation evidence', function (?array $evidence, ?string $raw) {
    runR8ReconciliationReadiness($this, $evidence, $raw)
        ->expectsOutputToContain('sandbox_reconciliation: PENDENTE')->assertExitCode(1);
})->with([
    'absent' => [null, null],
    'invalid json' => [null, '{'],
    'incompatible version' => [['schema_version' => '2.0'], null],
]);

it('blocks expired or non sandbox reconciliation evidence', function (array $override) {
    runR8ReconciliationReadiness($this, r8ReconciliationEvidence($override))
        ->expectsOutputToContain('sandbox_reconciliation: PENDENTE')->assertExitCode(1);
})->with([
    'expired' => [['executed_at' => now()->subDays(8)->toIso8601String()]],
    'production' => [['environment' => 'production']],
]);

it('blocks an insufficient reconciliation sample', function () {
    runR8ReconciliationReadiness($this, r8ReconciliationEvidence(['sample_size' => 99, 'matched_total' => 99]))
        ->expectsOutputToContain('sandbox_reconciliation: PENDENTE')->assertExitCode(1);
});

it('blocks financial reconciliation mismatches', function (array $override) {
    runR8ReconciliationReadiness($this, r8ReconciliationEvidence($override))
        ->expectsOutputToContain('sandbox_reconciliation: PENDENTE')->assertExitCode(1);
})->with([
    'payment without ledger' => [['unmatched_payments' => 1]],
    'ledger without payment' => [['unmatched_ledger_entries' => 1]],
    'duplicate' => [['duplicate_transactions' => 1]],
    'amount mismatch' => [['amount_mismatch_count' => 1]],
    'settlement mismatch' => [['settlement_mismatch_count' => 1]],
    'unresolved amount' => [['unresolved_difference_amount' => 0.01]],
]);

it('blocks pending retest or missing approval', function (array $override) {
    runR8ReconciliationReadiness($this, r8ReconciliationEvidence($override))
        ->expectsOutputToContain('sandbox_reconciliation: PENDENTE')->assertExitCode(1);
})->with([
    'pending retest' => [['retest_required' => true, 'retest_completed' => false]],
    'missing approval' => [['approved_by' => '']],
]);

it('blocks invalid negative counts and incoherent dates or totals', function (array $override) {
    runR8ReconciliationReadiness($this, r8ReconciliationEvidence($override))
        ->expectsOutputToContain('sandbox_reconciliation: PENDENTE')->assertExitCode(1);
})->with([
    'negative count' => [['refunds_checked' => -1]],
    'period reversed' => [['period_start' => now()->toIso8601String(), 'period_end' => now()->subHour()->toIso8601String()]],
    'execution before period end' => [['executed_at' => now()->subHours(3)->toIso8601String()]],
    'sample total mismatch' => [['matched_total' => 99]],
    'checked total mismatch' => [['charges_checked' => 39]],
    'invalid currency' => [['currency' => 'REAL']],
]);

it('does not trust PASS without the complete reconciliation contract', function () {
    runR8ReconciliationReadiness($this, ['schema_version' => '1.0', 'result' => 'PASS'])
        ->expectsOutputToContain('sandbox_reconciliation: PENDENTE')->assertExitCode(1);
});
