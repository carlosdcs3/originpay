<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Charge as EloquentCharge;
use App\Enums\ChargeStatus;
use App\Services\Payments\ChargeService;
use App\Services\Gateways\GatewayManager;
use App\Domain\Auth\MerchantContext;

class ReconcilePendingCharges extends Command
{
    protected $signature = 'originpay:reconcile';
    protected $description = 'Reconcile pending charges with their gateways to ensure consistency';

    public function handle(ChargeService $chargeService, GatewayManager $gatewayManager)
    {
        $this->info("Starting reconciliation of pending charges...");

        $pendingCharges = EloquentCharge::where('status', ChargeStatus::PENDING->value)
            ->whereNotNull('metadata->internal_metadata->txid') // EFI Pix example
            ->get();

        $count = 0;
        foreach ($pendingCharges as $model) {
            try {
                $merchantContext = new MerchantContext($model->merchant_id, $model->environment);
                $charge = $chargeService->getCharge($model->charge_id, $merchantContext);
                
                if (!$charge) continue;

                $txid = $charge->internalMetadata['txid'] ?? null;
                if (!$txid) continue;

                // Currently fixed to 'efi' for Pix. Real implementation would check the actual gateway used.
                $gatewayResult = $gatewayManager->getStatus($charge->merchantId, $charge->environment, 'efi', $txid);

                if ($gatewayResult->success && $gatewayResult->status === 'succeeded') {
                    $chargeService->updateChargeStatus($charge, ChargeStatus::SUCCEEDED, 'System Reconciliation');
                    $count++;
                    $this->line("Charge {$charge->id} synchronized to SUCCEEDED.");
                } elseif ($gatewayResult->status === 'cancelled' || $gatewayResult->status === 'failed') {
                    $chargeService->updateChargeStatus($charge, ChargeStatus::FAILED, 'System Reconciliation');
                    $count++;
                    $this->line("Charge {$charge->id} synchronized to FAILED.");
                }
            } catch (\Exception $e) {
                $this->error("Failed to reconcile charge {$model->charge_id}: " . $e->getMessage());
            }
        }

        $this->info("Reconciliation completed. {$count} charges updated.");
    }
}
