<?php

namespace App\Http\Middleware;

use App\Exceptions\NotifyErrorException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserFeature
{
    /**
     * @throws NotifyErrorException
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        if (auth()->user() && ! auth()->user()->hasFeature($feature)) {
            $user = auth()->user();
            if (!$user->isKycVerified()) {
                throw new NotifyErrorException(__('Acesso bloqueado: Sua verificação de identidade (KYC) está pendente. Conclua a verificação para usar este recurso.'), 403);
            } else {
                throw new NotifyErrorException(__('Este recurso está indisponível para sua conta no momento. Por favor, entre em contato com o suporte.'), 403);
            }
        }

        return $next($request);
    }
}
