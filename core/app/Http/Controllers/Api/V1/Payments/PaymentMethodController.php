<?php

namespace App\Http\Controllers\Api\V1\Payments;

use App\Factories\ApiResponse;
use Illuminate\Routing\Controller;
use App\Http\Requests\Payments\CreatePaymentMethodRequest;
use App\Services\PaymentMethod\PaymentMethodService;
use App\DTOs\PaymentMethod\CreatePaymentMethodRequestDTO;

class PaymentMethodController extends Controller
{
    private PaymentMethodService $service;

    public function __construct(PaymentMethodService $service)
    {
        $this->service = $service;
    }

    public function store(CreatePaymentMethodRequest $request)
    {
        $cardData = $request->input('card');
        
        $dto = new CreatePaymentMethodRequestDTO(
            $request->input('type'),
            $cardData['number'],
            $cardData['exp_month'],
            $cardData['exp_year'],
            $cardData['cvv'],
            $cardData['holder_name'] ?? ''
        );

        try {
            $responseDTO = $this->service->createPaymentMethod($dto);
            return response()->json($responseDTO->toArray(), 201);
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::validation([]);
        }
    }

    public function show(string $id)
    {
        $responseDTO = $this->service->getPaymentMethod($id);

        if (!$responseDTO) {
            return ApiResponse::notFound('Resource not found.');
        }

        return response()->json($responseDTO->toArray());
    }
}
