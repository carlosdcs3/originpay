<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ApiErrorCode;
use App\Enums\ApiErrorType;
use App\Factories\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PayoutController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'pix_key' => 'required|string',
            'pix_key_type' => 'required|in:cpf,cnpj,email,phone,random',
        ]);

        return ApiResponse::error(
            ApiErrorType::INVALID_REQUEST,
            ApiErrorCode::INVALID_PARAMETERS,
            'Endpoint unavailable.',
            501
        );
    }

    public function index(Request $request)
    {
        return response()->json(['data' => []]);
    }
}
