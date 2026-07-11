<?php

namespace App\Repositories\Idempotency;

use App\Models\IdempotencyKey;
use Illuminate\Support\Facades\DB;

class EloquentIdempotencyRepository
{
    public function findOrLock(string $merchantId, string $idempotencyKey, string $method, string $path, string $hash): ?IdempotencyKey
    {
        return DB::transaction(function () use ($merchantId, $idempotencyKey, $method, $path, $hash) {
            $key = IdempotencyKey::where('merchant_id', $merchantId)
                ->where('idempotency_key', $idempotencyKey)
                ->lockForUpdate()
                ->first();

            if ($key) {
                return $key;
            }

            // Create new locked record
            return IdempotencyKey::create([
                'merchant_id' => $merchantId,
                'idempotency_key' => $idempotencyKey,
                'request_method' => $method,
                'request_path' => $path,
                'request_hash' => $hash,
                'locked_until' => now()->addMinutes(5),
                'expires_at' => now()->addDays(1) // Keep for 24h
            ]);
        });
    }

    public function updateResponse(IdempotencyKey $key, int $status, ?array $body): void
    {
        $key->update([
            'response_status' => $status,
            'response_body' => $body,
            'locked_until' => null // Unlock
        ]);
    }
}
