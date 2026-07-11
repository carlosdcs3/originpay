<?php

namespace App\Services\Payment;

use App\Services\Payment\Contracts\BalanceProviderInterface;
use App\Models\FinancialReconciliation;
use App\Models\Wallet;
use App\Console\Commands\ScanAnomaliesCommand;

class EfiReconciliationService
{
    protected BalanceProviderInterface $balanceProvider;

    public function __construct(BalanceProviderInterface $balanceProvider)
    {
        $this->balanceProvider = $balanceProvider;
    }

    public function reconcileBalance(): FinancialReconciliation
    {
        $providerName = $this->balanceProvider->getProviderName();
        $actualBalance = $this->balanceProvider->getBalance();

        // Calculate Expected Balance
        // Expected = (Pix In Holding + Withdraw Failed/Refunds Held) - Payout Holding + (Revenue collected related to EFI)
        // A simpler way: The actual balance of the provider account is theoretically the sum of all EFI holding wallets.
        // Efi Pix Holding (money waiting to be settled)
        // Efi Payout Holding (money scheduled to leave but maybe not left yet)
        // Efi Fee Holding (money collected for Efi fees)
        // If the platform withdraws its own Revenue from Efi, that's external.
        // For this system, we will sum the holding wallets.
        
        $holdingUuids = [
            "GATEWAY_{$providerName}_PIX_HOLDING",
            "GATEWAY_{$providerName}_PIX_PAYOUT_HOLDING",
            "GATEWAY_{$providerName}_FEE_HOLDING",
        ];

        $expectedBalance = Wallet::whereIn('uuid', $holdingUuids)->sum('balance');

        $difference = abs($expectedBalance - $actualBalance);
        
        $status = 'GREEN';
        $severity = null;

        if ($difference > 0 && $difference <= 10) {
            $status = 'LOW';
            $severity = 'LOW';
        } elseif ($difference > 10 && $difference <= 100) {
            $status = 'MEDIUM';
            $severity = 'MEDIUM';
        } elseif ($difference > 100 && $difference <= 1000) {
            $status = 'HIGH';
            $severity = 'HIGH';
        } elseif ($difference > 1000) {
            $status = 'CRITICAL';
            $severity = 'CRITICAL';
        }

        $reconciliation = FinancialReconciliation::create([
            'provider' => $providerName,
            'expected_balance' => $expectedBalance,
            'actual_balance' => $actualBalance,
            'difference' => $difference,
            'status' => $status,
            'metadata' => [
                'holding_sum' => $expectedBalance,
            ],
        ]);

        if ($severity) {
            app(ScanAnomaliesCommand::class)->registerAnomaly(
                'efi_balance_mismatch', 
                $severity, 
                'reconciliation', 
                $reconciliation->id, 
                "efi_balance_mismatch:global:" . date('Y-m-d'),
                "Balance mismatch for {$providerName}. Expected: {$expectedBalance}, Actual: {$actualBalance}",
                ['reconciliation_id' => $reconciliation->id], 
                ['investigate_external_account']
            );
        }

        return $reconciliation;
    }

    public function getLatestEfiBalanceSnapshot(): ?FinancialReconciliation
    {
        return FinancialReconciliation::query()
            ->where('provider', $this->balanceProvider->getProviderName())
            ->latest()
            ->first();
    }
}
