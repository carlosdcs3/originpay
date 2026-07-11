<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Factories\ApiResponse;
use Illuminate\Http\JsonResponse;

class MeController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $merchantContext = $request->attributes->get('merchant_context');

        return ApiResponse::success([
            'merchant' => $merchantContext->merchantId,
            'environment' => $merchantContext->environment,
            'permissions' => $merchantContext->permissions,
            'api_version' => $merchantContext->apiVersion,
        ]);
    }
}
