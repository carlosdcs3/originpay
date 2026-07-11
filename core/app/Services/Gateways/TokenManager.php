<?php

namespace App\Services\Gateways;

use Illuminate\Support\Facades\Cache;
use Exception;

class TokenManager
{
    public function getToken(string $key, int $ttlSeconds, callable $fetcher): string
    {
        $token = Cache::get($key);

        if ($token) {
            return $token;
        }

        // Prevent cache stampede / race condition
        $lock = Cache::lock("lock_{$key}", 10);

        try {
            if ($lock->block(5)) {
                // Double check if another process fetched it while we waited
                $token = Cache::get($key);
                if ($token) {
                    return $token;
                }

                $token = $fetcher();
                if (!$token) {
                    throw new Exception("Token fetcher returned empty token.");
                }

                Cache::put($key, $token, $ttlSeconds);
                return $token;
            } else {
                throw new Exception("Could not acquire lock to refresh token for {$key}.");
            }
        } finally {
            $lock?->release();
        }
    }

    public function invalidate(string $key): void
    {
        Cache::forget($key);
    }
}
