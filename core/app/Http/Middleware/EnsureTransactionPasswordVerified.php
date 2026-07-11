<?php

namespace App\Http\Middleware;

use App\Services\TransactionPasswordService;
use Closure;
use Illuminate\Http\Request;

class EnsureTransactionPasswordVerified
{
    public function __construct(
        private readonly TransactionPasswordService $transactionPasswordService
    ) {
    }

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user || ! $this->transactionPasswordService->verifyRequest($request, $user)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Autorização transacional inválida.',
                ], 403);
            }

            notifyEvs('error', 'Senha transacional incorreta. Verifique e tente novamente.');

            return back();
        }

        return $next($request);
    }
}
