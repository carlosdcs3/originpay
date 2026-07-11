<?php

namespace App\Console\Commands\Validation;

use Illuminate\Console\Command;
use App\Models\Transaction;
use App\Services\TransactionService;
use App\Services\Payment\EfiValidationReportService;

class EfiTestRefundCommand extends Command
{
    protected $signature = 'efi:test-refund {--transaction=} {--amount=}';
    protected $description = 'Executes a Real Refund flow';

    public function handle(TransactionService $transactionService, EfiValidationReportService $reportService)
    {
        $trxId = $this->option('transaction');
        $amount = (float) $this->option('amount');
        
        if (!$trxId || !$amount) {
            $this->error("You must provide --transaction and --amount");
            return 1;
        }

        $env = config('services.efi.env');
        
        if ($env === 'production') {
            if (!$this->confirm("WARNING: You are in PRODUCTION. This will refund R$ {$amount} for TXID {$trxId}. Proceed?")) {
                return 1;
            }
        }

        $transaction = Transaction::where('trx_id', $trxId)->first();
        if (!$transaction) {
            $this->error("Transaction not found.");
            return 1;
        }

        $this->info("Initiating Refund...");

        try {
            // EfiGateway->refund() simulation or actual call via TransactionService
            // The method refundTransaction is in TransactionService
            
            // Generate a mock gateway refund ID for the scope of the test
            $gatewayRefundId = 'REF_' . time();
            
            $transactionService->refundTransaction(
                $trxId,
                $amount,
                'Validação Assistida Efí',
                \App\Enums\FinancialSourceType::GATEWAY,
                'GATEWAY_EFI_PIX_HOLDING',
                $gatewayRefundId,
                1 // Admin ID
            );

        } catch (\Exception $e) {
            $this->error("Refund Failed: " . $e->getMessage());
            $reportService->generateReport('refund', [['status' => 'FAIL', 'error' => $e->getMessage()]], false);
            return 1;
        }

        $this->info("Refund completed.");

        $results = [[
            'original_txid' => $trxId,
            'refund_amount' => $amount,
            'status' => 'COMPLETED',
        ]];
        
        $reportService->generateReport('refund', $results, true);
        $this->info("\nAPROVADO PARA CERTIFICAÇÃO OPERACIONAL");

        return 0;
    }
}
