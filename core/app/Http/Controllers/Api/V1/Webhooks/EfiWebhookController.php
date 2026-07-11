<?php

namespace App\Http\Controllers\Api\V1\Webhooks;

use Illuminate\Http\Request;
use App\Models\Charge as EloquentCharge;
use App\Services\Payments\ChargeService;
use App\Services\Gateways\GatewayManager;
use App\Domain\Auth\MerchantContext;
use App\Enums\ChargeStatus;
use Illuminate\Support\Facades\Log;
use App\Models\WebhookDlq;

class EfiWebhookController
{
    public function handle(Request $request, ChargeService $chargeService, GatewayManager $gatewayManager)
    {
        $pixes = $request->input('pix', []);

        foreach ($pixes as $pix) {
            $txid = $pix['txid'] ?? null;
            if (!$txid) continue;

            $chargeModel = EloquentCharge::whereJsonContains('metadata->internal_metadata->txid', $txid)->first();
            if (!$chargeModel) {
                Log::warning("EFI Webhook received for unknown txid: {$txid}");
                continue;
            }

            $merchantContext = new MerchantContext(
                merchantId: (string) $chargeModel->merchant_id,
                merchantName: 'OriginPay Merchant',
                environment: (string) ($chargeModel->environment ?? 'sandbox'),
                permissions: ['*'],
                requestId: 'efi_webhook_' . $txid,
                apiVersion: 'v1'
            );
            $charge = $chargeService->getCharge($chargeModel->charge_id, $merchantContext);

            if (!$charge || $charge->status === ChargeStatus::SUCCEEDED) {
                continue;
            }

            // Trust but verify: Call EFI API to confirm status
            try {
                $gatewayResult = $gatewayManager->getStatus($charge->merchantId, $charge->environment, 'efi', $txid);

                if ($gatewayResult->success && $gatewayResult->status === 'succeeded') {
                    $chargeService->updateChargeStatus($charge, ChargeStatus::SUCCEEDED, 'EFI Webhook confirmed payment');
                } elseif ($gatewayResult->status === 'failed' || $gatewayResult->status === 'cancelled') {
                    $chargeService->updateChargeStatus($charge, ChargeStatus::FAILED, 'EFI Webhook confirmed failure');
                }
            } catch (\Exception $e) {
                Log::error("Failed to verify EFI status for txid {$txid}: " . $e->getMessage());
                
                // Send to DLQ
                WebhookDlq::create([
                    'merchant_id' => $charge->merchantId,
                    'provider' => 'efi',
                    'event_id' => $txid,
                    'payload' => $pix,
                    'error_reason' => $e->getMessage(),
                    'status' => 'new'
                ]);
            }
        }

        return response()->json(['received' => true]);
    }
}
