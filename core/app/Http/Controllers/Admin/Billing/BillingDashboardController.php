<?php

namespace App\Http\Controllers\Admin\Billing;

use App\Http\Controllers\Controller;
use App\Models\CommercialPlan;
use App\Models\Subscription;

class BillingDashboardController extends Controller
{
    public function index()
    {
        $pageTitle = 'Billing Dashboard';
        
        $activeSubscriptions = Subscription::whereIn('status', ['active', 'trialing'])->count();
        $totalPlans = CommercialPlan::count();
        
        $mrr = Subscription::where('subscriptions.status', 'active')
            ->join('prices', 'subscriptions.price_id', '=', 'prices.id')
            ->where('prices.billing_period', 'monthly')
            ->sum('prices.amount') / 100;

        $arr = $mrr * 12;

        return view('admin.billing.dashboard', compact('pageTitle', 'activeSubscriptions', 'totalPlans', 'mrr', 'arr'));
    }
}
