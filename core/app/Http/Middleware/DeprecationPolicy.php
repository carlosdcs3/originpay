<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DeprecationPolicy
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Se a rota contiver o header/middleware de deprecation, podemos injetar os headers.
        // Simulando que a API V1 inteira ganhará deprecation no futuro.
        // $response->headers->set('Deprecation', 'true');
        // $response->headers->set('Sunset', 'Wed, 11 Nov 2026 23:59:59 GMT');
        // $response->headers->set('Link', '<https://digisynk.com/docs/changelog>; rel="sunset"');

        return $response;
    }
}
