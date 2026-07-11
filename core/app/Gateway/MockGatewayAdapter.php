<?php

namespace App\Gateway;

use App\Models\Charge;
use Illuminate\Support\Str;

class MockGatewayAdapter implements GatewayAdapter
{
    protected \App\Models\PaymentGateway $gatewayModel;

    public function __construct(\App\Models\PaymentGateway $gatewayModel)
    {
        $this->gatewayModel = $gatewayModel;
    }

    public function getIdentifier(): string
    {
        return $this->gatewayModel->code;
    }

    public function healthCheck(): GatewayHealth
    {
        return new GatewayHealth(true, 5, $this->gatewayModel->is_sandbox ? 'sandbox' : 'production');
    }

    public function supportsPix(): bool { return true; }
    public function supportsCard(): bool { return true; }
    public function supportsBoleto(): bool { return true; }
    public function supportsSplit(): bool { return false; }
    public function supportsWithdraw(): bool { return true; }
    public function supportsRefund(): bool { return true; }
    public function supportsWebhookValidation(): bool { return false; }

    public function createCharge(Charge $charge): void
    {
        if (($charge->payment_method->value ?? $charge->payment_method) === 'boleto') {
            $this->createBoleto($charge);
            return;
        }

        $charge->gateway_charge_id = 'mock_' . Str::random(12);
        
        if ($charge->payment_method->value === 'pix') {
            $charge->qr_code = 'mock_qr_code_image_data_here';
            $charge->pix_copy_paste = '00020126580014br.gov.bcb.pix0136mock-pix-key' . Str::random(20);
        }
        
        $charge->payment_link = url("/mock-pay/{$charge->gateway_charge_id}");
    }

    public function createBoleto(Charge $charge): void
    {
        $reference = 'mock_boleto_' . Str::random(12);

        $charge->gateway_charge_id = $reference;
        $charge->gateway_reference = $reference;
        $charge->boleto_url = url("/mock-boleto/{$reference}");
        $charge->boleto_pdf_url = url("/mock-boleto/{$reference}.pdf");
        $charge->barcode = '23793381286000012345678901234567890123456789';
        $charge->digitable_line = '23793.38128 60000.123456 78901.234567 8 90123456789';
        $charge->payment_link = $charge->boleto_url;
        $charge->expires_at = $charge->expires_at ?: now()->addDays(3);
    }

    public function refreshBoleto(Charge $charge): void
    {
        $this->createBoleto($charge);
    }

    public function cancelCharge(Charge $charge): void
    {
        // Mock cancellation logic
    }

    public function getCharge(string $gatewayChargeId): array
    {
        return [
            'id' => $gatewayChargeId,
            'status' => 'paid',
        ];
    }

    public function refund(Charge $charge): void
    {
        // Mock refund logic
    }

    public function createWithdrawal(float $amount, string $pixKey): array
    {
        return [
            'id' => 'mock_wd_' . Str::random(12),
            'status' => 'processing',
        ];
    }

    public function handleWebhook(\Illuminate\Http\Request $request): NormalizedEvent
    {
        // Mock webhook just receives gateway_charge_id and status from request
        $chargeId = $request->input('gateway_charge_id', 'unknown');
        $statusStr = $request->input('status', 'paid');
        
        $status = \App\Enums\ChargeStatus::PAID;
        if ($statusStr === 'expired') $status = \App\Enums\ChargeStatus::EXPIRED;
        if ($statusStr === 'cancelled') $status = \App\Enums\ChargeStatus::CANCELLED;
        if ($statusStr === 'refunded') $status = \App\Enums\ChargeStatus::REFUNDED;

        return new NormalizedEvent($chargeId, $status, $request->all(), 'event_' . Str::random(10));
    }
}
