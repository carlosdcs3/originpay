<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\Payment\LiquidityCoverageService;
use App\Services\Payment\Contracts\BalanceProviderInterface;
use App\Models\Wallet;
use App\Models\User;

class LiquidityCoverageTest extends TestCase
{
    use RefreshDatabase;

    public function test_liquidity_green_when_balance_exceeds_withdrawable()
    {
        Wallet::factory()->create(['uuid' => 'USER_1_WALLET', 'balance' => 50000]);

        $mockProvider = $this->createMock(BalanceProviderInterface::class);
        $mockProvider->method('getBalance')->willReturn(60000.00); // 120%

        $service = new LiquidityCoverageService($mockProvider);
        $result = $service->calculateLCR();

        $this->assertEquals('GREEN', $result['status']);
        $this->assertEquals(120, $result['coverage_percent']);
    }

    public function test_liquidity_critical_when_balance_is_below_95_percent()
    {
        Wallet::factory()->create(['uuid' => 'USER_1_WALLET', 'balance' => 100000]);

        $mockProvider = $this->createMock(BalanceProviderInterface::class);
        $mockProvider->method('getBalance')->willReturn(90000.00); // 90%

        $service = new LiquidityCoverageService($mockProvider);
        $result = $service->calculateLCR();

        $this->assertEquals('CRITICAL', $result['status']);
        $this->assertEquals(90, $result['coverage_percent']);
    }
}
