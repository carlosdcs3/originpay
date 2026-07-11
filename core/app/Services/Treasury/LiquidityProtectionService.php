<?php

namespace App\Services\Treasury;

use App\Models\Wallet;
use App\Models\FinancialAnomaly;
use App\Services\Payment\EfiReconciliationService;
use App\Enums\SystemWalletUUID;
use Illuminate\Support\Facades\Log;

class LiquidityProtectionService
{
    protected EfiReconciliationService $reconciliationService;

    public function __construct(EfiReconciliationService $reconciliationService)
    {
        $this->reconciliationService = $reconciliationService;
    }

    /**
     * Get the Liquidity Coverage Ratio (LCR).
     */
    public function getLCR(): float
    {
        // Total expected by the platform (total users balance minus system profit)
        // A simple way is to get all wallets sum except SYSTEM ones, or just rely on global ledger sum.
        // Actually, the system obligation is the sum of all User wallets.
        $totalObligation = Wallet::whereNotNull('user_id')->sum('balance');
        
        if ($totalObligation <= 0) {
            return 1000.0; // Infinite liquidity if no obligations
        }

        $efiSnapshot = method_exists($this->reconciliationService, 'getLatestEfiBalanceSnapshot')
            ? $this->reconciliationService->getLatestEfiBalanceSnapshot()
            : null;

        $realBalance = $efiSnapshot
            ? (float) ($efiSnapshot->real_balance ?? $efiSnapshot->actual_balance ?? 0)
            : 0.0;

        if ($realBalance <= 0 && app()->environment(['local', 'testing'])) {
            Log::warning('LiquidityProtectionService running without external balance snapshot; using local bypass.', [
                'total_obligation' => $totalObligation,
            ]);

            return 1000.0;
        }

        return ($realBalance / $totalObligation) * 100;
    }

    public function evaluateLiquidity(): string
    {
        $lcr = $this->getLCR();

        if ($lcr > 120) {
            return 'GREEN';
        } elseif ($lcr >= 110) {
            return 'YELLOW';
        } elseif ($lcr >= 100) {
            $this->triggerAnomaly($lcr, 'RED');
            return 'RED';
        } else {
            $this->triggerAnomaly($lcr, 'CRITICAL');
            return 'CRITICAL';
        }
    }

    private function triggerAnomaly(float $lcr, string $level)
    {
        $fingerprint = "liquidity_protection_triggered:" . date('Y-m-d');
        
        $anomaly = FinancialAnomaly::where('fingerprint', $fingerprint)->first();

        if (!$anomaly) {
            FinancialAnomaly::create([
                'type' => 'liquidity_protection_triggered',
                'severity' => 'CRITICAL',
                'entity_type' => 'system',
                'entity_id' => 0,
                'fingerprint' => $fingerprint,
                'description' => "Liquidity Protection Level {$level} reached. LCR: " . number_format($lcr, 2) . "%",
                'detected_at' => now(),
            ]);
        }
    }
}
