<?php

namespace App\Http\Controllers\Api\V1\Payments;

use App\Factories\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Payments\ChargeService;
use App\Domain\Auth\MerchantContext;

class ChargeController extends Controller
{
    public function __construct(
        private readonly ChargeService $chargeService
    ) {}

    public function store(Request $request)
    {
        $merchant = $request->attributes->get('merchant_context');
        
        $request->validate([
            'amount' => 'required|integer|min:1',
            'currency' => 'nullable|string|size:3',
            'session_id' => 'nullable|exists:sessions,id',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
            'metadata' => 'nullable|array'
        ]);

        $charge = $this->chargeService->createCharge($request->all(), $merchant);

        return response()->json([
            'message' => 'Charge processed',
            'data' => [
                'id' => $charge->id,
                'charge_number' => $charge->chargeNumber,
                'amount' => $charge->amount,
                'currency' => $charge->currency,
                'status' => $charge->status->value,
                'metadata' => $charge->merchantMetadata,
                'pix_copy_paste' => $charge->internalMetadata['pix_copy_paste'] ?? null,
                'qr_code_url' => $charge->internalMetadata['qr_code_url'] ?? null,
                'failure_code' => $charge->failureCode,
                'failure_message' => $charge->failureMessage,
            ]
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $merchant = $request->attributes->get('merchant_context');

        $charge = $this->chargeService->getCharge($id, $merchant);

        if (!$charge) {
            return ApiResponse::notFound('Resource not found.');
        }

        return response()->json([
            'data' => [
                'id' => $charge->id,
                'charge_number' => $charge->chargeNumber,
                'amount' => $charge->amount,
                'currency' => $charge->currency,
                'status' => $charge->status->value,
                'metadata' => $charge->merchantMetadata,
                'pix_copy_paste' => $charge->internalMetadata['pix_copy_paste'] ?? null,
                'qr_code_url' => $charge->internalMetadata['qr_code_url'] ?? null,
                'failure_code' => $charge->failureCode,
                'failure_message' => $charge->failureMessage,
            ]
        ]);
    }
}
