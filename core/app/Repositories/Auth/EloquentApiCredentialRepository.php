<?php

namespace App\Repositories\Auth;

use App\Contracts\Auth\ApiCredentialRepositoryInterface;
use App\Domain\Auth\ApiCredential as DomainApiCredential;
use App\Models\ApiCredential;

class EloquentApiCredentialRepository implements ApiCredentialRepositoryInterface
{
    public function findByPublicKey(string $publicKey): ?DomainApiCredential
    {
        $credential = ApiCredential::where('public_key', $publicKey)->first();

        if (!$credential) {
            return null;
        }

        return $this->toDomain($credential);
    }

    public function findBySecretKey(string $secretKey): ?DomainApiCredential
    {
        // Secret key format: sk_live_prefix123_randomHashString
        // Extract prefix to search the DB efficiently
        $parts = explode('_', $secretKey);
        
        if (count($parts) < 4) {
            return null; // Invalid format
        }

        $prefix = $parts[0] . '_' . $parts[1] . '_' . $parts[2]; // sk_live_ab12cd34

        // Find all possible keys with this prefix (usually 1, maybe 2 if rotated quickly)
        $credentials = ApiCredential::where('key_prefix', $prefix)
            ->whereIn('status', ['active', 'rotating'])
            ->get();

        foreach ($credentials as $credential) {
            // If it's rotating, check if grace period expired
            if ($credential->grace_period_expires_at && $credential->grace_period_expires_at->isPast()) {
                continue; // It has expired, don't validate
            }

            if (\Illuminate\Support\Facades\Hash::check($secretKey, $credential->secret_key_hash)) {
                return $this->toDomain($credential);
            }
        }

        return null;
    }

    private function toDomain(ApiCredential $credential): DomainApiCredential
    {
        return new DomainApiCredential(
            id: (string) $credential->id,
            publicKey: $credential->public_key,
            secretKey: '', // Do not expose the real secret key hash to the domain unnecessarily
            merchantId: (string) $credential->merchant_id,
            status: $credential->status,
            createdAt: $credential->created_at->toIso8601String()
        );
    }
}
