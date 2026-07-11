<?php

namespace App\Gateway;

use App\Models\Charge;

interface GatewayAdapter extends GatewayCapabilities
{
    /**
     * Get the identifier of the adapter.
     */
    public function getIdentifier(): string;

    /**
     * Perform a health check on the gateway.
     */
    public function healthCheck(): GatewayHealth;

    /**
     * Create a charge in the gateway.
     * Must populate the gateway_charge_id, payment_link, qr_code, pix_copy_paste.
     */
    public function createCharge(Charge $charge): void;

    /**
     * Create a boleto charge in the gateway.
     */
    public function createBoleto(Charge $charge): void;

    /**
     * Refresh boleto payable artifacts without creating a new Charge.
     */
    public function refreshBoleto(Charge $charge): void;

    /**
     * Cancel a charge in the gateway.
     */
    public function cancelCharge(Charge $charge): void;

    /**
     * Get the current status of a charge from the gateway.
     */
    public function getCharge(string $gatewayChargeId): array;

    /**
     * Refund a charge in the gateway.
     */
    public function refund(Charge $charge): void;

    /**
     * Create a withdrawal request in the gateway.
     */
    public function createWithdrawal(float $amount, string $pixKey): array;
    /**
     * Parse and validate an incoming webhook request, normalizing it into a standard event.
     */
    public function handleWebhook(\Illuminate\Http\Request $request): NormalizedEvent;
}
