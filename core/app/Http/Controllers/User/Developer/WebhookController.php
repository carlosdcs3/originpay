<?php

namespace App\Http\Controllers\User\Developer;

use App\Http\Controllers\Controller;
use App\Models\WebhookEndpoint;
use App\Models\WebhookDelivery;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Services\TransactionPasswordService;

class WebhookController extends Controller
{
    public function index()
    {
        $endpoints = WebhookEndpoint::where('user_id', auth()->id())->orderBy('created_at', 'desc')->get();
        return view('frontend.user.developer.webhooks.index', compact('endpoints'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'url' => 'required|url|max:255',
            'environment' => 'required|in:test,live',
            'events' => 'required|array',
            'events.*' => 'string',
            'transaction_password' => 'nullable|string|size:4|regex:/^\d{4}$/',
        ]);

        if (! app(TransactionPasswordService::class)->verifyRequest($request, $request->user())) {
            return redirect()->back()->with('error', 'Senha transacional incorreta. Verifique e tente novamente.');
        }

        WebhookEndpoint::create([
            'user_id' => auth()->id(),
            'url' => $request->url,
            'environment' => $request->environment,
            'events' => $request->events,
            'secret' => 'whsec_' . Str::random(24),
            'status' => true
        ]);

        return redirect()->route('user.developer.webhooks.index')->with('success', 'Webhook cadastrado com sucesso!');
    }

    public function show($id)
    {
        $endpoint = WebhookEndpoint::where('user_id', auth()->id())->with(['deliveries' => function($q) {
            $q->orderBy('created_at', 'desc')->take(50);
        }])->findOrFail($id);

        $successCount = WebhookDelivery::where('webhook_endpoint_id', $endpoint->id)->where('success', true)->count();
        $failureCount = WebhookDelivery::where('webhook_endpoint_id', $endpoint->id)->where('success', false)->count();

        // Calculate average latency
        $avgLatency = WebhookDelivery::where('webhook_endpoint_id', $endpoint->id)->avg('latency_ms') ?? 0;

        return view('frontend.user.developer.webhooks.show', compact('endpoint', 'successCount', 'failureCount', 'avgLatency'));
    }

    public function test(Request $request, $id)
    {
        $endpoint = WebhookEndpoint::where('user_id', auth()->id())->findOrFail($id);
        
        // This is a mockup of sending a test event
        $delivery = WebhookDelivery::create([
            'webhook_endpoint_id' => $endpoint->id,
            'event_id' => 'evt_test_' . uniqid(),
            'event_type' => 'ping',
            'payload' => ['message' => 'Webhook test event', 'type' => 'ping'],
            'request_headers' => ['Content-Type' => 'application/json', 'Webhook-Signature' => 'test_sig'],
            'response_headers' => ['Content-Type' => 'application/json'],
            'response_body' => '{"received": true}',
            'http_status' => 200,
            'success' => true,
            'latency_ms' => rand(100, 500)
        ]);

        return back()->with('success', 'Evento de teste disparado com sucesso!');
    }

    public function retry(Request $request, $id)
    {
        $delivery = WebhookDelivery::whereHas('endpoint', function($q) {
            $q->where('user_id', auth()->id());
        })->findOrFail($id);
        
        // Mocking retry
        $newDelivery = $delivery->replicate();
        $newDelivery->http_status = 200;
        $newDelivery->success = true;
        $newDelivery->latency_ms = rand(100, 500);
        $newDelivery->response_body = '{"received": true, "retried": true}';
        $newDelivery->save();

        return back()->with('success', 'Evento reenviado com sucesso!');
    }
}
