<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminAuditMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Apenas auditar métodos que alteram estado
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            
            // Ignorar rotas de login
            if ($request->is('admin/login') || $request->is('admin/logout')) {
                return $response;
            }

            $admin = auth()->guard('admin')->user();
            if (!$admin) {
                return $response;
            }

            $payload = $request->except([
                'password', 
                'password_confirmation', 
                'token', 
                'authorization',
                'secret', 
                'secret_key',
                'secret_key_hash',
                'api_key', 
                'api_secret', 
                'test_api_key',
                'test_api_secret',
                'merchant_key',
                'test_merchant_key',
                'x-api-key',
                'x-merchant-key',
                'x-signature',
                'document_front', 
                'document_back', 
                'selfie', 
                '_token'
            ]);

            // Em um sistema real, salvaríamos numa tabela `audit_logs` no banco de dados.
            // Para simplicidade/rapidez, logamos em um canal dedicado 'audit'.
            Log::channel('audit')->info('Admin Action', [
                'admin_id' => $admin->id,
                'admin_name' => $admin->name,
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'payload' => $payload,
                'status_code' => $response->getStatusCode()
            ]);
        }

        return $response;
    }
}
