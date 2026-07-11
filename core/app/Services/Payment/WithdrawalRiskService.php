<?php

namespace App\Services\Payment;

use App\Models\User;
use App\Models\WithdrawalRequest;
use App\Services\Compliance\VelocityRiskService;
use App\Services\Compliance\BehavioralRiskService;
use App\Services\Compliance\SharedPixKeyRiskService;
use App\Models\BlacklistedPixKey;
use App\Services\Treasury\LiquidityProtectionService;

class WithdrawalRiskService
{
    protected VelocityRiskService $velocityRisk;
    protected BehavioralRiskService $behavioralRisk;
    protected SharedPixKeyRiskService $sharedPixKeyRisk;
    protected LiquidityProtectionService $liquidityProtection;

    public function __construct(
        VelocityRiskService $velocityRisk, 
        BehavioralRiskService $behavioralRisk, 
        SharedPixKeyRiskService $sharedPixKeyRisk,
        LiquidityProtectionService $liquidityProtection
    ) {
        $this->velocityRisk = $velocityRisk;
        $this->behavioralRisk = $behavioralRisk;
        $this->sharedPixKeyRisk = $sharedPixKeyRisk;
        $this->liquidityProtection = $liquidityProtection;
    }

    public function evaluateRisk(User $user, float $amount, string $pixKey, string $pixKeyType = 'random'): string
    {
        // 1. Check Blacklist first
        $isBlacklisted = BlacklistedPixKey::where('pix_key', $pixKey)->exists();
        if ($isBlacklisted) {
            \App\Models\FinancialAnomaly::create([
                'type' => 'blacklisted_pix_usage',
                'severity' => 'CRITICAL',
                'entity_type' => 'user',
                'entity_id' => $user->id,
                'fingerprint' => "blacklist_{$user->id}_{$pixKey}",
                'description' => "User {$user->id} attempted to withdraw to blacklisted PIX key {$pixKey}.",
                'detected_at' => now(),
            ]);
            return 'MANUAL_REVIEW';
        }

        // 2. Shared PIX Key Check
        $sharedKeyRisk = $this->sharedPixKeyRisk->detectSharedKey($user, $pixKey);
        if ($sharedKeyRisk === 'HIGH') {
            return 'MANUAL_REVIEW';
        }

        // 3. Velocity Check
        $velocityRisk = $this->velocityRisk->evaluate($user, $amount);
        if ($velocityRisk === 'CRITICAL' || $velocityRisk === 'HIGH') {
            return 'MANUAL_REVIEW';
        }

        // PIX Ownership Check
        $pixOwnershipService = app(\App\Services\Compliance\PixOwnershipService::class);
        $cpfSource = $user->cpf ?? 'dummy_gateway_cpf'; // Assuming we receive gateway cpf in real implementation
        $pixDecision = $pixOwnershipService->validateOwnership($user, $pixKey, $pixKeyType, $cpfSource, true);

        if ($pixDecision === 'WITHDRAW_BLOCK') {
            return 'REJECTED'; // Will be blocked anyway, but returning REJECTED stops the flow
        } elseif ($pixDecision === 'MANUAL_REVIEW' || $pixDecision === 'PENDING_OWNERSHIP_VERIFICATION') {
            return 'MANUAL_REVIEW';
        }

        // 4. Behavioral Check
        $behavioralRisk = $this->behavioralRisk->evaluate($user, $amount);
        if ($behavioralRisk === 'CRITICAL' || $behavioralRisk === 'HIGH') {
            return 'MANUAL_REVIEW';
        }

        // Fraud Score Check
        $fraudProfile = \App\Models\FraudProfile::where('user_id', $user->id)->first();
        if ($fraudProfile && in_array($fraudProfile->risk_level, ['MEDIUM', 'HIGH', 'CRITICAL'])) {
            return 'MANUAL_REVIEW'; // If High/Critical, it shouldn't even reach here (blocked at AccountRestriction level), but as a safety measure.
        }

        // Amount Governance (above 2000 requires manual review/governance)
        if ($amount > 2000) {
            return 'MANUAL_REVIEW';
        }

        // 5. Liquidity Protection Check
        $liquidityStatus = $this->liquidityProtection->evaluateLiquidity();
        if ($liquidityStatus === 'RED' || $liquidityStatus === 'CRITICAL') {
            return 'MANUAL_REVIEW';
        }

        return 'APPROVE';
    }
}
