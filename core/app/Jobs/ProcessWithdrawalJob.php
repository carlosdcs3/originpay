<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\WithdrawalRequest;
use App\Services\Payment\WithdrawalService;
use Illuminate\Support\Facades\Log;

class ProcessWithdrawalJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;
    
    protected int $withdrawalId;

    public function __construct(int $withdrawalId)
    {
        $this->withdrawalId = $withdrawalId;
    }

    public function handle(WithdrawalService $withdrawalService)
    {
        $request = WithdrawalRequest::find($this->withdrawalId);
        
        if (!$request || $request->status !== 'APPROVED') {
            return;
        }

        try {
            $withdrawalService->processWithdrawal($request);

            // Here we emulate sending to EfiGateway...
            // $gateway = new \App\Payment\Modern\Providers\EfiGateway();
            // $response = $gateway->sendPix($request);
            $simulatedEfiTxId = 'EFI_' . time();
            
            $withdrawalService->completeWithdrawal($request, $simulatedEfiTxId);

        } catch (\Exception $e) {
            Log::error("Withdrawal processing failed: " . $e->getMessage());
            $withdrawalService->failWithdrawal($request, $e->getMessage());
            
            // Optionally, if it's a network error we could retry. 
            // If it's Liquidity risk, we fail it to be reviewed.
        }
    }
}
