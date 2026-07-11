<?php

namespace App\Console\Commands\Validation;

use Illuminate\Console\Command;
use App\Models\Transaction;
use App\Enums\TrxType;
use App\Enums\MethodType;
use App\Enums\TrxStatus;
use App\Services\TransactionService;
use App\Data\TransactionData;
use App\Services\Payment\EfiValidationReportService;

class EfiTestPixInCommand extends Command
{
    protected $signature = 'efi:test-pix-in {--amount=1.00} {--timeout=300}';
    protected $description = 'Executes a Real/Sandbox PIX IN flow and monitors the Webhook settlement';

    public function handle(TransactionService $transactionService, EfiValidationReportService $reportService)
    {
        $amount = (float) $this->option('amount');
        $timeout = (int) $this->option('timeout');
        
        $env = config('services.efi.env');
        
        if ($env === 'production') {
            if (!$this->confirm("WARNING: You are in PRODUCTION. This will generate a real PIX charge of R$ {$amount}. Proceed?")) {
                return 1;
            }
        }

        $this->info("Creating PIX IN of R$ {$amount}...");

        $user = \App\Models\User::first(); // Assuming a valid user exists
        $wallet = \App\Models\Wallet::where('user_id', $user->id)->first();

        // Use the Gateway layer (normally called from DepositController)
        $gateway = new \App\Payment\Modern\Providers\EfiGateway();
        
        $dto = new \App\Payment\Modern\DTO\DepositDTO();
        $dto->amount = $amount;
        $dto->userId = $user->id;

        try {
            $pixResponse = $gateway->createPix($dto);
        } catch (\Exception $e) {
            $this->error("Failed to create PIX: " . $e->getMessage());
            $reportService->generateReport('pix-in', [['status' => 'FAIL', 'error' => $e->getMessage()]], false);
            return 1;
        }

        $this->info("PIX Created Successfully!");
        $this->info("TXID: " . $pixResponse->transactionId);
        $this->info("Copia e Cola: " . $pixResponse->copyPasteCode);

        // Create Internal Transaction
        $txData = new TransactionData();
        $txData->user_id = $user->id;
        $txData->trx_type = TrxType::DEPOSIT;
        $txData->amount = $amount;
        $txData->provider = 'EFI';
        $txData->processing_type = MethodType::AUTO;
        $txData->wallet_reference = $wallet->uuid;
        $txData->trx_reference = $pixResponse->transactionId;
        $txData->status = TrxStatus::PENDING;
        
        $transaction = $transactionService->create($txData);
        // Force the tx_id to match provider for webhook reconciliation
        $transaction->trx_id = $pixResponse->transactionId;
        $transaction->save();

        $this->info("\nWaiting for Webhook Payment... (Timeout: {$timeout}s)");

        $startTime = time();
        $paid = false;

        while ((time() - $startTime) < $timeout) {
            $transaction->refresh();
            
            if ($transaction->status === TrxStatus::COMPLETED) {
                $paid = true;
                break;
            }
            
            sleep(5); // Polling every 5 seconds
            $this->output->write('.');
        }

        $this->output->writeln('');

        if ($paid) {
            $this->info("Payment Detected via Webhook!");
            
            // Reconcile via EfiReconciliationService
            app(\App\Console\Commands\Reconciliation\ReconcileEfiBalanceCommand::class)->handle(app(\App\Services\Payment\EfiReconciliationService::class));

            $results = [[
                'txid' => $transaction->trx_id,
                'amount' => $amount,
                'status' => 'COMPLETED',
                'time_taken' => (time() - $startTime) . 's',
            ]];
            $reportService->generateReport('pix-in', $results, true);
            $this->info("\nAPROVADO PARA CERTIFICAÇÃO OPERACIONAL");
        } else {
            $this->error("FAILED_TIMEOUT: No webhook received within {$timeout} seconds.");
            
            $transaction->status = TrxStatus::FAILED;
            $transaction->remarks = 'FAILED_TIMEOUT';
            $transaction->save();

            $results = [[
                'txid' => $transaction->trx_id,
                'amount' => $amount,
                'status' => 'FAILED_TIMEOUT',
                'time_taken' => $timeout . 's',
            ]];
            $reportService->generateReport('pix-in', $results, false);
            $this->error("\nBLOQUEADO POR DIVERGÊNCIAS");
        }

        return 0;
    }
}
