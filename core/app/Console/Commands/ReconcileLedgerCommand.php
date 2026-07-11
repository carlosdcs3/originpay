<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Financial\FinancialReconciliationService;

class ReconcileLedgerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ledger:reconcile {date?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reconcilia o Ledger com as transações pagas nos gateways e alerta inconsistências.';

    /**
     * Execute the console command.
     */
    public function handle(FinancialReconciliationService $reconciliationService)
    {
        $date = $this->argument('date');
        $this->info("Iniciando reconciliação financeira" . ($date ? " para a data $date" : "") . "...");

        $inconsistencies = $reconciliationService->reconcileCharges($date);

        if (empty($inconsistencies)) {
            $this->info('Reconciliação concluída: 0 divergências encontradas. O Ledger está perfeito.');
            return Command::SUCCESS;
        }

        $this->error('ATENÇÃO! Foram encontradas ' . count($inconsistencies) . ' divergências financeiras.');
        
        foreach ($inconsistencies as $inc) {
            $this->warn("Charge ID: {$inc['charge_id']} | Tipo: {$inc['type']} | Motivo: {$inc['message']}");
        }

        return Command::FAILURE;
    }
}
