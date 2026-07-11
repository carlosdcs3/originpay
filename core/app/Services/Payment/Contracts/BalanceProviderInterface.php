<?php

namespace App\Services\Payment\Contracts;

interface BalanceProviderInterface
{
    /**
     * Retrieve the real external balance of the account
     */
    public function getBalance(): float;
    
    /**
     * Determine the provider name (e.g. 'EFI')
     */
    public function getProviderName(): string;
}
