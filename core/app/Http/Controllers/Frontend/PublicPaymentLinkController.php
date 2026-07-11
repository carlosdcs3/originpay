<?php

namespace App\Http\Controllers\Frontend;

use App\Data\CreateCustomerSubscriptionData;
use App\Http\Controllers\Controller;
use App\Models\PaymentLink;
use App\Services\PaymentLinks\PaymentLinkStatusSyncService;
use App\Services\PaymentLinks\PaymentLinkAnalyticsService;
use App\Services\PaymentMethodCatalogService;
use App\Services\ChargeService;
use App\Services\Subscriptions\CustomerSubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class PublicPaymentLinkController extends Controller
{
    public function show(string $slug, PaymentLinkStatusSyncService $statusSync, PaymentLinkAnalyticsService $analytics)
    {
        $link = PaymentLink::with([
                'user:id,username,first_name,last_name',
                'charge:id,uuid,status,payment_method,amount,description,customer_name,expires_at,paid_at,payment_link,boleto_url,boleto_pdf_url,barcode,digitable_line,qr_code,pix_copy_paste',
                'subscription:id,uuid,status,customer_name,customer_email,amount,currency,payment_method,interval,interval_count,next_billing_at',
            ])
            ->where('slug', $slug)
            ->first();

        if (! $link) {
            Log::warning('Invalid payment link access attempt.', [
                'slug_hash' => hash('sha256', $slug),
                'ip' => request()->ip(),
            ]);
            abort(404);
        }

        $analytics->recordVisit($link, request());

        $link = $statusSync->sync($link);
        $availablePaymentMethods = app(PaymentMethodCatalogService::class)->activeChargeMethods();
        $activePaymentMethodCodes = $availablePaymentMethods->pluck('code')->all();

        return view('frontend.public.payment-link', compact('link', 'availablePaymentMethods', 'activePaymentMethodCodes'));
    }

    public function submit(
        Request $request,
        string $slug,
        ChargeService $chargeService,
        CustomerSubscriptionService $subscriptionService,
        PaymentLinkStatusSyncService $statusSync,
        PaymentMethodCatalogService $methodCatalog
    ) {
        $activePaymentMethodCodes = $methodCatalog->activeChargeMethodCodes();

        $validated = $request->validate([
            'payment_method' => ['required', Rule::in($activePaymentMethodCodes)],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_document' => ['required', 'string', 'max:30'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'address_line' => ['nullable', 'string', 'max:255'],
            'address_city' => ['nullable', 'string', 'max:120'],
            'address_state' => ['nullable', 'string', 'max:2'],
            'address_zip' => ['nullable', 'string', 'max:20'],
        ]);

        $preflightLink = PaymentLink::with(['charge', 'subscription'])
            ->where('slug', $slug)
            ->firstOrFail();
        $preflightLink = $statusSync->sync($preflightLink);
        abort_unless($preflightLink->isPubliclyPayable(), 422, 'Este link nao esta disponivel para pagamento.');
        abort_unless($preflightLink->allowsPaymentMethod($validated['payment_method']), 422, 'Metodo de pagamento nao permitido para este link.');

        $link = DB::transaction(function () use ($slug, $validated, $chargeService, $subscriptionService, $statusSync, $activePaymentMethodCodes) {
            $link = PaymentLink::where('slug', $slug)->lockForUpdate()->firstOrFail();
            $link->loadMissing(['charge', 'subscription']);
            $link = $statusSync->sync($link);

            abort_unless($link->isPubliclyPayable(), 422, 'Este link nao esta disponivel para pagamento.');
            abort_unless($link->allowsPaymentMethod($validated['payment_method']), 422, 'Metodo de pagamento nao permitido para este link.');
            abort_unless(in_array($validated['payment_method'], $activePaymentMethodCodes, true), 422, 'Metodo de pagamento indisponivel no momento.');

            if ($link->charge_id) {
                return $link->refresh()->load(['user:id,username,first_name,last_name', 'charge', 'subscription']);
            }

            if ($link->type === PaymentLink::TYPE_SUBSCRIPTION) {
                $metadata = $link->metadata ?? [];
                $subscription = $subscriptionService->create(
                    $link->user,
                    CreateCustomerSubscriptionData::fromArray($link->user_id, [
                        'amount' => $link->amount,
                        'currency' => $link->currency,
                        'payment_method' => $validated['payment_method'],
                        'interval' => $metadata['interval'] ?? 'month',
                        'interval_count' => $metadata['interval_count'] ?? 1,
                        'start_at' => $metadata['start_at'] ?? now()->toDateString(),
                        'description' => $link->description ?? $link->title,
                        'customer' => [
                            'name' => $validated['customer_name'],
                            'email' => $validated['customer_email'],
                            'document' => $validated['customer_document'],
                        ],
                        'metadata' => [
                            'payment_link_uuid' => $link->uuid,
                            'customer_phone' => $validated['customer_phone'] ?? null,
                            'payment_link_visit_id' => session('payment_link_visit_id'),
                        ],
                    ], 'paylink_' . $link->uuid)
                )->load('latestInvoice.charge');

                $charge = $subscription->latestInvoice?->charge;
                $link->update([
                    'customer_subscription_id' => $subscription->id,
                    'charge_id' => $charge?->id,
                    'payment_method' => $validated['payment_method'],
                    'status' => PaymentLink::STATUS_AWAITING_PAYMENT,
                    'metadata' => array_merge($metadata, [
                        'submitted_at' => now()->toIso8601String(),
                        'customer_email_hash' => hash('sha256', strtolower($validated['customer_email'])),
                    ]),
                ]);

                return $link->refresh()->load(['user:id,username,first_name,last_name', 'charge', 'subscription']);
            }

            $charge = $chargeService->create($link->user, (float) $link->amount, $validated['payment_method'], [
                'idempotency_key' => 'paylink_' . $link->uuid,
                'name' => $validated['customer_name'],
                'email' => $validated['customer_email'],
                'document' => $validated['customer_document'],
                'description' => $link->description ?? $link->title,
            ]);

            $chargeMeta = $charge->metadata ?? [];
            $chargeMeta['payment_link_uuid'] = $link->uuid;
            $chargeMeta['payment_link_visit_id'] = session('payment_link_visit_id');
            $charge->update(['metadata' => $chargeMeta]);

            $link->update([
                'charge_id' => $charge->id,
                'payment_method' => $validated['payment_method'],
                'status' => PaymentLink::STATUS_AWAITING_PAYMENT,
                'metadata' => array_merge($link->metadata ?? [], [
                    'submitted_at' => now()->toIso8601String(),
                    'customer_email_hash' => hash('sha256', strtolower($validated['customer_email'])),
                    'customer_phone' => $validated['customer_phone'] ?? null,
                ]),
            ]);

            return $link->refresh()->load(['user:id,username,first_name,last_name', 'charge', 'subscription']);
        });

        $availablePaymentMethods = $methodCatalog->activeChargeMethods();

        return view('frontend.public.payment-link', compact('link', 'availablePaymentMethods', 'activePaymentMethodCodes'));
    }
}
