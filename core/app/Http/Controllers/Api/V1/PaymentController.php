<?php

namespace App\Http\Controllers\Api\V1;

use App\Factories\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Charge;
use App\Models\IntegrationChecklist;
use App\Models\User;
use App\Services\ChargeService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    public function store(Request $request, ChargeService $chargeService)
    {
        $environment = $request->input('api_environment');
        $userId = (int) $request->input('api_user_id');

        $checklist = IntegrationChecklist::firstOrCreate(['user_id' => $userId]);
        if (!$checklist->has_test_charge && $environment === 'sandbox') {
            $checklist->update(['has_test_charge' => true]);
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['nullable', Rule::in(['pix', 'card', 'credit_card', 'boleto', 'crypto'])],
            'method' => ['nullable', Rule::in(['pix', 'card', 'credit_card', 'boleto', 'crypto'])],
            'customer' => ['required', 'array'],
            'customer.name' => ['required', 'string'],
            'customer.email' => ['nullable', 'email'],
            'customer.document' => ['nullable', 'string'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $paymentMethod = $validated['payment_method'] ?? $validated['method'] ?? null;
        if (!$paymentMethod) {
            return ApiResponse::validation([]);
        }

        if ($paymentMethod === 'credit_card') {
            $paymentMethod = 'card';
        }

        $merchant = User::findOrFail($userId);
        $charge = $chargeService->create($merchant, (float) $validated['amount'], $paymentMethod, [
            'idempotency_key' => $request->header('Idempotency-Key'),
            'name' => $validated['customer']['name'] ?? null,
            'email' => $validated['customer']['email'] ?? null,
            'document' => $validated['customer']['document'] ?? null,
            'description' => $validated['description'] ?? null,
        ])->refresh();

        return response()->json($this->serializeCharge($charge, $environment), 201);
    }

    public function show($id, Request $request)
    {
        $charge = Charge::where('user_id', $request->input('api_user_id'))
            ->where(function ($query) use ($id) {
                $query->where('id', $id)
                    ->orWhere('uuid', $id)
                    ->orWhere('gateway_charge_id', $id);
            })
            ->first();

        if (! $charge) {
            return ApiResponse::notFound('Resource not found.');
        }

        return response()->json($this->serializeCharge($charge, $request->input('api_environment')));
    }

    public function index(Request $request)
    {
        $charges = Charge::where('user_id', $request->input('api_user_id'))
            ->latest()
            ->paginate((int) $request->input('per_page', 20));

        return response()->json([
            'data' => $charges->getCollection()
                ->map(fn (Charge $charge) => $this->serializeCharge($charge, $request->input('api_environment')))
                ->values(),
            'meta' => [
                'total' => $charges->total(),
                'page' => $charges->currentPage(),
                'per_page' => $charges->perPage(),
            ],
        ]);
    }

    private function serializeCharge(Charge $charge, ?string $environment = null): array
    {
        $paymentMethod = $charge->payment_method->value ?? (string) $charge->payment_method;

        $payload = [
            'id' => $charge->uuid,
            'gateway_id' => $charge->gateway_charge_id,
            'status' => $charge->status->value ?? (string) $charge->status,
            'amount' => (float) $charge->amount,
            'currency' => 'BRL',
            'payment_method' => $paymentMethod,
            'payment_link' => $charge->payment_link,
            'created_at' => $charge->created_at?->toIso8601String(),
            'environment' => $environment,
        ];

        if ($paymentMethod === 'boleto') {
            $payload['boleto'] = [
                'url' => $charge->boleto_url,
                'pdf' => $charge->boleto_pdf_url,
                'barcode' => $charge->barcode,
                'digitable_line' => $charge->digitable_line,
                'expires_at' => $charge->expires_at?->toIso8601String(),
            ];
        }

        if ($paymentMethod === 'pix') {
            $payload['pix'] = [
                'qr_code' => $charge->qr_code,
                'copy_paste' => $charge->pix_copy_paste,
            ];
        }

        return $payload;
    }
}
