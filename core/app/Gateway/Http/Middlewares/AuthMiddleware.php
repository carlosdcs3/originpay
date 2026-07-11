<?php

namespace App\Gateway\Http\Middlewares;

use App\DTOs\Gateway\GatewayResponse;
use App\Gateway\Security\GatewayAuthenticationService;
use App\Gateway\Config\GatewayCredentials;
use Closure;

class AuthMiddleware implements GatewayMiddlewareInterface
{
    public function __construct(protected GatewayAuthenticationService $authService) {}

    public function handle(array $requestData, Closure $next): GatewayResponse
    {
        /** @var GatewayCredentials $credentials */
        $credentials = $requestData['credentials'] ?? null;
        
        if ($credentials && isset($requestData['gatewaySlug'])) {
            $authData = $this->authService->authenticate($requestData['gatewaySlug'], $credentials);
            
            $requestData['headers'] = array_merge($requestData['headers'] ?? [], $authData['headers'] ?? []);
            $requestData['options'] = array_merge($requestData['options'] ?? [], $authData['options'] ?? []);
        }

        return $next($requestData);
    }
}
