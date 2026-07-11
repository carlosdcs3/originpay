<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\MerchantStatus;
use App\Enums\TrxStatus;
use App\Enums\TrxType;
use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    /**
     * Display the dashboard view.
     */
    public function index(): View
    {
        $user = auth()->user();

        // Parse Dates
        $startDateStr = request('start_date');
        $endDateStr = request('end_date');
        
        try {
            $endDate = $endDateStr ? Carbon::parse($endDateStr)->endOfDay() : Carbon::now()->endOfDay();
            // Default 7 days including today means 6 days ago + today
            $startDate = $startDateStr ? Carbon::parse($startDateStr)->startOfDay() : Carbon::now()->subDays(6)->startOfDay();
            
            if ($startDate->gt($endDate)) {
                $startDate = clone $endDate;
                $startDate->subDays(6)->startOfDay();
            }
        } catch (\Exception $e) {
            $endDate = Carbon::now()->endOfDay();
            $startDate = Carbon::now()->subDays(6)->startOfDay();
        }

        $relevantTypes  = $this->getRelevantTransactionTypes($user);
        $financialStats = $this->getFinancialStats($user, $relevantTypes, $startDate, $endDate);
        $staticStats    = $this->getStaticStats($user);
        $statistics     = array_merge($financialStats, $staticStats);

        $chartData = $this->getChartTransactionStats($user, $startDate, $endDate);
        
        $totalSuccessDeposit  = $this->getTotalAmountForTrx($user, TrxType::DEPOSIT, $startDate, $endDate);
        $totalSuccessWithdraw = $this->getTotalAmountForTrx($user, TrxType::WITHDRAW, $startDate, $endDate);

        $transactions = $user->transactions()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->latest()
            ->take(5)
            ->get();

        $periodTransactions = $user->transactions()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get(['status', 'amount', 'created_at']);

        $approvedTransactions = $periodTransactions->where('status', TrxStatus::COMPLETED)->count();
        $pendingTransactions = $periodTransactions->where('status', TrxStatus::PENDING)->count();
        $rejectedTransactions = $periodTransactions
            ->filter(fn ($transaction) => in_array($transaction->status, [TrxStatus::FAILED, TrxStatus::CANCELED], true))
            ->count();
        $totalTransactions = $periodTransactions->count();

        $totalApprovedAmount = $periodTransactions->where('status', TrxStatus::COMPLETED)->sum('amount');
        $totalPendingAmount = $periodTransactions->where('status', TrxStatus::PENDING)->sum('amount');
        $totalRejectedAmount = $periodTransactions->filter(fn ($transaction) => in_array($transaction->status, [TrxStatus::FAILED, TrxStatus::CANCELED], true))->sum('amount');
        
        $lastTransaction = $periodTransactions->sortByDesc('created_at')->first();

        $dashboardSummary = [
            'approved' => $approvedTransactions,
            'pending' => $pendingTransactions,
            'rejected' => $rejectedTransactions,
            'total' => $totalTransactions,
            'approved_amount' => $totalApprovedAmount,
            'pending_amount' => $totalPendingAmount,
            'rejected_amount' => $totalRejectedAmount,
            'last_transaction' => $lastTransaction,
            'query_time' => now(),
            'approval_rate' => $totalTransactions > 0
                ? round(($approvedTransactions / $totalTransactions) * 100, 1)
                : null,
        ];
            
        $userWallets  = clone $user->wallets;

        $kycProfile = \App\Models\KycProfile::firstOrCreate(
            ['user_id' => $user->id],
            ['level' => 0, 'status' => 'PENDING']
        );

        $hasKycLimitLock = app(\App\Services\Compliance\AccountRestrictionService::class)->hasRestriction($user, 'KYC_LIMIT_LOCK');

        // Onboarding Checklist
        $kycApproved = $user->kyc_status === \App\Enums\KycStatus::APPROVED;
        $kycSubmitted = $user->kyc_status !== \App\Enums\KycStatus::NOT_STARTED && $user->kyc_status !== \App\Enums\KycStatus::DRAFT;
        $apiCreated = $user->apiKeys()->exists();
        $pixConfigured = \App\Models\PixKey::where('user_id', $user->id)->exists();

        $onboarding = [
            'kyc_approved' => $kycApproved,
            'kyc_submitted' => $kycSubmitted,
            'kyc_rejected' => $user->kyc_status === \App\Enums\KycStatus::REJECTED,
            'api_key_created' => $apiCreated,
            'pix_configured' => $pixConfigured,
        ];
        
        $completedSteps = 0;
        if ($kycApproved) $completedSteps++;
        if ($apiCreated) $completedSteps++;
        if ($pixConfigured) $completedSteps++;
        
        $totalSteps = 3;
        $onboardingProgress = round(($completedSteps / $totalSteps) * 100);

        return view('frontend.user.dashboard-v2.index', compact(
            'statistics',
            'chartData',
            'totalSuccessDeposit',
            'totalSuccessWithdraw',
            'transactions',
            'dashboardSummary',
            'userWallets',
            'kycProfile',
            'hasKycLimitLock',
            'onboarding',
            'completedSteps',
            'totalSteps',
            'onboardingProgress',
            'startDate',
            'endDate'
        ));
    }

    private function getRelevantTransactionTypes(User $user): array
    {
        $types = [
            TrxType::DEPOSIT,
            TrxType::WITHDRAW,
            TrxType::SEND_MONEY,
            TrxType::REQUEST_MONEY,
            TrxType::EXCHANGE_MONEY,
            TrxType::RECEIVE_MONEY,
            TrxType::REWARD,
        ];

        if ($user->isMerchant()) {
            $types[] = TrxType::RECEIVE_PAYMENT;
        }

        return $types;
    }

    private function getFinancialStats(User $user, array $relevantTypes, Carbon $startDate, Carbon $endDate): array
    {
        $transactions = $user->transactions()
            ->where('status', TrxStatus::COMPLETED)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('trx_type', $relevantTypes)
            ->selectRaw('trx_type, currency, COALESCE(SUM(amount), 0) as total_amount')
            ->groupBy('trx_type', 'currency')
            ->get();

        $stats = [];

        foreach ($relevantTypes as $trxType) {
            $filtered = $transactions->where('trx_type', $trxType);

            $formattedValue = $filtered->map(
                fn ($row) => getSymbol($row->currency).number_format($row->total_amount, 2)
            )->implode(', ');

            $stats[] = [
                'title'       => $trxType->label(),
                'value'       => $formattedValue ?: getSymbol('BRL').' 0,00',
                'icon'        => $trxType->icon(),
                'color_class' => $trxType->kebabCase(),
                'link'        => route('user.transaction.index'),
            ];
        }

        return $stats;
    }

    private function getStaticStats(User $user): array
    {
        $stats = [
            [
                'title'       => __('Total Tickets'),
                'value'       => $user->tickets()->count(),
                'icon'        => 'tickets',
                'color_class' => 'tickets',
                'link'        => '#',
            ],
            [
                'title'       => __('Total Referrals'),
                'value'       => $user->referrals()->count(),
                'icon'        => 'referrals',
                'color_class' => 'referrals',
                'link'        => '#',
            ],
        ];

        if ($user->isMerchant()) {
            $stats[] = [
                'title'       => __('Merchant Shop'),
                'value'       => $user->merchants()->count(),
                'icon'        => 'merchant',
                'color_class' => 'merchant',
                'link'        => '#',
            ];
            $stats[] = [
                'title'       => __('Awaiting Merchant'),
                'value'       => $user->merchants()->where('status', MerchantStatus::PENDING)->count(),
                'icon'        => 'merchant-2',
                'color_class' => 'merchant-pending',
                'link'        => '#',
            ];
        }

        return $stats;
    }

    private function getChartTransactionStats(User $user, Carbon $startDate, Carbon $endDate): array
    {
        $diffInDays = $startDate->diffInDays($endDate);
        
        $transactions = $user->transactions()
            ->whereIn('trx_type', [TrxType::DEPOSIT, TrxType::RECEIVE_PAYMENT, TrxType::RECEIVE_MONEY])
            ->where('status', TrxStatus::COMPLETED)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw("
                DATE(created_at) as date,
                SUM(amount) as success_total
            ")
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        $labels = [];
        $data = [];

        $currentDate = clone $startDate;
        for ($i = 0; $i <= $diffInDays; $i++) {
            $dateString = $currentDate->format('Y-m-d');
            $labels[] = $currentDate->format('d/m');
            
            $tx = $transactions->firstWhere('date', $dateString);
            $data[] = $tx ? (float)$tx->success_total : 0;
            
            $currentDate->addDay();
        }

        return [
            'labels' => $labels,
            'data' => $data
        ];
    }

    private function getTotalAmountForTrx(User $user, TrxType $trxType, Carbon $startDate, Carbon $endDate): string
    {
        $total = $user->transactions()
            ->where('trx_type', $trxType)
            ->where('status', TrxStatus::COMPLETED)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('currency, SUM(amount) as total')
            ->groupBy('currency')
            ->get()
            ->map(fn ($row) => getSymbol($row->currency).number_format($row->total, 2))
            ->implode(', ');

        return $total ?: getSymbol('BRL').' 0,00';
    }
}
