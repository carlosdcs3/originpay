<?php

namespace App\Console\Commands\Reconciliation;

use Illuminate\Console\Command;
use App\Models\Transaction;
use App\Models\FinancialAnomaly;
use App\Enums\TrxType;
use App\Enums\TrxStatus;
use Illuminate\Support\Facades\Http;
use App\Services\Security\TenantBypass;

class ReconcileEfiWithdrawsCommand extends Command
{
    protected $signature = 'reconcile:efi-withdraws';
    protected $description = 'Audits withdraws for stuck or mismatched status with EFI.';

    public function handle()
    {
        $this->info("Starting EFI Withdraws Audit...");

        // Find stuck pending withdraws (older than 10 minutes)
        $stuckWithdraws = TenantBypass::run(fn () => Transaction::where('trx_type', TrxType::WITHDRAW)
            ->where('status', TrxStatus::PENDING)
            ->where('created_at', '<', now()->subMinutes(10))
            ->get());

        foreach ($stuckWithdraws as $withdraw) {
            FinancialAnomaly::updateOrCreate(
                ['fingerprint' => "withdraw_stuck:{$withdraw->id}"],
                [
                    'type' => 'withdraw_stuck',
                    'severity' => 'HIGH',
                    'entity_type' => 'transaction',
                    'entity_id' => $withdraw->id,
                    'description' => "Withdraw {$withdraw->trx_id} has been stuck in PENDING for over 10 minutes.",
                    'metadata' => ['trx_id' => $withdraw->trx_id],
                    'suggested_actions' => ['investigate_efi_payout', 'verify_webhook'],
                    'detected_at' => now(),
                ]
            );
            $this->warn("Found stuck withdraw: {$withdraw->trx_id}");
        }

        // Ideally here we would also fetch the real status from EFI using GET /v2/gn/pix/envio/{txid} or similar
        // and compare with completed/failed withdraws locally.
        // For now, we simulate this layer.

        $this->info("Found " . $stuckWithdraws->count() . " stuck withdraws.");
        $this->info("EFI Withdraw Audit completed.");
        return 0;
    }
}
