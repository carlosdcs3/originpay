<?php

namespace App\Console\Commands\Reconciliation;

use Illuminate\Console\Command;
use App\Services\Payment\EfiReconciliationService;

class ReconcileEfiBalanceCommand extends Command
{
    protected $signature = 'reconcile:efi-balance';
    protected $description = 'Reconciles local holding wallets with actual EFI account balance';

    public function handle(EfiReconciliationService $reconciliationService)
    {
        $this->info("Starting EFI Balance Reconciliation...");
        
        try {
            $reconciliation = $reconciliationService->reconcileBalance();
            
            $this->table(
                ['Provider', 'Expected Balance', 'Actual Balance', 'Difference', 'Status'],
                [
                    [
                        $reconciliation->provider,
                        number_format($reconciliation->expected_balance, 2),
                        number_format($reconciliation->actual_balance, 2),
                        number_format($reconciliation->difference, 2),
                        $reconciliation->status
                    ]
                ]
            );

            if ($reconciliation->status !== 'GREEN') {
                $this->warn("Reconciliation concluded with status: {$reconciliation->status}. Difference: R$ {$reconciliation->difference}");
            } else {
                $this->info("Reconciliation successful. Accounts match exactly.");
            }

        } catch (\Exception $e) {
            $this->error("Failed to reconcile balance: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
