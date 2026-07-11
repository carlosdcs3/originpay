<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Billing\PlanAccessService;
use App\Services\Commercial\CommercialService;

class UserBillingController extends Controller
{
    protected $accessService;

    public function __construct(PlanAccessService $accessService)
    {
        $this->accessService = $accessService;
    }

    public function index()
    {
        $pageTitle = 'Billing & Faturamento';
        $user = auth()->user();
        
        $subscription = $this->accessService->getActiveSubscription($user);
        
        // Fetch current usage
        $apiRequests = $this->accessService->getCurrentUsage($user, 'api_requests');
        $apiLimit = $this->accessService->getFeatureLimit($user, 'api_requests');
        
        $webhooks = $this->accessService->getCurrentUsage($user, 'webhooks');
        $webhookLimit = $this->accessService->getFeatureLimit($user, 'webhooks');
        
        $commercialService = new CommercialService();
        $catalog = $commercialService->getActiveCatalog();

        return view('frontend.user.billing.index', compact(
            'pageTitle', 
            'subscription', 
            'apiRequests', 
            'apiLimit',
            'webhooks',
            'webhookLimit',
            'catalog'
        ));
    }
}
