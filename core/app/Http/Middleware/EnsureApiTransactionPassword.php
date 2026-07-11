<?php

namespace App\Http\Middleware;

use App\Enums\ApiErrorCode;
use App\Enums\ApiErrorType;
use App\Factories\ApiResponse;
use App\Models\User;
use App\Services\TransactionPasswordService;
use Closure;
use Illuminate\Http\Request;

class EnsureApiTransactionPassword
{
    public function __construct(
        private readonly TransactionPasswordService $transactionPasswordService
    ) {
    }

    public function handle(Request $request, Closure $next)
    {
        $userId = $request->input('api_user_id');
        $user = $userId ? User::find($userId) : null;

        if (! $user || ! $this->transactionPasswordService->verifyRequest($request, $user)) {
            return ApiResponse::error(
                ApiErrorType::AUTHENTICATION_ERROR,
                ApiErrorCode::INVALID_API_KEY,
                'Transaction authorization failed.',
                403
            );
        }

        return $next($request);
    }
}
