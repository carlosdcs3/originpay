<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Wallet;
use App\Models\PaymentGateway;
use App\Models\WalletBalance;
use Illuminate\Support\Facades\DB;

class AuditGatewayBalances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wallets:audit-gateway-balances';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audit wallets to ensure ledger consistency between consolidated balance and gateway balances.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Gateway Balances Audit...');

        $wallets = Wallet::all();
        $totalWallets = $wallets->count();
        $divergences = 0;
        $noBreakdown = 0;
        $invalidGateways = 0;
        $negativeBalances = 0;
        
        $bar = $this->output->createProgressBar($totalWallets);

        foreach ($wallets as $wallet) {
            // Check for negative balances
            if ($wallet->balance < 0) {
                $negativeBalances++;
                $this->error("\nNegative balance found in Wallet ID {$wallet->id}: {$wallet->balance}");
            }

            $balances = WalletBalance::where('wallet_id', $wallet->id)->get();

            // Check if wallet has balance but no breakdown
            if ($wallet->balance > 0 && $balances->isEmpty()) {
                $noBreakdown++;
                $this->error("\nWallet ID {$wallet->id} has balance ({$wallet->balance}) but no gateway breakdown.");
            }

            $sumAvailable = 0;
            $sumPending = 0;
            $sumBlocked = 0;

            foreach ($balances as $b) {
                if ($b->available < 0 || $b->pending < 0 || $b->blocked < 0) {
                    $negativeBalances++;
                    $this->error("\nNegative ledger balance found in WalletBalance ID {$b->id}");
                }

                if (!PaymentGateway::find($b->gateway_id)) {
                    $invalidGateways++;
                    $this->error("\nInvalid Gateway ID {$b->gateway_id} found in WalletBalance ID {$b->id}");
                }

                $sumAvailable += $b->available;
                $sumPending += $b->pending;
                $sumBlocked += $b->blocked;
            }

            // Only check available balance consistency for now
            // Or maybe sum all? Usually wallet balance is just 'available', but depends on system rules.
            // Let's assume wallet->balance should equal sum of available.
            $epsilon = 0.00001; // For float comparison
            if (abs($wallet->balance - $sumAvailable) > $epsilon && $balances->isNotEmpty()) {
                $divergences++;
                $this->error("\nDivergence in Wallet ID {$wallet->id}: Consolidated {$wallet->balance} != Sum of Available {$sumAvailable}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Audit Report:");
        $this->table(
            ['Metric', 'Value', 'Status'],
            [
                ['Total Wallets Checked', $totalWallets, 'INFO'],
                ['Wallets with Divergences', $divergences, $divergences > 0 ? '<fg=red>FAIL</>' : '<fg=green>PASS</>'],
                ['Wallets with No Breakdown (Legacy)', $noBreakdown, $noBreakdown > 0 ? '<fg=yellow>WARNING</>' : '<fg=green>PASS</>'],
                ['Records with Invalid Gateways', $invalidGateways, $invalidGateways > 0 ? '<fg=red>FAIL</>' : '<fg=green>PASS</>'],
                ['Wallets with Negative Balances', $negativeBalances, $negativeBalances > 0 ? '<fg=red>FAIL</>' : '<fg=green>PASS</>'],
            ]
        );

        if ($divergences == 0 && $invalidGateways == 0 && $negativeBalances == 0) {
            $this->info('Audit passed successfully. Ledger is consistent.');
        } else {
            $this->error('Audit failed. Ledger inconsistencies found.');
        }
    }
}
