<?php

namespace Tests\Feature;

use App\Services\PaymentService;
use App\Services\TransactionService;
use App\Services\WalletService;
use Tests\TestCase;

class ServiceSingletonTest extends TestCase
{
    public function test_currency_service_alias_is_a_singleton(): void
    {
        $this->assertSame(app('currency.service'), app('currency.service'));
    }

    public function test_wallet_service_is_a_singleton(): void
    {
        $this->assertSame(app(WalletService::class), app(WalletService::class));
    }

    public function test_transaction_service_is_a_singleton(): void
    {
        $this->assertSame(app(TransactionService::class), app(TransactionService::class));
    }

    public function test_payment_service_is_a_singleton(): void
    {
        $this->assertSame(app(PaymentService::class), app(PaymentService::class));
    }

    public function test_different_services_are_distinct(): void
    {
        $this->assertNotSame(app(WalletService::class), app(TransactionService::class));
    }
}
