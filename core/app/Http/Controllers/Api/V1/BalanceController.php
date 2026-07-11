<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Wallet;

class BalanceController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->input('api_user_id');
        $environment = $request->input('api_environment');

        $wallet = Wallet::where('user_id', $userId)->first();

        return response()->json([
            'available' => $wallet ? (float) $wallet->balance : 0,
            'pending' => 0,
            'currency' => 'BRL',
            'environment' => $environment === 'sandbox' ? 'sandbox' : 'live'
        ]);
    }
}
