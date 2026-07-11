<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ApiErrorCode;
use App\Enums\ApiErrorType;
use App\Factories\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RefundController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'payment_id' => 'required|string',
            'amount' => 'nullable|numeric|min:1',
        ]);

        return ApiResponse::error(
            ApiErrorType::INVALID_REQUEST,
            ApiErrorCode::INVALID_PARAMETERS,
            'Endpoint unavailable.',
            501
        );
    }
}
