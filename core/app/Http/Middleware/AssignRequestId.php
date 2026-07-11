<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AssignRequestId
{
    public function handle(Request $request, Closure $next)
    {
        $requestId = 'req_' . Str::random(24);
        
        $request->attributes->set('request_id', $requestId);
        
        $response = $next($request);
        
        if (method_exists($response, 'header')) {
            $response->header('X-OriginPay-Request-Id', $requestId);
        }
        
        return $response;
    }
}
