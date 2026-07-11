<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\CustomerSubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Models\CustomerSubscription;
use App\Services\Subscriptions\CustomerSubscriptionService;
use Illuminate\Http\Request;

class CustomerSubscriptionDashboardController extends Controller
{
    public function index(Request $request)
    {
        $merchant = $request->user();
        $baseQuery = CustomerSubscription::query()
            ->select([
                'id',
                'uuid',
                'user_id',
                'customer_name',
                'customer_email',
                'customer_document',
                'status',
                'amount',
                'currency',
                'payment_method',
                'interval',
                'interval_count',
                'description',
                'next_billing_at',
                'created_at',
            ])
            ->with([
                'items:id,customer_subscription_id,description',
                'latestInvoice' => function ($query) {
                    $query->select([
                        'subscription_invoices.id',
                        'subscription_invoices.uuid',
                        'subscription_invoices.customer_subscription_id',
                        'subscription_invoices.charge_id',
                        'subscription_invoices.status',
                        'subscription_invoices.paid_at',
                        'subscription_invoices.failed_at',
                        'subscription_invoices.updated_at',
                    ]);
                },
                'latestInvoice.charge:id,uuid,status,paid_at,updated_at',
            ])
            ->where('user_id', $merchant->id);

        $filteredQuery = (clone $baseQuery);
        $this->applyFilters($filteredQuery, $request);

        $subscriptions = $filteredQuery
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $recurringRevenueStatuses = [
            CustomerSubscriptionStatus::ACTIVE->value,
            CustomerSubscriptionStatus::PAST_DUE->value,
        ];
        $today = now(config('app.timezone'))->toDateString();

        $metrics = [
            'mrr' => (clone $baseQuery)->whereIn('status', $recurringRevenueStatuses)->sum('amount'),
            'active' => (clone $baseQuery)->where('status', CustomerSubscriptionStatus::ACTIVE->value)->count(),
            'renewals_today' => (clone $baseQuery)->whereIn('status', $recurringRevenueStatuses)->whereDate('next_billing_at', $today)->count(),
            'past_due' => (clone $baseQuery)->where('status', CustomerSubscriptionStatus::PAST_DUE->value)->count(),
            'canceled' => (clone $baseQuery)->where('status', CustomerSubscriptionStatus::CANCELED->value)->count(),
            'average_ticket' => (clone $baseQuery)->whereIn('status', $recurringRevenueStatuses)->avg('amount') ?? 0,
        ];

        $statusCounts = [
            'all' => (clone $baseQuery)->count(),
            CustomerSubscriptionStatus::PENDING->value => (clone $baseQuery)->where('status', CustomerSubscriptionStatus::PENDING->value)->count(),
            CustomerSubscriptionStatus::ACTIVE->value => (clone $baseQuery)->where('status', CustomerSubscriptionStatus::ACTIVE->value)->count(),
            CustomerSubscriptionStatus::PAST_DUE->value => (clone $baseQuery)->where('status', CustomerSubscriptionStatus::PAST_DUE->value)->count(),
            CustomerSubscriptionStatus::CANCELED->value => (clone $baseQuery)->where('status', CustomerSubscriptionStatus::CANCELED->value)->count(),
            CustomerSubscriptionStatus::INCOMPLETE->value => (clone $baseQuery)->where('status', CustomerSubscriptionStatus::INCOMPLETE->value)->count(),
        ];

        return view('frontend.user.subscriptions.index', compact('subscriptions', 'metrics', 'statusCounts'));
    }

    public function show(Request $request, string $id)
    {
        $subscription = CustomerSubscription::select([
                'id',
                'uuid',
                'user_id',
                'customer_name',
                'customer_email',
                'customer_document',
                'status',
                'amount',
                'currency',
                'payment_method',
                'interval',
                'interval_count',
                'description',
                'start_at',
                'current_period_start',
                'current_period_end',
                'next_billing_at',
                'canceled_at',
                'cancel_at_period_end',
                'last_error',
            ])
            ->with([
                'items:id,customer_subscription_id,description,quantity,unit_amount,total_amount',
                'invoices' => function ($query) {
                    $query->select([
                            'id',
                            'uuid',
                            'customer_subscription_id',
                            'user_id',
                            'charge_id',
                            'status',
                            'period_start',
                            'period_end',
                            'amount_due',
                            'amount_paid',
                            'currency',
                            'due_at',
                            'paid_at',
                            'failed_at',
                            'last_error',
                            'created_at',
                            'updated_at',
                        ])
                        ->orderByDesc('period_start')
                        ->orderByDesc('id');
                },
                'invoices.charge:id,uuid,status,amount,paid_at,created_at,updated_at',
            ])
            ->where('user_id', $request->user()->id)
            ->where(function ($query) use ($id) {
                $query->where('uuid', $id);
                if (is_numeric($id)) {
                    $query->orWhere('id', (int) $id);
                }
            })
            ->firstOrFail();

        return view('frontend.user.subscriptions.show', compact('subscription'));
    }

    public function cancel(Request $request, string $id, CustomerSubscriptionService $service)
    {
        $validated = $request->validate([
            'cancel_at_period_end' => ['nullable', 'boolean'],
        ]);

        $subscription = CustomerSubscription::where('user_id', $request->user()->id)
            ->where(function ($query) use ($id) {
                $query->where('uuid', $id);
                if (is_numeric($id)) {
                    $query->orWhere('id', (int) $id);
                }
            })
            ->firstOrFail();

        $service->cancel($request->user(), $subscription, (bool) ($validated['cancel_at_period_end'] ?? false));

        return back()->with('success', 'Assinatura atualizada com sucesso.');
    }

    private function applyFilters($query, Request $request): void
    {
        if ($search = $request->input('search')) {
            $query->where(function ($inner) use ($search) {
                $inner->where('uuid', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_email', 'like', "%{$search}%")
                    ->orWhere('customer_document', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('items', function ($itemQuery) use ($search) {
                        $itemQuery->where('description', 'like', "%{$search}%");
                    });
            });
        }

        if ($status = $request->input('status')) {
            if ($status !== 'all') {
                $query->where('status', $status);
            }
        }

        if ($method = $request->input('payment_method')) {
            if ($method !== 'all') {
                $query->where('payment_method', $method);
            }
        }

        if ($from = $request->input('date_from')) {
            $query->whereDate('next_billing_at', '>=', $from);
        }

        if ($to = $request->input('date_to')) {
            $query->whereDate('next_billing_at', '<=', $to);
        }
    }
}
