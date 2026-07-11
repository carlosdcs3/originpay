<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Wallet;
use App\Models\PaymentGateway;
use App\Models\WalletBalance;
use App\Models\Transaction;
use App\Enums\AmountFlow;
use App\Enums\TrxStatus;
use App\Enums\TrxType;
use App\Services\Security\TenantBypass;
use Illuminate\Support\Facades\DB;

class MigrateGatewayBalances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wallets:migrate-gateway-balances';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate legacy consolidated wallet balances to the new ledger-per-gateway structure.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        return TenantBypass::run(function () {
        $this->info('Starting legacy gateway balances migration...');

        // Identificar o gateway primário ativo (vamos usar o primeiro ativo ou o mais comum)
        // Idealmente, poderíamos buscar por provider == 'efi' ou o de maior prioridade.
        $primaryGateway = PaymentGateway::where('status', true)->first();

        if (!$primaryGateway) {
            $this->error('CRITICAL: No active primary Gateway found. Aborting migration to prevent data loss or misallocation.');
            return;
        }

        $this->info("Selected Primary Gateway for Migration: {$primaryGateway->name} (ID: {$primaryGateway->id})");

        $wallets = Wallet::where('balance', '>', 0)->get();
        $totalWallets = $wallets->count();
        $migratedWallets = 0;
        $totalAmount = 0;
        $ignoredWallets = 0;
        $errors = 0;

        $bar = $this->output->createProgressBar($totalWallets);

        foreach ($wallets as $wallet) {
            try {
                DB::transaction(function () use ($wallet, $primaryGateway, &$migratedWallets, &$totalAmount, &$ignoredWallets) {
                    $walletLock = Wallet::where('id', $wallet->id)->lockForUpdate()->first();

                    if ($walletLock->balance <= 0) {
                        $ignoredWallets++;
                        return;
                    }

                    // Verifica se já não foi migrado
                    $exists = WalletBalance::where('wallet_id', $walletLock->id)->exists();
                    if ($exists) {
                        $ignoredWallets++;
                        return;
                    }

                    // Create Ledger Balance
                    $balance = new WalletBalance();
                    $balance->wallet_id = $walletLock->id;
                    $balance->gateway_id = $primaryGateway->id;
                    $balance->available = $walletLock->balance;
                    $balance->pending = 0;
                    $balance->blocked = 0;
                    $balance->save();

                    // Create audit transaction
                    $trx = new Transaction();
                    $trx->user_id = $walletLock->user_id;
                    $trx->gateway_id = $primaryGateway->id;
                    $trx->trx_type = TrxType::ADJUSTMENT;
                    $trx->amount_flow = AmountFlow::PLUS;
                    $trx->amount = $walletLock->balance;
                    $trx->net_amount = $walletLock->balance;
                    $trx->fee = 0;
                    $trx->currency = $walletLock->currency->currency_code ?? 'BRL';
                    $trx->payable_amount = $walletLock->balance;
                    $trx->payable_currency = $trx->currency;
                    $trx->processing_type = 'auto';
                    $trx->operation = 'LEGACY_MIGRATION';
                    $trx->description = 'Migração de saldo legado consolidado para orquestração por gateway.';
                    $trx->status = TrxStatus::COMPLETED;
                    $trx->save();

                    $migratedWallets++;
                    $totalAmount += $walletLock->balance;
                });
            } catch (\Exception $e) {
                $errors++;
                $this->error("Failed to migrate wallet ID {$wallet->id}: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Migration Report:");
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Wallets Analyzed', $totalWallets],
                ['Wallets Migrated', $migratedWallets],
                ['Total Volume Migrated', '$' . number_format($totalAmount, 2)],
                ['Wallets Ignored (Zero balance or already migrated)', $ignoredWallets],
                ['Errors Encountered', $errors],
                ['Destination Gateway', $primaryGateway->name . ' (ID: ' . $primaryGateway->id . ')'],
            ]
        );
        });
    }
}
