<?php

namespace App\Http\Controllers\Api\V1;

use App\Data\CreateCustomerSubscriptionData;
use App\Enums\CustomerSubscriptionStatus;
use App\Enums\SubscriptionInterval;
use App\Http\Controllers\Controller;
use App\Models\CustomerSubscription;
use App\Models\User;
use App\Services\Subscriptions\CustomerSubscriptionService;
use App\Traits\ApiResponseFormatter;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerSubscriptionController extends Controller
{
    use ApiResponseFormatter;

    public function index(Request $request)
    {
        $merchant = $this->merchant($request);

        $subscriptions = CustomerSubscription::with('latestInvoice.charge')
            ->where('user_id', $merchant->id)
            ->latest()
            ->paginate((int) $request->input('per_page', 15));

        return $this->apiSuccess([
            'data' => $subscriptions->getCollection()->map(fn (CustomerSubscription $subscription) => $this->resource($subscription))->values(),
            'meta' => [
                'total' => $subscriptions->total(),
                'page' => $subscriptions->currentPage(),
                'per_page' => $subscriptions->perPage(),
            ],
        ]);
    }

    public function store(Request $request, CustomerSubscriptionService $service)
    {
        $merchant = $this->merchant($request);
        $validated = $request->validate([
            'customer' => ['required', 'array'],
            'customer.name' => ['required', 'string', 'max:255'],
            'customer.email' => ['required', 'email', 'max:255'],
            'customer.document' => ['nullable', 'string', 'max:32'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['nullable', 'string', 'size:3'],
            'payment_method' => ['required', Rule::in(['pix', 'card', 'boleto', 'crypto'])],
            'interval' => ['required', Rule::in(array_map(fn (SubscriptionInterval $case) => $case->value, SubscriptionInterval::cases()))],
            'interval_count' => ['nullable', 'integer', 'min:1', 'max:365'],
            'description' => ['nullable', 'string', 'max:255'],
            'start_at' => ['nullable', 'date'],
            'metadata' => ['nullable', 'array'],
            'idempotency_key' => ['nullable', 'string', 'max:255'],
        ]);

        $idempotencyKey = $request->header('Idempotency-Key') ?: ($validated['idempotency_key'] ?? null);
        $data = CreateCustomerSubscriptionData::fromArray($merchant->id, $validated, $idempotencyKey);
        $subscription = $service->create($merchant, $data);
        $statusCode = $subscription->wasRecentlyCreated ? 201 : 200;

        return $this->apiSuccess($this->resource($subscription), $statusCode);
    }

    public function show(Request $request, string $id)
    {
        $merchant = $this->merchant($request);
        $subscription = CustomerSubscription::with(['items', 'latestInvoice.charge'])
            ->where('user_id', $merchant->id)
            ->where(function ($query) use ($id) {
                $query->where('uuid', $id);
                if (is_numeric($id)) {
                    $query->orWhere('id', (int) $id);
                }
            })
            ->first();

        if (! $subscription) {
            return $this->apiError('DGK_SUBSCRIPTION_NOT_FOUND', 'Subscription not found.', 404);
        }

        return $this->apiSuccess($this->resource($subscription));
    }

    public function cancel(Request $request, string $id, CustomerSubscriptionService $service)
    {
        $merchant = $this->merchant($request);
        $request->validate([
            'cancel_at_period_end' => ['nullable', 'boolean'],
        ]);

        $subscription = CustomerSubscription::where('user_id', $merchant->id)
            ->where(function ($query) use ($id) {
                $query->where('uuid', $id);
                if (is_numeric($id)) {
                    $query->orWhere('id', (int) $id);
                }
            })
            ->first();

        if (! $subscription) {
            return $this->apiError('DGK_SUBSCRIPTION_NOT_FOUND', 'Subscription not found.', 404);
        }

        $subscription = $service->cancel($merchant, $subscription, (bool) $request->boolean('cancel_at_period_end'));

        return $this->apiSuccess($this->resource($subscription->load('latestInvoice.charge')));
    }

    private function merchant(Request $request): User
    {
        return User::findOrFail((int) $request->input('api_user_id'));
    }

    private function resource(CustomerSubscription $subscription): array
    {
        $invoice = $subscription->latestInvoice;

        return [
            'id' => $subscription->uuid,
            'status' => $subscription->status instanceof CustomerSubscriptionStatus ? $subscription->status->value : $subscription->status,
            'amount' => (float) $subscription->amount,
            'currency' => $subscription->currency,
            'payment_method' => $subscription->payment_method,
            'interval' => $subscription->interval?->value ?? $subscription->interval,
            'interval_count' => $subscription->interval_count,
            'customer' => [
                'name' => $subscription->customer_name,
                'email' => $subscription->customer_email,
                'document' => $subscription->customer_document,
            ],
            'current_period_start' => optional($subscription->current_period_start)->toIso8601String(),
            'current_period_end' => optional($subscription->current_period_end)->toIso8601String(),
            'next_billing_at' => optional($subscription->next_billing_at)->toIso8601String(),
            'cancel_at_period_end' => $subscription->cancel_at_period_end,
            'canceled_at' => optional($subscription->canceled_at)->toIso8601String(),
            'metadata' => $subscription->metadata ?? [],
            'latest_invoice' => $invoice ? [
                'id' => $invoice->uuid,
                'status' => $invoice->status?->value ?? $invoice->status,
                'amount_due' => (float) $invoice->amount_due,
                'amount_paid' => (float) $invoice->amount_paid,
                'charge_id' => $invoice->charge_id,
                'charge_uuid' => $invoice->charge?->uuid,
            ] : null,
            'created_at' => $subscription->created_at?->toIso8601String(),
        ];
    }
}
