<?php

namespace App\Services\Compliance;

use App\Models\KycProfile;
use App\Models\KycDocument;
use App\Models\User;
use App\Models\FraudEvent;
use Illuminate\Support\Facades\Log;

class KycDecisionService
{
    /**
     * Evaluates KYC submission and scores the identity.
     * MOCK_PROVIDER / MOCK_KYC_VALIDATION - until OCR/Bureaus are integrated.
     */
    public function evaluate(KycProfile $kycProfile, array $mockedBureauData = [])
    {
        $user = $kycProfile->user;
        $score = 0;
        $details = [];

        // MOCK_KYC_VALIDATION START
        // In real-life, these come from Serasa / ClearSale or OCR tools.
        
        // 1. Documento Válido
        $docValid = $mockedBureauData['document_valid'] ?? true; 
        if ($docValid) {
            $score += 20;
            $details[] = 'Valid document';
        }

        // 2. CPF Válido na Receita Federal
        $cpfValid = $mockedBureauData['cpf_valid'] ?? true;
        if ($cpfValid) {
            $score += 20;
            $details[] = 'Valid CPF status';
        }

        // 3. Nome Consistente (Bureau vs Input)
        $nameMatch = $mockedBureauData['name_match'] ?? true;
        if ($nameMatch) {
            $score += 20;
            $details[] = 'Name consistent with bureau';
        }

        // 4. Selfie Presente e legível
        $selfieValid = $mockedBureauData['selfie_valid'] ?? true;
        if ($selfieValid) {
            $score += 20;
            $details[] = 'Valid selfie check';
        }

        // 5. Sem sinais de fraude em Bureau Externo
        $bureauFraud = $mockedBureauData['bureau_fraud'] ?? false;
        if (!$bureauFraud) {
            $score += 20;
            $details[] = 'No external fraud signs';
        }
        // MOCK_KYC_VALIDATION END

        // Log decision logic securely
        Log::channel('compliance')->info("KYC Evaluation for User {$user->id}", ['score' => $score, 'details' => $details]);

        if ($score >= 80) {
            $kycProfile->status = 'APPROVED';
            $kycProfile->level = 2; // Full KYC
            $kycProfile->approved_at = now();
        } elseif ($score >= 50) {
            $kycProfile->status = 'MANUAL_REVIEW';
        } else {
            $kycProfile->status = 'REJECTED';
            $kycProfile->rejection_reason = "Score: {$score}. Failed automated identity checks.";
        }

        $kycProfile->save();

        return $kycProfile;
    }
}
