<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Services\GatewayRolloutService;
use Illuminate\Support\Facades\Config;
use Illuminate\Foundation\Testing\RefreshDatabase;

class NewProviderRolloutTest extends TestCase
{
    use RefreshDatabase;

    public function test_kill_switch_blocks_outbound()
    {
        Config::set('services.new_provider.kill_switch', true);
        
        $service = new GatewayRolloutService();
        $this->assertTrue($service->isKillSwitchActive());
        
        // This simulates what checkOutboundLocks() does
        $gateway = new \App\Payment\Modern\Providers\NewProviderGateway();
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Kill Switch: Outbound calls for NEW_PROVIDER are blocked.");
        
        $gateway->createDeposit(new \App\Payment\Modern\DTO\DepositDTO(10, 'USD', '123'));
    }

    public function test_kill_switch_allows_inbound_webhooks()
    {
        Config::set('services.new_provider.kill_switch', true);
        Config::set('services.new_provider.webhook_secret', 'my_sandbox_secret');

        $gateway = new \App\Payment\Modern\Providers\NewProviderGateway();
        
        $payload = json_encode(['id' => '123']);
        $timestamp = time();
        $signedPayload = $timestamp . '.' . $payload;
        $signature = hash_hmac('sha256', $signedPayload, 'my_sandbox_secret');

        $request = \Illuminate\Http\Request::create('/webhook', 'POST', [], [], [], [
            'HTTP_X-NewProvider-Signature' => $signature,
            'HTTP_X-NewProvider-Timestamp' => $timestamp
        ], $payload);

        // Does not throw Kill Switch Exception
        $this->assertTrue($gateway->verifyWebhook($request));
    }

    public function test_feature_flag_is_disabled_globally()
    {
        Config::set('services.new_provider.enabled', false);
        $service = new GatewayRolloutService();
        $user = User::factory()->create();

        $this->assertFalse($service->shouldUseNewProvider($user));
    }

    public function test_feature_flag_by_whitelist()
    {
        Config::set('services.new_provider.enabled', true);
        
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Config::set('services.new_provider.whitelist_users', $user1->id);

        $service = new GatewayRolloutService();

        $this->assertTrue($service->shouldUseNewProvider($user1));
        $this->assertFalse($service->shouldUseNewProvider($user2));
    }

    public function test_feature_flag_by_percentage()
    {
        Config::set('services.new_provider.enabled', true);
        Config::set('services.new_provider.rollout_percentage', 100);

        $user = User::factory()->create();
        $service = new GatewayRolloutService();

        $this->assertTrue($service->shouldUseNewProvider($user));
    }
}
