<?php

namespace App\Gateway\Security\Drivers;

use Illuminate\Support\Facades\Cache;

class OAuthDriver implements AuthenticationDriverInterface
{
    public function authenticate(array $config): array
    {
        // Aqui buscaríamos o token com cache, ou bateríamos no EndpointCollection (oauth)
        // Por ora, é apenas um driver estrutural pronto pra ser extendido pelo provedor.
        $token = Cache::remember("oauth_token_".$config['client_id'], 3600, function() {
            return "dummy-oauth-token";
        });
        
        return [
            'headers' => [
                'Authorization' => 'Bearer ' . $token
            ]
        ];
    }
}
