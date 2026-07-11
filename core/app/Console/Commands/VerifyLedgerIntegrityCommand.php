<?php

namespace App\Console\Commands;

use App\Models\WalletTransaction;
use Illuminate\Console\Command;

class VerifyLedgerIntegrityCommand extends Command
{
    protected $signature = 'ledger:verify-integrity {--wallet= : Verify a specific wallet id or uuid}';

    protected $description = 'Verifica a cadeia de integridade (HMAC) do Ledger absoluto contra mutacoes no banco de dados.';

    public function handle(): int
    {
        $this->info('Iniciando varredura de integridade do Ledger...');

        $walletOption = $this->option('wallet');
        $query = WalletTransaction::query();

        if ($walletOption) {
            $query->where(function ($query) use ($walletOption) {
                $query->where('wallet_id', $walletOption)
                    ->orWhereHas('wallet', fn ($walletQuery) => $walletQuery->where('uuid', $walletOption));
            });
        }

        $transactions = $query->orderBy('wallet_id')->orderBy('id', 'asc')->get();
        $secret = config('app.key');
        $lastHashes = [];

        $chainBreaks = 0;
        $missingHashes = 0;
        $hashMismatches = 0;

        $bar = $this->output->createProgressBar($transactions->count());

        foreach ($transactions as $transaction) {
            $expectedPreviousHash = $lastHashes[$transaction->wallet_id] ?? null;

            if ($transaction->previous_integrity_hash !== $expectedPreviousHash) {
                $this->error("\nchain_break: Transacao ID {$transaction->id} na Wallet {$transaction->wallet_id} possui previous_hash divergente.");
                $chainBreaks++;
            }

            if ($transaction->integrity_hash === null) {
                $this->warn("\nmissing_hash: Transacao ID {$transaction->id} na Wallet {$transaction->wallet_id}. Registro sem baseline de integridade detectado. Provavel dado legado/pre-migration.");
                $missingHashes++;
                $lastHashes[$transaction->wallet_id] = $transaction->integrity_hash;
                $bar->advance();
                continue;
            }

            $calculatedHash = $this->calculateHash($transaction, $secret);

            if ($calculatedHash !== $transaction->integrity_hash) {
                $this->error("\nhash_mismatch: Transacao ID {$transaction->id} na Wallet {$transaction->wallet_id}. Hash invalido. Possivel alteracao de dados.");
                $hashMismatches++;
            }

            $lastHashes[$transaction->wallet_id] = $transaction->integrity_hash;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->table(
            ['Registros', 'missing_hash', 'hash_mismatch', 'chain_break'],
            [[$transactions->count(), $missingHashes, $hashMismatches, $chainBreaks]]
        );

        if ($hashMismatches > 0 || $chainBreaks > 0) {
            $this->error("CRITICO: A integridade do Ledger foi comprometida. hash_mismatch={$hashMismatches}; chain_break={$chainBreaks}; missing_hash={$missingHashes}.");
            return Command::FAILURE;
        }

        if ($missingHashes > 0) {
            $this->warn("Verificacao concluida com warning: {$missingHashes} registros sem baseline de integridade. Provavel dado legado/pre-migration; nao classificado como corrupcao.");
            return Command::SUCCESS;
        }

        $this->info('Verificacao concluida: Cadeia de blocos intacta. Nenhuma violacao encontrada.');

        return Command::SUCCESS;
    }

    private function calculateHash(WalletTransaction $transaction, string $secret): string
    {
        $payload = implode('|', [
            $transaction->wallet_id,
            $transaction->amount,
            $transaction->type,
            $transaction->balance_before,
            $transaction->balance_after,
            $transaction->correlation_id,
            $transaction->idempotency_key,
            $transaction->previous_integrity_hash,
        ]);

        return hash_hmac('sha256', $payload, $secret);
    }
}
