<?php
namespace App\Services\Connect;

class ConnectAccessService
{
    protected $subscriptionService;
    protected $featureGate;

    public function __construct(ConnectSubscriptionService $subscriptionService, ConnectFeatureGate $featureGate)
    {
        $this->subscriptionService = $subscriptionService;
        $this->featureGate = $featureGate;
    }

    public function canUsePremiumFeature($merchantId, $feature = null)
    {
        if (!$this->featureGate->isEnabled('connect_module')) {
            return false;
        }
        if ($feature && !$this->featureGate->isEnabled($feature)) {
            return false;
        }
        return $this->subscriptionService->hasActiveAccess($merchantId);
    }

    public function isTransactionalEmailAllowed()
    {
        return true; 
    }
}
