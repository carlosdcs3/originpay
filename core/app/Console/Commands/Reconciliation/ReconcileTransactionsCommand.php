<?php

namespace App\Console\Commands\Reconciliation;

use Illuminate\Console\Command;
use App\Models\Transaction;

class ReconcileTransactionsCommand extends Command
{
    protected $signature = 'reconcile:transactions {--days=7}';
    protected $description = 'Finds orphaned transactions or logical inconsistencies.';

    public function handle()
    {
        $days = (int) $this->option('days');
        $this->info("Starting Transaction Logic Reconciliation for last {$days} days...");

        $since = now()->subDays($days);
        $transactions = Transaction::where('created_at', '>=', $since)->get();

        $anomalies = [];

        foreach ($transactions as $tx) {
            // Regra: Transação pendente por mais de 3 dias
            if ($tx->status === 'PENDING' && $tx->created_at->diffInDays(now()) > 3) {
                $anomalies[] = [
                    $tx->trx, $tx->user_id, 'ORPHANED_PENDING', $tx->created_at
                ];
            }

            // Regra: Refund duplicado no mesmo webhook (mock check)
            // Se houver lógica de checagem
        }

        if (count($anomalies) > 0) {
            $this->warn("Found " . count($anomalies) . " transaction anomalies.");
            
            $filename = "reconciliation/transactions_" . now()->format('Y_m_d_H_i_s') . ".csv";
            $path = storage_path("logs/" . $filename);
            
            @mkdir(dirname($path), 0755, true);
            $file = fopen($path, 'w');
            fputcsv($file, ['Trx ID', 'User ID', 'Anomaly Type', 'Created At']);
            foreach ($anomalies as $row) {
                fputcsv($file, $row);
            }
            fclose($file);

            $this->info("Report generated at: {$path}");
        } else {
            $this->info("Transactions are reconciled.");
        }
    }
}
