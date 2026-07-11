<?php

namespace App\Services\Payment;

use App\Console\Commands\ScanAnomaliesCommand;

class LiquidityGuardService
{
    protected LiquidityCoverageService $coverageService;

    public function __construct(LiquidityCoverageService $coverageService)
    {
        $this->coverageService = $coverageService;
    }

    /**
     * Blocks withdrawal if LCR is critical.
     * Throws exception if blocked.
     */
    public function validateLiquidityOrThrow(float $withdrawalAmount): void
    {
        $lcr = $this->coverageService->calculateLCR();

        // Check if the current liquidity status is CRITICAL or if deducting this amount would drop it dangerously low.
        // For simplicity, we just block if the current status is CRITICAL.
        if ($lcr['status'] === 'CRITICAL') {
            app(ScanAnomaliesCommand::class)->registerAnomaly(
                'liquidity_risk', 
                'CRITICAL', 
                'liquidity', 
                0, 
                "liquidity_risk:withdrawal_blocked:" . time(),
                "Withdrawal of R$ {$withdrawalAmount} blocked due to critical LCR ({$lcr['coverage_percent']}%).",
                ['attempted_amount' => $withdrawalAmount], 
                ['inject_liquidity']
            );

            throw new \Exception("Withdrawal blocked: Insufficient operational liquidity.");
        }
    }
}
