<?php

namespace App\Services\Billing;

use App\Models\User;
use App\Models\Subscription;
use App\Models\UsageMetric;
use App\Models\PlanVersion;
use Carbon\Carbon;

class PlanAccessService
{
    /**
     * Get the active subscription for the user.
     */
    public function getActiveSubscription(User $user)
    {
        return Subscription::where('user_id', $user->id)
            ->whereIn('status', ['active', 'trialing'])
            ->with(['planVersion.features.feature'])
            ->first();
    }

    /**
     * Check if a user can access a specific feature (boolean).
     */
    public function canAccess(User $user, string $featureSlug): bool
    {
        $subscription = $this->getActiveSubscription($user);
        if (!$subscription || !$subscription->planVersion) return false;

        $feature = $subscription->planVersion->features->first(function ($f) use ($featureSlug) {
            return $f->feature && $f->feature->slug === $featureSlug;
        });

        return $feature && $feature->is_enabled;
    }

    /**
     * Get a numeric limit for a specific feature.
     */
    public function getFeatureLimit(User $user, string $featureSlug): ?int
    {
        $subscription = $this->getActiveSubscription($user);
        if (!$subscription || !$subscription->planVersion) return 0;

        $feature = $subscription->planVersion->features->first(function ($f) use ($featureSlug) {
            return $f->feature && $f->feature->slug === $featureSlug;
        });

        if (!$feature || !$feature->is_enabled) return 0;
        
        return $feature->value !== null ? (int) $feature->value : null; // null means unlimited
    }

    /**
     * Get a specific rate from features.
     */
    public function getRate(User $user, string $featureSlug)
    {
        $subscription = $this->getActiveSubscription($user);
        if (!$subscription || !$subscription->planVersion) return null;

        $feature = $subscription->planVersion->features->first(function ($f) use ($featureSlug) {
            return $f->feature && $f->feature->slug === $featureSlug;
        });

        if (!$feature || !$feature->is_enabled) return null;
        
        return $feature->value;
    }

    /**
     * Check if user has reached the usage limit for a specific metric.
     */
    public function hasReachedLimit(User $user, string $metricType, string $featureSlug): bool
    {
        $limit = $this->getFeatureLimit($user, $featureSlug);
        if ($limit === null) return false; // null means unlimited
        if ($limit === 0) return true; // 0 means blocked

        $usage = $this->getCurrentUsage($user, $metricType);
        
        return $usage >= $limit;
    }

    /**
     * Get current usage for a metric in the current cycle.
     */
    public function getCurrentUsage(User $user, string $metricType): int
    {
        $subscription = $this->getActiveSubscription($user);
        if (!$subscription) {
            return 0;
        }

        $start = $subscription->current_period_start;
        $end = $subscription->current_period_end;

        $metric = UsageMetric::where('user_id', $user->id)
            ->where('metric_type', $metricType)
            ->where('cycle_start', '>=', $start)
            ->where('cycle_end', '<=', $end)
            ->first();

        return $metric ? $metric->used : 0;
    }

    /**
     * Increment usage metric.
     */
    public function incrementUsage(User $user, string $metricType, int $amount = 1): void
    {
        $subscription = $this->getActiveSubscription($user);
        if (!$subscription) return;
        
        $start = $subscription->current_period_start;
        $end = $subscription->current_period_end;

        $metric = UsageMetric::firstOrCreate(
            [
                'user_id' => $user->id,
                'metric_type' => $metricType,
                'cycle_start' => $start,
                'cycle_end' => $end,
            ],
            ['subscription_id' => $subscription->id, 'used' => 0]
        );

        $metric->increment('used', $amount);
    }
}
