<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\ChargeStatus;
use App\Http\Controllers\Controller;
use App\Models\Charge;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $charges = Charge::query()
            ->where('user_id', $request->user()->id)
            ->where(function ($query) {
                $query->whereNotNull('customer_email')
                    ->orWhereNotNull('customer_document')
                    ->orWhereNotNull('customer_name');
            })
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('created_at', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('created_at', '<=', $request->date_to))
            ->latest()
            ->get();

        $customers = $charges
            ->groupBy(fn (Charge $charge) => $charge->customer_document ?: $charge->customer_email ?: $charge->customer_name ?: $charge->uuid)
            ->map(function (Collection $items, string $key) {
                $first = $items->first();
                $paidItems = $items->where('status', ChargeStatus::PAID);
                $lastCharge = $items->sortByDesc('updated_at')->first();

                return [
                    'id' => $key,
                    'name' => $first->customer_name ?: 'Cliente sem nome',
                    'email' => $first->customer_email ?: 'E-mail nao informado',
                    'document' => $first->customer_document ?: 'Documento nao informado',
                    'total_amount' => $paidItems->sum(fn (Charge $charge) => (float) $charge->amount),
                    'total_charges' => $items->count(),
                    'paid_charges' => $paidItems->count(),
                    'last_charge_at' => $lastCharge?->updated_at,
                    'created_at' => $items->min('created_at'),
                    'status' => $this->resolveStatus($items),
                ];
            })
            ->values();

        if ($request->filled('search')) {
            $term = mb_strtolower($request->search);
            $customers = $customers->filter(function (array $customer) use ($term) {
                return str_contains(mb_strtolower($customer['name']), $term)
                    || str_contains(mb_strtolower($customer['email']), $term)
                    || str_contains(mb_strtolower($customer['document']), $term)
                    || str_contains(mb_strtolower((string) $customer['id']), $term);
            })->values();
        }

        $statusCounts = [
            'all' => $customers->count(),
            'active' => $customers->where('status', 'active')->count(),
            'inactive' => $customers->where('status', 'inactive')->count(),
            'blocked' => $customers->where('status', 'blocked')->count(),
            'risk' => $customers->where('status', 'risk')->count(),
        ];

        $stats = [
            'total' => $customers->count(),
            'active' => $customers->where('status', 'active')->count(),
            'new' => $customers->filter(fn (array $customer) => $customer['created_at'] && $customer['created_at']->gte(now()->subDays(7)))->count(),
            'volume' => $customers->sum('total_amount'),
            'ticket' => $customers->sum('paid_charges') > 0 ? $customers->sum('total_amount') / $customers->sum('paid_charges') : 0,
        ];

        if ($request->filled('status') && $request->status !== 'all') {
            $customers = $customers->where('status', $request->status)->values();
        }

        $paginatedCustomers = $this->paginate($customers, $request, 7);

        return view('frontend.user.customer.index', [
            'customers' => $paginatedCustomers,
            'stats' => $stats,
            'statusCounts' => $statusCounts,
        ]);
    }

    private function resolveStatus(Collection $charges): string
    {
        $failedCount = $charges->whereIn('status', [ChargeStatus::EXPIRED, ChargeStatus::CANCELLED, ChargeStatus::REFUNDED])->count();
        $paidCount = $charges->where('status', ChargeStatus::PAID)->count();

        if ($charges->count() >= 5 && $failedCount > $paidCount) {
            return 'risk';
        }

        if ($charges->count() > 0 && $paidCount === 0 && $charges->max('updated_at')?->lt(now()->subDays(30))) {
            return 'inactive';
        }

        return 'active';
    }

    private function paginate(Collection $items, Request $request, int $perPage): LengthAwarePaginator
    {
        $page = LengthAwarePaginator::resolveCurrentPage();
        $pageItems = $items->slice(($page - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $pageItems,
            $items->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
    }
}
