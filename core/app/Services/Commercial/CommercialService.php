<?php

namespace App\Services\Commercial;

use App\Models\Product;
use App\Models\CommercialPlan;
use App\Models\PlanVersion;
use Illuminate\Support\Facades\Cache;

class CommercialService
{
    /**
     * Get all active products with their current plans and prices
     * Ideal for Landing Page and User Dashboard
     */
    public function getActiveCatalog()
    {
        return Cache::remember('commercial_catalog', 3600, function () {
            try {
                return Product::with(['versions' => function ($q) {
                        $q->where('is_active', true)->orderBy('version_number', 'desc')->take(1);
                    }])
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->get()
                    ->map(function ($product) {
                        $currentVersion = $product->versions->first();
                        if (!$currentVersion) return null;

                        // Fetch active plans for this product version
                        $plans = PlanVersion::with(['plan', 'prices', 'features.feature'])
                            ->where('product_version_id', $currentVersion->id)
                            ->where('is_active', true)
                            ->get()
                            ->filter(function ($pv) {
                                return $pv->plan->is_active;
                            })
                            ->sortBy(function ($pv) {
                                return $pv->plan->sort_order;
                            })
                            ->map(function ($pv) {
                                $pv->prices = $pv->prices->sortBy('price');
                                return $pv;
                            });

                        $product->active_plans = $plans;
                        $product->product_version = $currentVersion;
                        return $product;
                    })
                    ->filter()
                    ->values();
            } catch (\Exception $e) {
                return collect([]);
            }
        });
    }

    public function clearCache()
    {
        Cache::forget('commercial_catalog');
    }
}
