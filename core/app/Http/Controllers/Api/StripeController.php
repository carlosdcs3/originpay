<?php

namespace App\Http\Controllers\Api;

use App\Factories\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\PaymentGateway;
use App\Models\VirtualCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Stripe\StripeClient;

class StripeController extends Controller
{
    public function createEphemeralKey(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'card_id' => 'required|string',
                'nonce'   => 'required|string',
            ]);

            if ($validator->fails()) {
                return ApiResponse::validation([]);
            }

            $user = $request->user();

            $card = VirtualCard::where('meta->stripe_card_id', $request->card_id)
                ->where('user_id', $user->id)
                ->firstOrFail();

            $credentials = PaymentGateway::getCredentials('stripe');
            $stripe      = new StripeClient($credentials['stripe_secret']);

            // Create Ephemeral Key with nonce (PCI-compliant Issuing Elements flow)
            $ephemeralKey = $stripe->ephemeralKeys->create([
                'nonce'        => $request->nonce,
                'issuing_card' => $request->card_id,
            ], [
                'stripe_version' => '2023-10-16',
            ]);

            return response()->json([
                'ephemeralKeySecret' => $ephemeralKey->secret,
                'stripeCardId'       => $request->card_id,
                'publishableKey'     => $credentials['stripe_key'],
            ]);
        } catch (\Exception $e) {
            report($e);

            return ApiResponse::internal();
        }
    }
}
