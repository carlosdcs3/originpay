<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function test(Request $request)
    {
        $environment = $request->input('api_environment');
        
        return response()->json([
            'message' => 'Webhook test event triggered successfully.',
            'environment' => $environment
        ], 200);
    }
}
