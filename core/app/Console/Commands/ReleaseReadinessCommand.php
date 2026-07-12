<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use JsonException;

class ReleaseReadinessCommand extends Command
{
    protected $signature = 'release:readiness
        {--checklist= : Caminho do checklist JSON da release candidate}
        {--load-soak-evidence= : Caminho da evidência JSON versionada de carga/soak}
        {--pentest-evidence= : Caminho da evidência JSON versionada de pentest}
        {--sandbox-reconciliation-evidence= : Caminho da evidência JSON versionada de reconciliação sandbox}
        {--uat-evidence= : Caminho da evidência JSON versionada de UAT merchant/admin}
        {--rollback-evidence= : Caminho da evidência JSON versionada de rollback}';

    protected $description = 'Valida, de forma fail-closed, os gates canônicos da Release Candidate.';

    private const BOOLEAN_GATES = [
        'scope_frozen',
        'full_suite',
        'load_soak',
        'pentest',
        'sandbox_reconciliation',
        'merchant_uat',
        'admin_uat',
        'rollback',
        'production_checklist_signed',
    ];

    public function handle(): int
    {
        $path = $this->option('checklist') ?: storage_path('app/release-candidate/checklist.json');

        try {
            $checklist = $this->readChecklist($path);
        } catch (JsonException|\RuntimeException $exception) {
            $this->error('Checklist inválido: '.$exception->getMessage());

            return self::FAILURE;
        }

        $approved = true;
        $uatEvidence = null;

        foreach (self::BOOLEAN_GATES as $gate) {
            $passed = match ($gate) {
                'load_soak' => $checklist[$gate] && $this->validLoadSoakEvidence(),
                'pentest' => $checklist[$gate] && $this->validPentestEvidence(),
                'sandbox_reconciliation' => $checklist[$gate] && $this->validSandboxReconciliationEvidence(),
                'merchant_uat' => $checklist[$gate] && $this->validUatEvidenceBlock('merchant_uat', $uatEvidence),
                'admin_uat' => $checklist[$gate] && $this->validUatEvidenceBlock('admin_uat', $uatEvidence),
                'rollback' => $checklist[$gate] && $this->validRollbackEvidence(),
                default => $checklist[$gate],
            };
            $this->line($gate.': '.($passed ? 'OK' : 'PENDENTE'));
            $approved = $approved && $passed;
        }

        foreach (['open_critical_findings', 'open_high_findings'] as $gate) {
            $count = $checklist[$gate];
            $this->line($gate.": {$count}");
            $approved = $approved && $count === 0;
        }

        if (! $approved) {
            $this->error('RELEASE CANDIDATE BLOQUEADA');

            return self::FAILURE;
        }

        $this->info('RELEASE CANDIDATE APROVADA');

        return self::SUCCESS;
    }

