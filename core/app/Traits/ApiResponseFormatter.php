<?php

namespace App\Traits;

trait ApiResponseFormatter
{
    public function apiSuccess($data, $statusCode = 200, $headers = [])
    {
        $requestId = request()->attributes->get('request_id');
        $mergedHeaders = array_merge(['X-OriginPay-Request-Id' => $requestId], $headers);

        return response()->json($data, $statusCode)->withHeaders($mergedHeaders);
    }

    public function apiError($code, $message, $statusCode = 400, $headers = [])
    {
        $requestId = request()->attributes->get('request_id');
        $mergedHeaders = array_merge(['X-OriginPay-Request-Id' => $requestId], $headers);

        return response()->json([
            'error' => [
                'code' => $code,
                'message' => $message,
            ]
        ], $statusCode)->withHeaders($mergedHeaders);
    }
}
