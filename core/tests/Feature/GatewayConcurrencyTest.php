<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\ChargeService;
use App\Models\Charge;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Mockery;

class GatewayConcurrencyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_concurrency_limiter_rejects_after_limit()
    {
        $concurrencyLimit = 2; // For test
        
        $redisLimiter = new GatewayConcurrencyLimiterFake($concurrencyLimit);
        
        $locked1 = false;
        $redisLimiter->then(function() use (&$locked1) { $locked1 = true; }, function() {});

        $locked2 = false;
        $redisLimiter->then(function() use (&$locked2) { $locked2 = true; }, function() {});

        $locked3 = false;
        $redisLimiter->then(function() use (&$locked3) { $locked3 = true; }, function() {});

        $this->assertTrue($locked1);
        $this->assertTrue($locked2);
        $this->assertFalse($locked3);
    }
}

class GatewayConcurrencyLimiterFake
{
    private int $acquired = 0;

    public function __construct(private readonly int $limit)
    {
    }

    public function then(callable $acquired, callable $rejected): void
    {
        if ($this->acquired >= $this->limit) {
            $rejected();
            return;
        }

        $this->acquired++;
        $acquired();
    }
}
