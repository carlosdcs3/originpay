<?php

namespace App\Console\Commands\Reconciliation;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Ledger;
use Illuminate\Support\Facades\DB;

class ReconcileLedgerCommand extends Command
{
    protected $signature = 'reconcile:ledger';
    protected $description = 'Checks Ledger sum against Wallet balance for discrepancies.';

    public function handle()
    {
        $this->info("Starting Ledger vs Wallet Reconciliation...");

        $anomalies = [];

        // This is pseudo-code for the reconciliation logic.
        // Assumes Wallet model has balance and Ledger has credit/debit or amount.
        // Assuming wallet table structure: wallets (id, user_id, balance)
        // Assuming ledger table: ledgers (id, user_id, amount, type [credit, debit])

        $users = User::all();

        foreach ($users as $user) {
            // Simplified sum. Adjust to actual schema.
            // If the schema uses `transactions` for ledger, we adapt.
            // Digikash v1.0.5 uses transactions table and user balance
            $txSum = DB::table('transactions')
                ->where('user_id', $user->id)
                ->where('status', '!=', 'REJECTED') // assuming logic
                ->sum(DB::raw('CASE WHEN trx_type = "+" THEN amount ELSE -amount END'));

            // Assume $user->balance is the wallet balance
            $balance = $user->balance ?? 0;

            // Float precision compare
            if (abs($txSum - $balance) > 0.01) {
                $anomalies[] = [
                    $user->id, $user->username, $balance, $txSum, round($balance - $txSum, 2)
                ];
            }
        }

        if (count($anomalies) > 0) {
            $this->warn("Found " . count($anomalies) . " ledger mismatches.");
            
            $filename = "reconciliation/ledger_" . now()->format('Y_m_d_H_i_s') . ".csv";
            $path = storage_path("logs/" . $filename);
            
            @mkdir(dirname($path), 0755, true);
            $file = fopen($path, 'w');
            fputcsv($file, ['User ID', 'Username', 'Current Balance', 'Tx Sum', 'Difference']);
            foreach ($anomalies as $row) {
                fputcsv($file, $row);
            }
            fclose($file);

            $this->info("Report generated at: {$path}");
        } else {
            $this->info("Ledger is perfectly reconciled.");
        }
    }
}
