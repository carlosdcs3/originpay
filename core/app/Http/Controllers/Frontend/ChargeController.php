<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\PaymentMethodCatalogService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ChargeController extends Controller
{
    /**
     * Renderiza a tela de Nova Cobrança (Split Layout).
     */
    public function create()
    {
        $paymentMethods = app(PaymentMethodCatalogService::class)->activeChargeMethods();
        $recentCharges  = \App\Models\Charge::where('user_id', auth()->id())
            ->latest()->limit(5)->get();
        return view('frontend.user.charge.create', compact('paymentMethods', 'recentCharges'));
    }

    public function store(Request $request, \App\Services\ChargeService $chargeService, PaymentMethodCatalogService $methodCatalog)
    {
        $activeMethods = $methodCatalog->activeChargeMethodCodes();

        // Validação básica
        $validated = $request->validate([
            'method' => ['required', Rule::in($activeMethods)],
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string|max:255',
            'expires_at' => 'required|string',
            'customer_name' => 'nullable|string|max:255',
            'customer_email' => 'nullable|email',
            'customer_document' => 'nullable|string',
        ]);

        $paymentMethod = \App\Enums\PaymentMethod::tryFrom($validated['method']);

        if (! $paymentMethod) {
            return response()->json([
                'status' => 'error',
                'message' => 'Metodo de pagamento indisponivel para criacao de cobranca.',
            ], 422);
        }

        $charge = $chargeService->create(
            $request->user(),
            $validated['amount'],
            $paymentMethod->value,
            [
                'name'        => $validated['customer_name'] ?? null,
                'email'       => $validated['customer_email'] ?? null,
                'document'    => $validated['customer_document'] ?? null,
                'description' => $validated['description'] ?? null,
            ]
        );

        $paymentLink = \App\Models\PaymentLink::create([
            'uuid' => Str::uuid()->toString(),
            'slug' => $this->newPaymentLinkSlug(),
            'user_id' => $request->user()->id,
            'type' => \App\Models\PaymentLink::TYPE_CHARGE,
            'charge_id' => $charge->id,
            'amount' => $charge->amount,
            'currency' => 'BRL',
            'payment_method' => $paymentMethod->value,
            'allowed_payment_methods' => [$paymentMethod->value],
            'title' => $validated['description'],
            'description' => $validated['description'] ?? null,
            'status' => \App\Models\PaymentLink::STATUS_AWAITING_PAYMENT,
            'expires_at' => $charge->expires_at,
            'metadata' => ['created_from' => 'charge_dashboard'],
        ]);

        $isPix = $paymentMethod === \App\Enums\PaymentMethod::PIX;
        $isBoleto = $paymentMethod === \App\Enums\PaymentMethod::BOLETO;

        return response()->json([
            'status' => 'success',
            'charge' => [
                'id' => $charge->gateway_charge_id,
                'amount' => $charge->amount,
                'method' => $charge->payment_method->value,
                'description' => $charge->description,
                'expires_at' => $charge->expires_at->toIso8601String(),
                'payment_link' => $charge->payment_link,
                'public_payment_link' => $paymentLink->publicUrl(),
                'qr_code' => $isPix ? $charge->qr_code : null,
                'copy_paste' => $isPix ? $charge->pix_copy_paste : null,
                'boleto' => $isBoleto ? [
                    'url' => $charge->boleto_url,
                    'pdf' => $charge->boleto_pdf_url,
                    'barcode' => $charge->barcode,
                    'digitable_line' => $charge->digitable_line,
                    'expires_at' => $charge->expires_at?->toIso8601String(),
                ] : null,
            ],
            'message' => 'Cobrança criada com sucesso!'
        ]);
    }

    private function newPaymentLinkSlug(): string
    {
        do {
            $slug = Str::lower(Str::random(32));
        } while (\App\Models\PaymentLink::where('slug', $slug)->exists());

        return $slug;
    }

    public function index(Request $request)
    {
        $availablePaymentMethods = app(PaymentMethodCatalogService::class)->activeChargeMethods();
        $activeMethod = $request->input('method', 'all');
        $validMethods = array_merge(['all'], $availablePaymentMethods->pluck('code')->all());

        if (! in_array($activeMethod, $validMethods, true)) {
            $activeMethod = 'all';
        }

        $baseQuery = \App\Models\Charge::where('user_id', $request->user()->id);
        $query = (clone $baseQuery)->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($activeMethod !== 'all') {
            $query->where('payment_method', $activeMethod);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('gateway_charge_id', 'like', "%{$search}%")
                  ->orWhere('gateway_reference', 'like', "%{$search}%")
                  ->orWhere('uuid', 'like', "%{$search}%")
                  ->orWhere('payment_link', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_email', 'like', "%{$search}%")
                  ->orWhere('customer_document', 'like', "%{$search}%");
            });
        }

        $statsQuery = clone $baseQuery;
        if ($activeMethod !== 'all') {
            $statsQuery->where('payment_method', $activeMethod);
        }

        $totalCount = (clone $statsQuery)->count();
        $paidCount = (clone $statsQuery)->where('status', \App\Enums\ChargeStatus::PAID->value)->count();
        $pendingCount = (clone $statsQuery)->whereIn('status', [
            \App\Enums\ChargeStatus::PENDING->value,
            \App\Enums\ChargeStatus::WAITING_PAYMENT->value,
        ])->count();

        $stats = [
            'paid_count' => $paidCount,
            'pending_count' => $pendingCount,
            'paid_volume' => (clone $statsQuery)->where('status', \App\Enums\ChargeStatus::PAID->value)->sum('net_amount'),
            'total_count' => $totalCount,
        ];

        $methodCounts = ['all' => (clone $baseQuery)->count()];
        foreach ($availablePaymentMethods as $method) {
            $methodCounts[$method['code']] = (clone $baseQuery)->where('payment_method', $method['code'])->count();
        }

        $charges = $query->paginate(15)->withQueryString();
        return view('frontend.user.charge.index', compact('charges', 'stats', 'methodCounts', 'activeMethod', 'availablePaymentMethods'));
    }

    public function show(Request $request, $id)
    {
        $charge = \App\Models\Charge::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->with('events')
            ->firstOrFail();

        return view('frontend.user.charge.show', compact('charge'));
    }
}
