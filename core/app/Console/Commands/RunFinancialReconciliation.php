<?php

namespace App\Console\Commands;

use App\Services\Finance\ReconciliationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RunFinancialReconciliation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'finance:reconcile 
                            {--days=1 : Number of past days to check transactions}
                            {--wallet= : Reconcile a specific wallet ID}
                            {--charge= : Reconcile a specific transaction (trx_id)}
                            {--gateway= : Reconcile a specific gateway code}
                            {--from= : Start date (YYYY-MM-DD)}
                            {--to= : End date (YYYY-MM-DD)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Executa a reconciliação cruzada entre PSP, Ledger e Wallets';

    /**
     * Execute the console command.
     */
    public function handle(ReconciliationService $service)
    {
        $this->info('Starting Financial Reconciliation...');
        $startTime = microtime(true);

        $dateStart = $this->option('from') ? $this->option('from') . ' 00:00:00' : now()->subDays((int)$this->option('days'))->startOfDay()->toDateTimeString();
        $dateEnd = $this->option('to') ? $this->option('to') . ' 23:59:59' : now()->toDateTimeString();

        $filters = [
            'wallet_id' => $this->option('wallet'),
            'trx_id' => $this->option('charge'),
            'gateway' => $this->option('gateway'),
            'dateStart' => $dateStart,
            'dateEnd' => $dateEnd,
        ];

        $this->info("Checking transactions from {$dateStart} to {$dateEnd}");

        $allDiscrepancies = [];

        // 1. Reconcile Wallets (Ledger vs Wallet Balance)
        $this->line('Reconciling Wallets (Ledger vs Balance)...');
        $walletDiffs = $service->reconcileWallets(500, $filters); // chunk size 500
        $allDiscrepancies = array_merge($allDiscrepancies, $walletDiffs);

        // 2. Reconcile Transactions (Status vs Ledger)
        $this->line('Reconciling Transactions (Status vs Ledger)...');
        $trxDiffs = $service->reconcileTransactions($dateStart, $dateEnd, 1000, $filters); // chunk size 1000
        $allDiscrepancies = array_merge($allDiscrepancies, $trxDiffs);

        // 3. Reconcile Webhooks (PSP vs Ledger)
        $this->line('Reconciling Webhooks...');
        $webhookDiffs = $service->reconcileWebhooks($dateStart, $dateEnd, $filters);
        $allDiscrepancies = array_merge($allDiscrepancies, $webhookDiffs);

        $duration = microtime(true) - $startTime;

        if (count($allDiscrepancies) > 0) {
            $this->error('Discrepancies found!');
            
            $criticalCount = 0;
            $warningCount = 0;

            foreach ($allDiscrepancies as $diff) {
                if ($diff['type'] === 'CRITICAL') {
                    $criticalCount++;
                    Log::critical('Financial Reconciliation Failure', $diff);
                } else {
                    $warningCount++;
                    Log::warning('Financial Reconciliation Warning', $diff);
                }
            }

            // Simulate sending to Discord/Ops if Critical
            if ($criticalCount > 0) {
                // app(PlatformAlertService::class)->sendDiscordAlert(...)
                $this->error("Triggered CRITICAL alert to Operations. ({$criticalCount} critical issues)");
            }

            $this->table(
                ['Type', 'Message', 'Wallet / Trx'],
                array_map(function ($diff) {
                    return [
                        $diff['type'],
                        $diff['message'],
                        $diff['wallet_uuid'] ?? $diff['trx_id'] ?? 'N/A'
                    ];
                }, $allDiscrepancies)
            );
        } else {
            $this->info('Reconciliation completed successfully. No discrepancies found.');
        }

        $this->line("Time elapsed: " . round($duration, 2) . "s");

        return count($allDiscrepancies) > 0 ? 1 : 0;
    }
}