    /** @return array<string, bool|int> */
    private function readChecklist(string $path): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new \RuntimeException('arquivo ausente ou ilegível');
        }

        $decoded = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($decoded)) {
            throw new \RuntimeException('estrutura JSON deve ser um objeto');
        }

        foreach (self::BOOLEAN_GATES as $gate) {
            if (! array_key_exists($gate, $decoded) || ! is_bool($decoded[$gate])) {
                throw new \RuntimeException("gate {$gate} ausente ou inválido");
            }
        }

        foreach (['open_critical_findings', 'open_high_findings'] as $gate) {
            if (! array_key_exists($gate, $decoded) || ! is_int($decoded[$gate]) || $decoded[$gate] < 0) {
                throw new \RuntimeException("gate {$gate} ausente ou inválido");
            }
        }

        return $decoded;
    }

    private function validLoadSoakEvidence(): bool
    {
        $path = $this->option('load-soak-evidence') ?: storage_path('app/release-candidate/load-soak-evidence.json');

        try {
            if (! is_file($path) || ! is_readable($path)) {
                throw new \RuntimeException('arquivo ausente ou ilegível');
            }

            $evidence = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            if (! is_array($evidence)) {
                throw new \RuntimeException('estrutura JSON deve ser um objeto');
            }

            $this->assertLoadSoakEvidence($evidence);
        } catch (JsonException|\RuntimeException $exception) {
            $this->warn('Evidência load/soak inválida: '.$exception->getMessage());

            return false;
        }

        return true;
    }

    /** @param array<string, mixed> $evidence */
    private function assertLoadSoakEvidence(array $evidence): void
    {
        $configured = config('release.load_soak');
        $thresholds = $evidence['thresholds'] ?? null;
        $numeric = ['duration_seconds', 'requests_total', 'error_rate', 'p95_ms', 'p99_ms', 'throughput_rps', 'functional_failures'];

        if (! is_array($configured) || ! is_array($thresholds)
            || ($evidence['schema_version'] ?? null) !== $configured['schema_version']
            || ! is_string($evidence['executed_at'] ?? null)
            || ! is_string($evidence['environment'] ?? null) || trim($evidence['environment']) === ''
            || ($evidence['result'] ?? null) !== 'PASS') {
            throw new \RuntimeException('contrato obrigatório ausente ou inválido');
        }

        foreach ($numeric as $field) {
            if (! isset($evidence[$field]) || ! is_int($evidence[$field]) && ! is_float($evidence[$field]) || $evidence[$field] < 0) {
                throw new \RuntimeException("campo {$field} ausente ou inválido");
            }
        }

        foreach (array_diff_key($configured, ['schema_version' => true]) as $name => $value) {
            if (! array_key_exists($name, $thresholds) || (float) $thresholds[$name] !== (float) $value) {
                throw new \RuntimeException("threshold {$name} não corresponde à configuração");
            }
        }

        try {
            $executedAt = Carbon::parse($evidence['executed_at']);
        } catch (\Throwable) {
            throw new \RuntimeException('executed_at inválido');
        }

        if ($executedAt->isFuture() || $executedAt->lt(now()->subHours($configured['max_age_hours']))
            || $evidence['duration_seconds'] < $configured['min_duration_seconds']
            || $evidence['requests_total'] <= 0
            || $evidence['error_rate'] > $configured['max_error_rate']
            || $evidence['p95_ms'] > $configured['max_p95_ms']
            || $evidence['p99_ms'] > $configured['max_p99_ms']
            || $evidence['throughput_rps'] < $configured['min_throughput_rps']
            || $evidence['functional_failures'] !== 0) {
            throw new \RuntimeException('métricas fora dos thresholds provisórios');
        }
    }

    private function validPentestEvidence(): bool
    {
        $path = $this->option('pentest-evidence') ?: storage_path('app/release-candidate/pentest-evidence.json');
        try {
            if (! is_file($path) || ! is_readable($path)) {
                throw new \RuntimeException('arquivo ausente ou ilegível');
            }
            $evidence = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            if (! is_array($evidence)) {
                throw new \RuntimeException('estrutura JSON deve ser um objeto');
            }
            $this->assertPentestEvidence($evidence);
        } catch (JsonException|\RuntimeException $exception) {
            $this->warn('Evidência pentest inválida: '.$exception->getMessage());

            return false;
        }

        return true;
    }

    /** @param array<string, mixed> $evidence */
    private function assertPentestEvidence(array $evidence): void
    {
        $configured = config('release.pentest');
        $strings = ['executed_at', 'environment', 'methodology', 'tester', 'findings_summary', 'evidence_reference', 'approved_by'];
        $counts = ['critical_open', 'high_open', 'medium_open', 'medium_accepted', 'low_open'];
        if (! is_array($configured) || ($evidence['schema_version'] ?? null) !== $configured['schema_version']
            || ($evidence['result'] ?? null) !== 'PASS' || ! is_array($evidence['scope'] ?? null)
            || ! is_bool($evidence['retest_required'] ?? null) || ! is_bool($evidence['retest_completed'] ?? null)) {
            throw new \RuntimeException('contrato obrigatório ausente ou inválido');
        }
        foreach ($strings as $field) {
            if (! is_string($evidence[$field] ?? null) || trim($evidence[$field]) === '') {
                throw new \RuntimeException("campo {$field} ausente ou inválido");
            }
        }
        foreach ($counts as $field) {
            if (! array_key_exists($field, $evidence) || ! is_int($evidence[$field]) || $evidence[$field] < 0) {
                throw new \RuntimeException("campo {$field} ausente ou inválido");
            }
        }
        try {
            $executedAt = Carbon::parse($evidence['executed_at']);
        } catch (\Throwable) {
            throw new \RuntimeException('executed_at inválido');
        }
        $scope = array_values(array_unique(array_filter($evidence['scope'], 'is_string')));
        if ($executedAt->isFuture() || $executedAt->lt(now()->subHours($configured['max_age_hours']))
            || array_diff($configured['required_scope'], $scope) !== []
            || $evidence['critical_open'] > 0 || $evidence['high_open'] > 0
            || $evidence['medium_open'] > $evidence['medium_accepted']
            || $evidence['medium_accepted'] > $evidence['medium_open']
            || $evidence['retest_required'] && ! $evidence['retest_completed']) {
            throw new \RuntimeException('evidência não atende aos critérios de aprovação');
        }
    }

    private function validSandboxReconciliationEvidence(): bool
    {
        $path = $this->option('sandbox-reconciliation-evidence') ?: storage_path('app/release-candidate/sandbox-reconciliation-evidence.json');

        try {
            if (! is_file($path) || ! is_readable($path)) {
                throw new \RuntimeException('arquivo ausente ou ilegível');
            }
            $evidence = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            if (! is_array($evidence)) {
                throw new \RuntimeException('estrutura JSON deve ser um objeto');
            }
            $this->assertSandboxReconciliationEvidence($evidence);
        } catch (JsonException|\RuntimeException $exception) {
            $this->warn('Evidência de reconciliação sandbox inválida: '.$exception->getMessage());

            return false;
        }

        return true;
    }

    /** @param array<string, mixed> $evidence */
    private function assertSandboxReconciliationEvidence(array $evidence): void
    {
        $configured = config('release.sandbox_reconciliation');
        $strings = ['executed_at', 'environment', 'period_start', 'period_end', 'currency', 'evidence_reference', 'approved_by'];
        $counts = ['sample_size', 'charges_checked', 'payments_checked', 'refunds_checked', 'settlements_checked',
            'ledger_entries_checked', 'matched_total', 'unmatched_payments', 'unmatched_ledger_entries',
            'duplicate_transactions', 'amount_mismatch_count', 'settlement_mismatch_count'];

        if (! is_array($configured) || ($evidence['schema_version'] ?? null) !== $configured['schema_version']
            || ($evidence['result'] ?? null) !== 'PASS'
            || ! is_bool($evidence['retest_required'] ?? null) || ! is_bool($evidence['retest_completed'] ?? null)
            || ! array_key_exists('unresolved_difference_amount', $evidence)
            || (! is_int($evidence['unresolved_difference_amount']) && ! is_float($evidence['unresolved_difference_amount']))
            || $evidence['unresolved_difference_amount'] < 0) {
            throw new \RuntimeException('contrato obrigatório ausente ou inválido');
        }
        foreach ($strings as $field) {
            if (! is_string($evidence[$field] ?? null) || trim($evidence[$field]) === '') {
                throw new \RuntimeException("campo {$field} ausente ou inválido");
            }
        }
        foreach ($counts as $field) {
            if (! array_key_exists($field, $evidence) || ! is_int($evidence[$field]) || $evidence[$field] < 0) {
                throw new \RuntimeException("campo {$field} ausente ou inválido");
            }
        }
        try {
            $executedAt = Carbon::parse($evidence['executed_at']);
            $periodStart = Carbon::parse($evidence['period_start']);
            $periodEnd = Carbon::parse($evidence['period_end']);
        } catch (\Throwable) {
            throw new \RuntimeException('datas inválidas');
        }
        $checkedTotal = $evidence['charges_checked'] + $evidence['payments_checked']
            + $evidence['refunds_checked'] + $evidence['settlements_checked'];
        if ($executedAt->isFuture() || $executedAt->lt(now()->subHours($configured['max_age_hours']))
            || ! in_array($evidence['environment'], ['sandbox', 'test'], true)
            || ! preg_match('/^[A-Z]{3}$/', $evidence['currency'])
            || $periodStart->gte($periodEnd) || $periodEnd->gt($executedAt)
            || $evidence['sample_size'] < $configured['min_sample_size']
            || $checkedTotal !== $evidence['sample_size'] || $evidence['matched_total'] !== $evidence['sample_size']
            || $evidence['ledger_entries_checked'] < $evidence['payments_checked']
            || $evidence['unmatched_payments'] !== 0 || $evidence['unmatched_ledger_entries'] !== 0
            || $evidence['duplicate_transactions'] !== 0 || $evidence['amount_mismatch_count'] !== 0
            || $evidence['settlement_mismatch_count'] !== 0 || (float) $evidence['unresolved_difference_amount'] !== 0.0
            || $evidence['retest_required'] && ! $evidence['retest_completed']) {
            throw new \RuntimeException('evidência não atende aos critérios de aprovação');
        }
    }

    /** @param array<string, mixed>|null $evidence */
    private function validUatEvidenceBlock(string $block, ?array &$evidence): bool
    {
        try {
            if ($evidence === null) {
                $path = $this->option('uat-evidence') ?: storage_path('app/release-candidate/uat-evidence.json');
                if (! is_file($path) || ! is_readable($path)) {
                    throw new \RuntimeException('arquivo ausente ou ilegível');
                }
                $evidence = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
                $configured = config('release.uat');
                if (! is_array($evidence) || ! is_array($configured)
                    || ($evidence['schema_version'] ?? null) !== $configured['schema_version']
                    || ($evidence['result'] ?? null) !== 'PASS'
                    || ! in_array($evidence['environment'] ?? null, ['sandbox', 'test', 'staging'], true)) {
                    throw new \RuntimeException('contrato inválido');
                }
                foreach (['executed_at', 'evidence_reference', 'approved_by'] as $field) {
                    if (! is_string($evidence[$field] ?? null) || trim($evidence[$field]) === '') {
                        throw new \RuntimeException("campo {$field} inválido");
                    }
                }
                try {
                    $executedAt = Carbon::parse($evidence['executed_at']);
                } catch (\Throwable) {
                    throw new \RuntimeException('executed_at inválido');
                }
                if ($executedAt->isFuture() || $executedAt->lt(now()->subHours($configured['max_age_hours']))) {
                    throw new \RuntimeException('evidência expirada');
                }
            }

            $data = $evidence[$block] ?? null;
            if (! is_array($data)) {
                throw new \RuntimeException("{$block} ausente");
            }
            foreach (['scenarios_total', 'scenarios_passed', 'scenarios_failed', 'blockers_open', 'critical_open', 'high_open'] as $field) {
                if (! array_key_exists($field, $data) || ! is_int($data[$field]) || $data[$field] < 0) {
                    throw new \RuntimeException("{$block}.{$field} inválido");
                }
            }
            foreach (['signer', 'notes_reference'] as $field) {
                if (! is_string($data[$field] ?? null) || trim($data[$field]) === '') {
                    throw new \RuntimeException("{$block}.{$field} inválido");
                }
            }
            $scopeKey = $block === 'merchant_uat' ? 'merchant_required_scope' : 'admin_required_scope';
            $scope = is_array($data['scope'] ?? null) ? array_values(array_unique(array_filter($data['scope'], 'is_string'))) : [];
            if ($data['scenarios_total'] <= 0 || $data['scenarios_total'] !== $data['scenarios_passed'] + $data['scenarios_failed']
                || $data['scenarios_failed'] > 0 || $data['blockers_open'] > 0 || $data['critical_open'] > 0 || $data['high_open'] > 0
                || ($data['acceptance_signed'] ?? null) !== true || array_diff(config("release.uat.{$scopeKey}"), $scope) !== []) {
                throw new \RuntimeException("{$block} não aprovado");
            }

            return true;
        } catch (JsonException|\RuntimeException $exception) {
            $this->warn("Evidência {$block} inválida: ".$exception->getMessage());

            return false;
        }
    }

    private function validRollbackEvidence(): bool
    {
        $path = $this->option('rollback-evidence') ?: storage_path('app/release-candidate/rollback-evidence.json');
        try {
            if (! is_file($path) || ! is_readable($path)) {
                throw new \RuntimeException('arquivo ausente ou ilegível');
            }
            $e = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            if (! is_array($e)) {
                throw new \RuntimeException('estrutura inválida');
            }
            $this->assertRollbackEvidence($e);
        } catch (JsonException|\RuntimeException $x) {
            $this->warn('Evidência rollback inválida: '.$x->getMessage());

            return false;
        }

        return true;
    }

    private function assertRollbackEvidence(array $e): void
    {
        $c = config('release.rollback');
        foreach (['executed_at', 'environment', 'release_version', 'previous_version', 'rollback_started_at', 'rollback_completed_at', 'evidence_reference', 'approved_by'] as $f) {
            if (! is_string($e[$f] ?? null) || trim($e[$f]) === '') {
                throw new \RuntimeException("{$f} inválido");
            }
        }
        if (! preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,127}$/', $e['release_version']) || ! preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,127}$/', $e['previous_version']) || $e['release_version'] === $e['previous_version']) {
            throw new \RuntimeException('versões inválidas');
        }
        if (! is_array($c) || ($e['schema_version'] ?? null) !== $c['schema_version'] || ($e['result'] ?? null) !== 'PASS' || ! in_array($e['environment'], ['sandbox', 'test', 'staging'], true) || ! is_int($e['duration_seconds'] ?? null) || $e['duration_seconds'] <= 0) {
            throw new \RuntimeException('contrato inválido');
        }
        try {
            $at = Carbon::parse($e['executed_at']);
            $start = Carbon::parse($e['rollback_started_at']);
            $end = Carbon::parse($e['rollback_completed_at']);
        } catch (\Throwable) {
            throw new \RuntimeException('datas inválidas');
        }
        if ($at->isFuture() || $at->lt(now()->subHours($c['max_age_hours'])) || $start->gte($end) || $end->gt($at) || (int) $start->diffInSeconds($end) !== $e['duration_seconds']) {
            throw new \RuntimeException('timestamps incoerentes');
        }
        foreach (['application_rollback', 'database_rollback', 'workers_rollback', 'health_validation', 'financial_integrity'] as $f) {
            if (! is_array($e[$f] ?? null)) {
                throw new \RuntimeException("{$f} ausente");
            }
        }
        $a = $e['application_rollback'];
        foreach (['attempted', 'completed', 'version_restored', 'config_restored', 'assets_restored'] as $f) {
            if (($a[$f] ?? null) !== true) {
                throw new \RuntimeException("app {$f}");
            }
        } if (($a['errors_count'] ?? null) !== 0) {
            throw new \RuntimeException('app errors');
        }
        $d = $e['database_rollback'];
        foreach (['attempted', 'completed', 'schema_compatible', 'restore_tested'] as $f) {
            if (($d[$f] ?? null) !== true) {
                throw new \RuntimeException("db {$f}");
            }
        } if (! is_bool($d['migrations_reversible'] ?? null) || (! $d['migrations_reversible'] && ! in_array($d['strategy'] ?? null, $c['safe_database_strategies'], true)) || ! is_string($d['strategy'] ?? null) || trim($d['strategy']) === '' || ! is_string($d['backup_reference'] ?? null) || trim($d['backup_reference']) === '' || ($d['data_loss_detected'] ?? null) !== false || ($d['errors_count'] ?? null) !== 0) {
            throw new \RuntimeException('db inseguro');
        }
        $w = $e['workers_rollback'];
        foreach (['attempted', 'completed', 'old_workers_stopped', 'target_workers_started', 'queue_compatible'] as $f) {
            if (($w[$f] ?? null) !== true) {
                throw new \RuntimeException("workers {$f}");
            }
        } foreach (['jobs_lost', 'jobs_duplicated', 'errors_count'] as $f) {
            if (($w[$f] ?? null) !== 0) {
                throw new \RuntimeException("workers {$f}");
            }
        }
        foreach (['liveness_ok', 'readiness_ok', 'deep_health_ok', 'critical_dependencies_ok'] as $f) {
            if (($e['health_validation'][$f] ?? null) !== true) {
                throw new \RuntimeException("health {$f}");
            }
        }
        $f = $e['financial_integrity'];
        if (($f['ledger_balanced'] ?? null) !== true || ! is_string($f['currency'] ?? null) || ! preg_match('/^[A-Z]{3}$/', $f['currency'])) {
            throw new \RuntimeException('financial inválido');
        } foreach (['duplicate_transactions', 'missing_transactions', 'amount_mismatch_count', 'settlement_mismatch_count'] as $k) {
            if (($f[$k] ?? null) !== 0) {
                throw new \RuntimeException("financial {$k}");
            }
        } if (! isset($f['unresolved_difference_amount']) || ! is_numeric($f['unresolved_difference_amount']) || (float) $f['unresolved_difference_amount'] !== 0.0) {
            throw new \RuntimeException('diferença financeira');
        }
    }
}
