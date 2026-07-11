<?php

namespace App\Services\Fraud;

use App\Models\User;
use App\Models\IdentityFingerprint;

class IdentityRiskService
{
    public function checkIdentity(User $user, array $data): array
    {
        $secret = env('FRAUD_FINGERPRINT_SECRET', 'default_secret_if_not_set');
        
        $cpfHash = isset($data['cpf']) ? hash_hmac('sha256', $data['cpf'], $secret) : null;
        $docHash = isset($data['document']) ? hash_hmac('sha256', $data['document'], $secret) : null;
        $selfieHash = isset($data['selfie']) ? hash_hmac('sha256', $data['selfie'], $secret) : null;

        $record = IdentityFingerprint::firstOrCreate(
            ['user_id' => $user->id],
            []
        );

        if ($cpfHash) $record->update(['cpf_hash' => $cpfHash]);
        if ($docHash) $record->update(['document_hash' => $docHash]);
        if ($selfieHash) $record->update(['selfie_hash' => $selfieHash]);

        $risks = [
            'duplicate_cpf' => false,
            'duplicate_document' => false,
            'duplicate_selfie' => false,
        ];

        if ($cpfHash && IdentityFingerprint::where('cpf_hash', $cpfHash)->where('user_id', '!=', $user->id)->exists()) {
            $risks['duplicate_cpf'] = true;
        }

        if ($docHash && IdentityFingerprint::where('document_hash', $docHash)->where('user_id', '!=', $user->id)->exists()) {
            $risks['duplicate_document'] = true;
        }

        if ($selfieHash && IdentityFingerprint::where('selfie_hash', $selfieHash)->where('user_id', '!=', $user->id)->exists()) {
            $risks['duplicate_selfie'] = true;
        }

        return $risks;
    }
}
