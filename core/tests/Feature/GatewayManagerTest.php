<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Merchant;
use App\Models\MerchantGateway;
use App\Services\Gateways\GatewayManager;
use App\Domain\Payments\GatewayAuthorizeRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GatewayManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_gateway_manager_falls_back_when_primary_gateway_cannot_execute()
    {
        $merchant = Merchant::factory()->create();

        // EFI is priority 1, but without runtime credentials it cannot execute.
        // The manager should fall back to the next configured gateway.
        MerchantGateway::create([
            'merchant_id' => $merchant->id,
            'gateway_name' => 'efi',
            'environment' => 'sandbox',
            'priority' => 1,
            'enabled' => true
        ]);

        MerchantGateway::create([
            'merchant_id' => $merchant->id,
            'gateway_name' => 'sicoob',
            'environment' => 'sandbox',
            'priority' => 2,
            'enabled' => true
        ]);

        $manager = app(GatewayManager::class);

        $request = new GatewayAuthorizeRequest(
            chargeId: 'ch_123',
            merchantId: $merchant->id,
            amount: 5000,
            currency: 'BRL',
            paymentMethodId: null,
            merchantMetadata: [],
            environment: 'sandbox'
        );

        $result = $manager->authorize($request);

        // Sicoob is selected after EFI fails to build a runnable runtime config.
        $this->assertTrue($result->success);
        $this->assertEquals('sicoob', $result->gatewayName);
    }

    public function test_gateway_manager_fallback_on_unconfigured_sandbox()
    {
        $merchant = Merchant::factory()->create();
        $manager = app(GatewayManager::class);

        // No gateways configured for this merchant
        $request = new GatewayAuthorizeRequest(
            chargeId: 'ch_456',
            merchantId: $merchant->id,
            amount: 1500,
            currency: 'BRL',
            paymentMethodId: null,
            merchantMetadata: [],
            environment: 'sandbox' // Sandbox allows default fallback
        );

        $result = $manager->authorize($request);

        // Should fallback to 'mock' safely
        $this->assertTrue($result->success);
        $this->assertEquals('mock', $result->gatewayName);
    }

    public function test_gateway_manager_fails_on_unconfigured_production()
    {
        $merchant = Merchant::factory()->create();
        $manager = app(GatewayManager::class);

        $request = new GatewayAuthorizeRequest(
            chargeId: 'ch_789',
            merchantId: $merchant->id,
            amount: 1500,
            currency: 'BRL',
            paymentMethodId: null,
            merchantMetadata: [],
            environment: 'production' // Production requires explicit gateway
        );

        $result = $manager->authorize($request);

        $this->assertFalse($result->success);
        $this->assertEquals('gateway_not_configured', $result->failureCode);
        $this->assertTrue($result->isTechnicalFailure);
    }
}
