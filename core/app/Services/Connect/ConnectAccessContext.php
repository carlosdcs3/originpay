<?php
namespace App\Services\Connect;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Connect\Subscription;
use App\Models\Connect\UsageLimit;

class ConnectAccessContext
{
    protected $merchantId;
    protected $subscription;
    protected $planService;
    protected $featureGate;
    
    // O(1) Hash Map
    protected $capabilities = [];
    protected $gracePeriodDays = 3;
    
    // Cached variables to avoid multiple queries within the same request
    protected $usageLimits = null;

    private function __construct($merchantId)
    {
        $this->merchantId = $merchantId;
        $this->planService = app(ConnectPlanService::class);
        $this->featureGate = app(ConnectFeatureGate::class);

        $this->subscription = Subscription::where('merchant_id', $merchantId)->first();
        
        $this->buildCapabilitiesPipeline();
    }

    protected function buildCapabilitiesPipeline()
    {
        $planName = $this->subscription->plan_name ?? 'free';

        // 1. Base Plan
        $caps = app(ConnectFeatureResolver::class)->resolve($planName);

        // 2. Addons
        $caps = app(ConnectAddonResolver::class)->resolve($caps, $this->merchantId);

        // 3. Feature Flags
        $caps = app(ConnectFeatureFlagResolver::class)->resolve($caps, $this->merchantId);

        // Convert to O(1) lookup map
        foreach ($caps as $cap) {
            $this->capabilities[$cap] = true;
        }
    }

    /**
     * Instantiates or retrieves the context bound to the Request via Laravel's Container.
     */
    public static function getInstance($merchantId)
    {
        $key = "connect.access.context.{$merchantId}";
        
        if (!app()->has($key)) {
            app()->instance($key, new self($merchantId));
        }

        return app($key);
    }

    public function subscription()
    {
        return $this->subscription;
    }

    public function plan()
    {
        return $this->planService->getStarterPlan();
    }

    public function limits()
    {
        return $this->plan()['limits'] ?? [];
    }

    public function usage()
    {
        if ($this->usageLimits === null) {
            $this->usageLimits = UsageLimit::where('merchant_id', $this->merchantId)->get()->keyBy('channel');
        }
        return $this->usageLimits;
    }

    public function remainingQuota($channel)
    {
        $usage = $this->usage()->get($channel);
        if (!$usage || $usage->monthly_limit == 0) return 999999;
        return max(0, $usage->monthly_limit - $usage->current_usage);
    }

    public function expiresAt()
    {
        return $this->subscription ? $this->subscription->ends_at : null;
    }

    public function hasGraceAccess()
    {
        if (!$this->subscription || $this->subscription->status !== 'past_due') return false;
        
        $pastDueSince = $this->subscription->ends_at ?? $this->subscription->updated_at;
        return Carbon::now()->diffInDays($pastDueSince) <= $this->gracePeriodDays;
    }

    public function isActive()
    {
        if (!$this->subscription) return false;
        if (in_array($this->subscription->status, ['active', 'trialing'])) return true;
        if ($this->subscription->status === 'past_due') return $this->hasGraceAccess();
        
        return false;
    }

    public function isEnabled()
    {
        return $this->featureGate->isEnabled('connect_module');
    }

    public function hasFeature($feature)
    {
        if (!$this->isEnabled()) return false;

        if (str_contains($feature, 'whatsapp') && !$this->featureGate->isEnabled('connect_whatsapp')) {
            return false;
        }

        // O(1) lookup
        return isset($this->capabilities[$feature]) && $this->isActive();
    }
}
