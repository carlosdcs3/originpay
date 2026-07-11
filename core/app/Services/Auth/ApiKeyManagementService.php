<?php

namespace App\Services\Auth;

use App\Models\ApiCredential;
use App\Models\ApiCredentialEvent;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class ApiKeyManagementService
{
    public function generateKeys(int $merchantId, string $environment = 'sandbox', array $permissions = ['*']): array
    {
        $prefixStr = $environment === 'sandbox' ? 'test_' : 'live_';
        $randomPrefix = Str::random(8); // e.g., ab12cd34
        $keyPrefix = 'sk_' . $prefixStr . $randomPrefix;
        
        $publicKey = 'pk_' . $prefixStr . Str::random(24);
        $secretKeyPlain = $keyPrefix . '_' . Str::random(32);
        
        $secretKeyHash = Hash::make($secretKeyPlain);

        $credential = DB::transaction(function () use ($merchantId, $publicKey, $secretKeyHash, $keyPrefix, $environment, $permissions) {
            $cred = ApiCredential::create([
                'merchant_id' => $merchantId,
                'public_key' => $publicKey,
                'secret_key_hash' => $secretKeyHash,
                'key_prefix' => $keyPrefix,
                'environment' => $environment,
                'status' => 'active',
                'permissions' => $permissions,
                'api_version' => 'v1',
            ]);

            $this->logEvent($cred->id, 'created');
            return $cred;
        });

        return [
            'id' => $credential->id,
            'public_key' => $publicKey,
            'secret_key' => $secretKeyPlain, // Show only once
            'environment' => $environment,
        ];
    }

    public function rotateKey(int $credentialId, int $gracePeriodMinutes = 60): ?array
    {
        $oldCredential = ApiCredential::find($credentialId);
        if (!$oldCredential || $oldCredential->status !== 'active') {
            return null;
        }

        return DB::transaction(function () use ($oldCredential, $gracePeriodMinutes) {
            // 1. Generate new key with same env and permissions
            $newKeys = $this->generateKeys(
                $oldCredential->merchant_id, 
                $oldCredential->environment,
                $oldCredential->permissions ?? ['*']
            );

            // 2. Set grace period on old key
            $oldCredential->update([
                'status' => 'rotating',
                'grace_period_expires_at' => now()->addMinutes($gracePeriodMinutes),
            ]);

            $this->logEvent($oldCredential->id, 'rotated');

            return $newKeys;
        });
    }

    public function revokeKey(int $credentialId): bool
    {
        $credential = ApiCredential::find($credentialId);
        
        if (!$credential) {
            return false;
        }

        DB::transaction(function () use ($credential) {
            $credential->update([
                'status' => 'revoked',
                'revoked_at' => now(),
            ]);
            
            $this->logEvent($credential->id, 'revoked');
            
            $credential->delete(); // Soft delete
        });
        
        return true;
    }

    private function logEvent(int $credentialId, string $action): void
    {
        ApiCredentialEvent::create([
            'api_credential_id' => $credentialId,
            'action' => $action,
            'performed_by' => auth()->id() ?? 'system',
            'ip_address' => request()->ip(),
        ]);
    }
}
