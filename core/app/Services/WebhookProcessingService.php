<?php

namespace App\Services;

use App\DTOs\Gateway\GatewayWebhookData;
use App\Models\WebhookEvent;
use App\Models\Charge;
use App\Models\WithdrawalRequest;
use App\Models\Chargeback;
use App\Models\Settlement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class WebhookProcessingService
{
    protected $chargeActionService;
    protected $withdrawalActionService;
    protected $chargebackActionService;
    protected $settlementActionService;
    protected $feeActionService;

    public function __construct(
        ChargeActionService $chargeActionService,
        WithdrawalActionService $withdrawalActionService,
        ChargebackActionService $chargebackActionService,
        SettlementActionService $settlementActionService,
        FeeActionService $feeActionService
    ) {
        $this->chargeActionService = $chargeActionService;
        $this->withdrawalActionService = $withdrawalActionService;
        $this->chargebackActionService = $chargebackActionService;
        $this->settlementActionService = $settlementActionService;
        $this->feeActionService = $feeActionService;
    }

    public function process(GatewayWebhookData $data): bool
    {
        $payloadHash = md5(json_encode($data->raw_payload));
        
        // Verifica idempotência pelo banco
        $webhookEvent = WebhookEvent::firstOrCreate(
            [
                'gateway' => $data->gateway,
                'event_id' => $data->event_id,
                'provider_reference' => $data->provider_reference,
                'payload_hash' => $payloadHash,
            ],
            [
                'event_type' => $data->event_type,
                'raw_payload' => $data->raw_payload,
                'status' => 'received',
                'correlation_id' => Str::uuid(),
            ]
        );

        if ($webhookEvent->status === 'processed' || $webhookEvent->status === 'processing') {
            Log::info("Webhook {$webhookEvent->event_id} already processed or processing. Skipping.");
            return true; // Already handled
        }

        $webhookEvent->status = 'processing';
        $webhookEvent->attempts += 1;
        $webhookEvent->save();

        try {
            DB::transaction(function () use ($data) {
                switch ($data->entity_type) {
                    case 'charge':
                        $this->handleCharge($data);
                        break;
                    case 'withdrawal':
                        $this->handleWithdrawal($data);
                        break;
                    case 'chargeback':
                        $this->handleChargeback($data);
                        break;
                    case 'settlement':
                        $this->handleSettlement($data);
                        break;
                    default:
                        throw new \Exception("Entity type {$data->entity_type} not supported.");
                }
            });

            $webhookEvent->status = 'processed';
            $webhookEvent->processed_at = now();
            $webhookEvent->save();
            
            return true;

        } catch (\Exception $e) {
            $webhookEvent->status = 'failed';
            $webhookEvent->failed_at = now();
            $webhookEvent->error_message = $e->getMessage();
            $webhookEvent->save();

            Log::error("Webhook Processing Failed", [
                'event_id' => $webhookEvent->event_id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    protected function handleCharge(GatewayWebhookData $data)
    {
        // Usa lock para evitar race condition se dois webhooks vierem juntos e bypassarem o hash somehow
        $charge = Charge::where('trx_id', $data->provider_reference)->lockForUpdate()->first();
        if (!$charge) {
            throw new \Exception("Charge not found for reference: {$data->provider_reference}");
        }

        if ($data->status === 'paid' && $charge->status === 'pending') {
            // Em vez de adminId, usar 0 ou systemId
            $this->chargeActionService->reprocessWebhook($charge, 0); 
        }
    }

    protected function handleWithdrawal(GatewayWebhookData $data)
    {
        // ... similar logic
    }

    protected function handleChargeback(GatewayWebhookData $data)
    {
        // ... similar logic
    }

    protected function handleSettlement(GatewayWebhookData $data)
    {
        // ... similar logic
    }
}
