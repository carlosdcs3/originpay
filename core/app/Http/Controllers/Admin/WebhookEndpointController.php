<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WebhookEndpoint;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Crypt;
use App\Services\Webhooks\WebhookEventService;

class WebhookEndpointController extends Controller
{
    public function __construct(
        private readonly WebhookEventService $eventService
    ) {}

    public function index()
    {
        $endpoints = WebhookEndpoint::with('merchant')->paginate(20);
        return response()->json($endpoints);
    }

    public function store(Request $request)
    {
        $request->validate([
            'merchant_id' => 'required|exists:merchants,id',
            'url' => 'required|url',
            'environment' => 'required|in:sandbox,production',
            'events' => 'required|array',
            'description' => 'nullable|string'
        ]);

        $secretPlain = 'whsec_' . Str::random(32);
        
        $endpoint = WebhookEndpoint::create([
            'merchant_id' => $request->merchant_id,
            'url' => $request->url,
            'secret_encrypted' => Crypt::encryptString($secretPlain),
            'secret_preview' => substr($secretPlain, 0, 10),
            'environment' => $request->environment,
            'events' => $request->events,
            'description' => $request->description,
            'status' => 'active'
        ]);

        return response()->json([
            'message' => 'Webhook Endpoint created successfully.',
            'secret' => $secretPlain, // Show only once
            'data' => $endpoint
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'url' => 'sometimes|required|url',
            'events' => 'sometimes|required|array',
            'status' => 'sometimes|required|in:active,disabled',
            'description' => 'nullable|string'
        ]);

        $endpoint = WebhookEndpoint::findOrFail($id);
        $endpoint->update($request->only(['url', 'events', 'status', 'description']));

        return response()->json(['message' => 'Endpoint updated', 'data' => $endpoint]);
    }

    public function rotateSecret($id)
    {
        $endpoint = WebhookEndpoint::findOrFail($id);
        
        $secretPlain = 'whsec_' . Str::random(32);
        
        $endpoint->update([
            'secret_encrypted' => Crypt::encryptString($secretPlain),
            'secret_preview' => substr($secretPlain, 0, 10),
        ]);

        return response()->json([
            'message' => 'Webhook Secret rotated successfully.',
            'secret' => $secretPlain, // Show only once
            'data' => $endpoint
        ]);
    }

    public function testWebhook($id)
    {
        $endpoint = WebhookEndpoint::findOrFail($id);

        $this->eventService->dispatchEvent(
            merchantId: $endpoint->merchant_id,
            eventType: 'ping',
            payload: ['message' => 'Test webhook from OriginPay'],
            environment: $endpoint->environment
        );

        return response()->json(['message' => 'Test webhook event dispatched (ping)']);
    }

    public function destroy($id)
    {
        $endpoint = WebhookEndpoint::findOrFail($id);
        $endpoint->delete(); // Soft delete

        return response()->json(['message' => 'Webhook endpoint deleted.']);
    }
}
