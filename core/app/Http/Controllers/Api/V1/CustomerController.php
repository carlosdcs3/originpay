<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ApiErrorCode;
use App\Enums\ApiErrorType;
use App\Factories\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
            'document' => 'nullable|string',
        ]);

        return ApiResponse::error(
            ApiErrorType::INVALID_REQUEST,
            ApiErrorCode::INVALID_PARAMETERS,
            'Endpoint unavailable.',
            501
        );
    }

    public function show($id, Request $request)
    {
        return ApiResponse::notFound('Resource not found.');
    }
}
