<?php

use Illuminate\Support\Facades\File;

it('fails closed when the release checklist is incomplete', function () {
    $path = storage_path('app/release-candidate/checklist.json');
    File::ensureDirectoryExists(dirname($path));
    File::put($path, json_encode([
        'scope_frozen' => true,
        'full_suite' => true,
        'load_soak' => false,
        'pentest' => true,
        'sandbox_reconciliation' => true,
        'merchant_uat' => true,
        'admin_uat' => true,
        'rollback' => true,
        'open_critical_findings' => 0,
        'open_high_findings' => 0,
        'production_checklist_signed' => true,
    ], JSON_THROW_ON_ERROR));

    try {
        $this->artisan('release:readiness', ['--checklist' => $path])
            ->expectsOutputToContain('load_soak: PENDENTE')
            ->expectsOutputToContain('RELEASE CANDIDATE BLOQUEADA')
            ->assertExitCode(1);
    } finally {
        File::delete($path);
    }
});

it('blocks a complete checklist when load soak evidence is absent', function () {
    $path = storage_path('app/release-candidate/checklist.json');
    File::ensureDirectoryExists(dirname($path));
    File::put($path, json_encode([
        'scope_frozen' => true,
        'full_suite' => true,
        'load_soak' => true,
        'pentest' => true,
        'sandbox_reconciliation' => true,
        'merchant_uat' => true,
        'admin_uat' => true,
        'rollback' => true,
        'open_critical_findings' => 0,
        'open_high_findings' => 0,
        'production_checklist_signed' => true,
    ], JSON_THROW_ON_ERROR));

    try {
        $this->artisan('release:readiness', ['--checklist' => $path])
            ->expectsOutputToContain('load_soak: PENDENTE')
            ->expectsOutputToContain('RELEASE CANDIDATE BLOQUEADA')
            ->assertExitCode(1);
    } finally {
        File::delete($path);
    }
});

it('fails closed for a missing or malformed checklist', function (string $contents) {
    $path = storage_path('app/release-candidate/checklist.json');
    File::ensureDirectoryExists(dirname($path));
    File::put($path, $contents);

    try {
        $this->artisan('release:readiness', ['--checklist' => $path])
            ->expectsOutputToContain('Checklist inválido')
            ->assertExitCode(1);
    } finally {
        File::delete($path);
    }
})->with(['invalid json' => '{', 'empty object' => '{}']);
