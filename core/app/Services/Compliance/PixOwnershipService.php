<?php

namespace App\Services\Compliance;

use App\Models\User;
use App\Models\PixKey;
use App\Models\FraudProfile;
use App\Models\AccountRestriction;
use App\Models\FinancialAnomaly;
use Illuminate\Support\Facades\Log;

class PixOwnershipService
{
    /**
     * Validates PIX key ownership.
     */
    public function validateOwnership(User $user, string $pixKey, string $keyType, ?string $cpfSource = null, bool $isTrustedSource = false)
    {
        $pixRecord = PixKey::firstOrCreate(
            ['user_id' => $user->id, 'pix_key' => $pixKey],
            ['key_type' => $keyType, 'status' => 'ACTIVE', 'risk_level' => 'LOW']
        );

        $pixRecord->last_used_at = now();

        if (!$isTrustedSource || !$cpfSource) {
            $pixRecord->status = 'PENDING_OWNERSHIP_VERIFICATION';
            $pixRecord->save();
            return 'PENDING_OWNERSHIP_VERIFICATION';
        }

        // We have a trusted CPF from the key (e.g. Gateway webhook)
        // Compare with KycProfile CPF
        $userCpfDoc = \App\Models\KycDocument::where('user_id', $user->id)->where('document_type', 'cpf')->first();
        $userCpf = $userCpfDoc ? $userCpfDoc->storage_path : null;

        $userRealCpf = $userCpf ?? $user->cpf ?? $user->document ?? '00000000000';

        if ($cpfSource !== $userRealCpf && $userRealCpf !== '00000000000') {
            $fraudProfile = FraudProfile::where('user_id', $user->id)->first();
            $isHighRisk = $fraudProfile && in_array($fraudProfile->risk_level, ['HIGH', 'CRITICAL']);

            $pixRecord->status = 'BLOCKED';
            $pixRecord->risk_level = 'HIGH';
            $pixRecord->save();

            FinancialAnomaly::create([
                'type' => 'pix_owner_mismatch',
                'severity' => 'HIGH',
                'entity_type' => 'pix_key',
                'entity_id' => $pixRecord->id,
                'fingerprint' => "pix_owner_mismatch_{$pixRecord->id}_" . time(),
                'description' => "PIX Key CPF ({$cpfSource}) does not match User CPF ({$userRealCpf})",
                'detected_at' => now(),
            ]);

            if ($isHighRisk) {
                AccountRestriction::updateOrCreate(
                    ['user_id' => $user->id, 'restriction_type' => 'WITHDRAW_BLOCK'],
                    ['reason' => 'PIX Ownership Mismatch on High Risk Profile', 'expires_at' => null]
                );
                return 'WITHDRAW_BLOCK';
            }

            return 'MANUAL_REVIEW';
        }

        // Match successful
        $pixRecord->verified = true;
        $pixRecord->verified_at = now();
        $pixRecord->status = 'ACTIVE';
        $pixRecord->save();

        return 'APPROVED';
    }
}
