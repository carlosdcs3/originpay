<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Wallet;
use App\Services\Financial\WalletBalanceService;

class RebuildWalletBalancesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wallet:rebuild-balances {userId?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reconstrói o saldo das carteiras lendo o Ledger absoluto';

    /**
     * Execute the console command.
     */
    public function handle(WalletBalanceService $walletBalanceService)
    {
        $userId = $this->argument('userId');

        if ($userId) {
            $wallets = Wallet::where('user_id', $userId)->get();
        } else {
            $wallets = Wallet::all();
            $this->warn("Atenção: Você está prestes a reconstruir o saldo de TODAS as carteiras do sistema.");
            if (!$this->confirm('Deseja continuar?')) {
                return Command::SUCCESS;
            }
        }

        $this->info("Iniciando reconstrução de saldos (" . $wallets->count() . " carteiras)...");

        $bar = $this->output->createProgressBar($wallets->count());

        foreach ($wallets as $wallet) {
            try {
                $walletBalanceService->rebuildBalance($wallet->id);
            } catch (\Exception $e) {
                $this->error("\nErro ao reconstruir carteira {$wallet->id}: " . $e->getMessage());
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Reconstrução concluída com sucesso!");

        return Command::SUCCESS;
    }
}
