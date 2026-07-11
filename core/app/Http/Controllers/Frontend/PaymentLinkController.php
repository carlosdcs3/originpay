<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\PaymentLink;
use App\Services\PaymentMethodCatalogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\PaymentLinkVisit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PaymentLinkController extends Controller
{
    public function index(Request $request)
    {
        $links = PaymentLink::query()
            ->with([
                'charge:id,uuid,status,payment_method,amount,paid_at',
                'subscription:id,uuid,status,next_billing_at',
            ])
            ->withCount(['visits as visits_count' => function ($query) {
                $query->where('is_bot', false);
            }])
            ->withCount(['visits as converted_count' => function ($query) {
                $query->where('is_bot', false)->whereNotNull('converted_at');
            }])
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $totalVisits = PaymentLinkVisit::whereIn('payment_link_id', $links->pluck('id'))->where('is_bot', false)->count();
        $totalConverted = PaymentLinkVisit::whereIn('payment_link_id', $links->pluck('id'))->where('is_bot', false)->whereNotNull('converted_at')->count();
        $globalConversion = $totalVisits > 0 ? round(($totalConverted / $totalVisits) * 100, 1) : 0;

        return view('frontend.user.payment-links.index', compact('links', 'totalVisits', 'totalConverted', 'globalConversion'));
    }

    public function create(Request $request)
    {
        $type = $request->query('type', PaymentLink::TYPE_CHARGE);
        if (!in_array($type, [PaymentLink::TYPE_CHARGE, PaymentLink::TYPE_SUBSCRIPTION])) {
            $type = PaymentLink::TYPE_CHARGE;
        }

        $paymentMethods = app(PaymentMethodCatalogService::class)->activeChargeMethods();

        return view('frontend.user.payment-links.create', compact('type', 'paymentMethods'));
    }

    public function store(Request $request)
    {
        $type = $request->input('type', PaymentLink::TYPE_CHARGE);
        
        if ($type === PaymentLink::TYPE_SUBSCRIPTION) {
            return $this->storeSubscriptionUnified($request);
        }

        return $this->storeChargeUnified($request);
    }

    private function storeChargeUnified(Request $request)
    {
        $activeMethods = app(PaymentMethodCatalogService::class)->activeChargeMethodCodes();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:1'],
            'allowed_payment_methods' => ['required', 'array', 'min:1'],
            'allowed_payment_methods.*' => ['required', Rule::in($activeMethods)],
            'expires_at' => ['nullable', 'date', 'after:now'],
            
            // Customization fields
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'primary_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'bg_theme' => ['nullable', 'in:dark,light,custom'],
            'bg_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'custom_title' => ['nullable', 'string', 'max:80'],
            'security_msg' => ['nullable', 'string', 'max:100'],
        ]);

        $methods = array_values(array_unique($validated['allowed_payment_methods']));

        $metadata = ['created_from' => 'dashboard'];
        
        $customization = [
            'primary_color' => $validated['primary_color'] ?? null,
            'bg_theme' => $validated['bg_theme'] ?? 'dark',
            'bg_color' => $validated['bg_color'] ?? null,
            'custom_title' => $validated['custom_title'] ?? null,
            'security_msg' => $validated['security_msg'] ?? null,
            'show_branding' => true,
        ];

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('payment-links/logos', 'public');
            $customization['logo'] = $path;
        }

        $metadata['customization'] = $customization;

        $link = PaymentLink::create([
            'uuid' => (string) Str::uuid(),
            'slug' => $this->newSlug(),
            'user_id' => $request->user()->id,
            'type' => PaymentLink::TYPE_CHARGE,
            'amount' => $validated['amount'],
            'currency' => 'BRL',
            'payment_method' => count($methods) === 1 ? $methods[0] : 'multiple',
            'allowed_payment_methods' => $methods,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status' => PaymentLink::STATUS_PENDING,
            'expires_at' => $validated['expires_at'] ?? null,
            'metadata' => $metadata,
        ]);

        return redirect()
            ->route('user.payment-links.index')
            ->with('success', 'Link de cobrança criado com sucesso.')
            ->with('payment_link_url', $link->publicUrl());
    }

    private function storeSubscriptionUnified(Request $request)
    {
        $activeMethods = app(PaymentMethodCatalogService::class)->activeChargeMethodCodes();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:1'],
            'currency' => ['nullable', 'string', 'size:3'],
            'allowed_payment_methods' => ['required', 'array', 'min:1'],
            'allowed_payment_methods.*' => ['required', Rule::in($activeMethods)],
            'interval' => ['required', 'in:day,week,month,year'],
            'interval_count' => ['nullable', 'integer', 'min:1', 'max:24'],
            'start_at' => ['nullable', 'date'],
            
            // Customization fields
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'primary_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'bg_theme' => ['nullable', 'in:dark,light,custom'],
            'bg_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'custom_title' => ['nullable', 'string', 'max:80'],
            'security_msg' => ['nullable', 'string', 'max:100'],
        ]);

        $methods = array_values(array_unique($validated['allowed_payment_methods']));

        $metadata = [
            'created_from' => 'dashboard',
            'interval' => $validated['interval'],
            'interval_count' => $validated['interval_count'] ?? 1,
            'start_at' => $validated['start_at'] ?? null,
        ];
        
        $customization = [
            'primary_color' => $validated['primary_color'] ?? null,
            'bg_theme' => $validated['bg_theme'] ?? 'dark',
            'bg_color' => $validated['bg_color'] ?? null,
            'custom_title' => $validated['custom_title'] ?? null,
            'security_msg' => $validated['security_msg'] ?? null,
            'show_branding' => true,
        ];

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('payment-links/logos', 'public');
            $customization['logo'] = $path;
        }

        $metadata['customization'] = $customization;

        $link = PaymentLink::create([
            'uuid' => (string) Str::uuid(),
            'slug' => $this->newSlug(),
            'user_id' => $request->user()->id,
            'type' => PaymentLink::TYPE_SUBSCRIPTION,
            'amount' => $validated['amount'],
            'currency' => strtoupper($validated['currency'] ?? 'BRL'),
            'payment_method' => count($methods) === 1 ? $methods[0] : 'multiple',
            'allowed_payment_methods' => $methods,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status' => PaymentLink::STATUS_PENDING,
            'expires_at' => null,
            'metadata' => $metadata,
        ]);

        return redirect()
            ->route('user.payment-links.index')
            ->with('success', 'Link de assinatura criado com sucesso.')
            ->with('payment_link_url', $link->publicUrl());
    }

    public function createCharge()
    {
        $paymentMethods = app(PaymentMethodCatalogService::class)->activeChargeMethods();

        return view('frontend.user.payment-links.create-charge', compact('paymentMethods'));
    }

    public function storeCharge(Request $request)
    {
        $activeMethods = app(PaymentMethodCatalogService::class)->activeChargeMethodCodes();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:1'],
            'allowed_payment_methods' => ['required', 'array', 'min:1'],
            'allowed_payment_methods.*' => ['required', Rule::in($activeMethods)],
            'expires_at' => ['nullable', 'date', 'after:now'],
            
            // Customization fields
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'primary_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'bg_theme' => ['nullable', 'in:dark,light,custom'],
            'bg_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'custom_title' => ['nullable', 'string', 'max:80'],
            'security_msg' => ['nullable', 'string', 'max:100'],
        ]);

        $methods = array_values(array_unique($validated['allowed_payment_methods']));

        $metadata = ['created_from' => 'dashboard'];
        
        $customization = [
            'primary_color' => $validated['primary_color'] ?? null,
            'bg_theme' => $validated['bg_theme'] ?? 'dark',
            'bg_color' => $validated['bg_color'] ?? null,
            'custom_title' => $validated['custom_title'] ?? null,
            'security_msg' => $validated['security_msg'] ?? null,
            'show_branding' => true, // Forced for standard accounts
        ];

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('payment-links/logos', 'public');
            $customization['logo'] = $path;
        }

        $metadata['customization'] = $customization;

        $link = PaymentLink::create([
            'uuid' => (string) Str::uuid(),
            'slug' => $this->newSlug(),
            'user_id' => $request->user()->id,
            'type' => PaymentLink::TYPE_CHARGE,
            'amount' => $validated['amount'],
            'currency' => 'BRL',
            'payment_method' => count($methods) === 1 ? $methods[0] : 'multiple',
            'allowed_payment_methods' => $methods,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status' => PaymentLink::STATUS_PENDING,
            'expires_at' => $validated['expires_at'] ?? null,
            'metadata' => $metadata,
        ]);

        return redirect()
            ->route('user.payment-links.index')
            ->with('success', 'Link de cobranca criado com sucesso.')
            ->with('payment_link_url', $link->publicUrl());
    }

    public function createSubscription()
    {
        $paymentMethods = app(PaymentMethodCatalogService::class)->activeChargeMethods();

        return view('frontend.user.payment-links.create-subscription', compact('paymentMethods'));
    }

    public function storeSubscription(Request $request)
    {
        $activeMethods = app(PaymentMethodCatalogService::class)->activeChargeMethodCodes();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:1'],
            'currency' => ['nullable', 'string', 'size:3'],
            'allowed_payment_methods' => ['required', 'array', 'min:1'],
            'allowed_payment_methods.*' => ['required', Rule::in($activeMethods)],
            'interval' => ['required', 'in:day,week,month,year'],
            'interval_count' => ['nullable', 'integer', 'min:1', 'max:24'],
            'start_at' => ['nullable', 'date'],
        ]);

        $methods = array_values(array_unique($validated['allowed_payment_methods']));

        $link = PaymentLink::create([
            'uuid' => (string) Str::uuid(),
            'slug' => $this->newSlug(),
            'user_id' => $request->user()->id,
            'type' => PaymentLink::TYPE_SUBSCRIPTION,
            'amount' => $validated['amount'],
            'currency' => strtoupper($validated['currency'] ?? 'BRL'),
            'payment_method' => count($methods) === 1 ? $methods[0] : 'multiple',
            'allowed_payment_methods' => $methods,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status' => PaymentLink::STATUS_PENDING,
            'expires_at' => null,
            'metadata' => [
                'created_from' => 'dashboard',
                'interval' => $validated['interval'],
                'interval_count' => $validated['interval_count'] ?? 1,
                'start_at' => $validated['start_at'] ?? null,
            ],
        ]);

        return redirect()
            ->route('user.payment-links.index')
            ->with('success', 'Link de assinatura criado com sucesso.')
            ->with('payment_link_url', $link->publicUrl());
    }

    public function cancel(Request $request, PaymentLink $paymentLink)
    {
        abort_unless($paymentLink->user_id === $request->user()->id, 404);

        $paymentLink->update([
            'status' => PaymentLink::STATUS_CANCELED,
            'canceled_at' => now(),
        ]);
        Log::info('Payment link canceled by merchant.', [
            'payment_link_id' => $paymentLink->id,
            'user_id' => $request->user()->id,
            'type' => $paymentLink->type,
        ]);

        return back()->with('success', 'Link cancelado com sucesso.');
    }

    public function show(Request $request, PaymentLink $paymentLink)
    {
        abort_unless($paymentLink->user_id === $request->user()->id, 404);

        $paymentLink->load(['charge', 'subscription']);
        
        $visitsCount = PaymentLinkVisit::where('payment_link_id', $paymentLink->id)->where('is_bot', false)->count();
        $paymentsCount = PaymentLinkVisit::where('payment_link_id', $paymentLink->id)->where('is_bot', false)->whereNotNull('converted_at')->count();
        $conversionRate = $visitsCount > 0 ? round(($paymentsCount / $visitsCount) * 100, 1) : 0;
        
        $revenue = $paymentsCount > 0 ? $paymentsCount * $paymentLink->amount : 0;
        $avgTicket = $paymentsCount > 0 ? $paymentLink->amount : 0; // Since amount is fixed per link

        $lastSale = PaymentLinkVisit::where('payment_link_id', $paymentLink->id)->whereNotNull('converted_at')->latest('converted_at')->first();

        $trafficSources = PaymentLinkVisit::where('payment_link_id', $paymentLink->id)
            ->where('is_bot', false)
            ->selectRaw("COALESCE(utm_source, referer, 'Direto') as source, COUNT(*) as total")
            ->groupBy('source')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        return view('frontend.user.payment-links.show', compact(
            'paymentLink', 
            'visitsCount', 
            'paymentsCount', 
            'conversionRate', 
            'revenue', 
            'avgTicket',
            'lastSale',
            'trafficSources'
        ));
    }

    private function newSlug(): string
    {
        do {
            $slug = Str::lower(Str::random(32));
        } while (PaymentLink::where('slug', $slug)->exists());

        return $slug;
    }
}
