<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;

class EnsureApiKeyScope
{
    public function handle(Request $request, Closure $next, string ...$requiredScopes)
    {
        $apiKeyId = $request->input('api_key_id');

        if (! $apiKeyId) {
            return $next($request);
        }

        $apiKey = ApiKey::find($apiKeyId);

        if (! $apiKey || ! $this->hasAnyScope($apiKey->permissions ?? [], $requiredScopes)) {
            return response()->json(['message' => 'Forbidden. API key scope is missing.'], 403);
        }

        return $next($request);
    }

    private function hasAnyScope(array $grantedScopes, array $requiredScopes): bool
    {
        if (in_array('*', $grantedScopes, true)) {
            return true;
        }

        foreach ($requiredScopes as $scope) {
            if (in_array($scope, $grantedScopes, true)) {
                return true;
            }
        }

        return false;
    }
}
