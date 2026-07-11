<?php

namespace App\Services\Payment;

use App\Models\Wallet;
use App\Models\User;
use App\Console\Commands\ScanAnomaliesCommand;
use App\Services\Payment\Contracts\BalanceProviderInterface;
use App\Services\Security\TenantBypass;

class LiquidityCoverageService
{
    protected BalanceProviderInterface $balanceProvider;

    public function __construct(BalanceProviderInterface $balanceProvider)
    {
        $this->balanceProvider = $balanceProvider;
    }

    public function calculateLCR(): array
    {
        $actualBalance = $this->balanceProvider->getBalance();

        // Saldo Sacável: Soma do saldo de todas as wallets dos usuários (excluindo sistema)
        // Isso assume que o tipo user_type distingue admin de cliente, ou uuid não sendo SYSTEM/GATEWAY.
        $withdrawableBalance = TenantBypass::run(fn () => Wallet::whereNotIn('uuid', function($query) {
            $query->select('uuid')->from('wallets')->where('uuid', 'like', 'SYSTEM_%')->orWhere('uuid', 'like', 'GATEWAY_%');
        })->sum('balance'));

        if ($withdrawableBalance <= 0) {
            $ratio = 999; // Infinito seguro
            $percent = 99900;
        } else {
            $ratio = $actualBalance / $withdrawableBalance;
            $percent = $ratio * 100;
        }

        $status = 'GREEN';
        if ($percent >= 110) {
            $status = 'GREEN';
        } elseif ($percent >= 100 && $percent < 110) {
            $status = 'YELLOW';
        } elseif ($percent >= 95 && $percent < 100) {
            $status = 'RED';
        } elseif ($percent < 95) {
            $status = 'CRITICAL';
            
            app(ScanAnomaliesCommand::class)->registerAnomaly(
                'liquidity_ratio_low', 
                'CRITICAL', 
                'liquidity', 
                0, 
                "liquidity_ratio_low:global:" . date('Y-m-d-H'),
                "Liquidity Coverage Ratio is critical at " . round($percent, 2) . "%. Actual Gateway Balance: {$actualBalance}, Withdrawable: {$withdrawableBalance}.",
                ['ratio' => $percent], 
                ['inject_liquidity', 'pause_withdraws']
            );
        }

        return [
            'actual_balance' => $actualBalance,
            'withdrawable_balance' => $withdrawableBalance,
            'coverage_percent' => $percent,
            'status' => $status
        ];
    }
}
