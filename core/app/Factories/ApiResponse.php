<?php

namespace App\Factories;

use Illuminate\Http\JsonResponse;
use App\Enums\ApiErrorCode;
use App\Enums\ApiErrorType;

class ApiResponse
{
    private static function getRequestId(): ?string
    {
        return request()->attributes->get('request_id');
    }

    private static function formatResponse(array $data, int $status = 200, array $headers = []): JsonResponse
    {
        $requestId = self::getRequestId();
        if ($requestId) {
            $data['request_id'] = $requestId;
            $headers['X-OriginPay-Request-Id'] = $requestId;
        }

        return response()->json($data, $status, $headers);
    }

    public static function success(array $data, int $status = 200): JsonResponse
    {
        return self::formatResponse($data, $status);
    }

    public static function created(array $data): JsonResponse
    {
        return self::formatResponse($data, 201);
    }

    public static function accepted(array $data): JsonResponse
    {
        return self::formatResponse($data, 202);
    }

    public static function error(ApiErrorType $type, ApiErrorCode $code, string $message, int $status = 400): JsonResponse
    {
        return self::formatResponse([
            'error' => [
                'type' => $type->value,
                'code' => $code->value,
                'message' => $message
            ]
        ], $status);
    }

    public static function validation(array $errors): JsonResponse
    {
        return self::formatResponse([
            'error' => [
                'type' => ApiErrorType::INVALID_REQUEST->value,
                'code' => ApiErrorCode::INVALID_PARAMETERS->value,
                'message' => 'The request is invalid.',
            ]
        ], 422);
    }

    public static function unauthorized(string $message = 'Unauthorized.'): JsonResponse
    {
        return self::error(
            ApiErrorType::AUTHENTICATION_ERROR,
            ApiErrorCode::INVALID_API_KEY,
            $message,
            401
        );
    }

    public static function missingApiKey(string $message = 'Unauthorized.'): JsonResponse
    {
        return self::error(
            ApiErrorType::AUTHENTICATION_ERROR,
            ApiErrorCode::INVALID_API_KEY,
            $message,
            401
        );
    }

    public static function notFound(string $message = 'Resource not found.'): JsonResponse
    {
        return self::error(
            ApiErrorType::INVALID_REQUEST,
            ApiErrorCode::RESOURCE_NOT_FOUND,
            $message,
            404
        );
    }

    public static function internal(string $message = 'An internal error occurred.'): JsonResponse
    {
        return self::error(
            ApiErrorType::API_ERROR,
            ApiErrorCode::INTERNAL_ERROR,
            $message,
            500
        );
    }
}
