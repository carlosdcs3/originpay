<?php

namespace App\Http\Controllers\Api\V1\Payments;

use Illuminate\Routing\Controller;
use App\Http\Requests\Payments\CreateSessionRequest;
use App\Services\Payments\SessionService;
use App\DTOs\Payments\CreateSessionRequestDTO;

class SessionController extends Controller
{
    private SessionService $service;

    public function __construct(SessionService $service)
    {
        $this->service = $service;
    }

    public function store(CreateSessionRequest $request)
    {
        $dto = new CreateSessionRequestDTO(
            (float) $request->input('amount'),
            $request->input('currency'),
            $request->input('reference_id'),
            $request->input('customer')
        );

        $responseDTO = $this->service->createSession($dto);

        return response()->json($responseDTO->toArray(), 201);
    }

    public function show(string $id)
    {
        $responseDTO = $this->service->getSession($id);

        if (!$responseDTO) {
            return response()->json([
                'error' => [
                    'type' => 'not_found',
                    'message' => 'Session not found.',
                ]
            ], 404);
        }

        return response()->json($responseDTO->toArray());
    }
}
