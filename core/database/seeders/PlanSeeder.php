<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;
use App\Models\PlanRate;
use App\Models\PlanQuota;
use App\Models\FeatureCatalog;

class PlanSeeder extends Seeder
{
    public function run()
    {
        // Free Plan
        $free = Plan::firstOrCreate(
            ['slug' => 'free'],
            [
                'name' => 'Free',
                'description' => 'Ideal para testar a integração.',
                'monthly_price' => 0,
                'annual_price' => 0,
                'is_active' => true,
                'sort_order' => 1,
            ]
        );

        PlanRate::firstOrCreate(['plan_id' => $free->id], [
            'pix_fee_percent' => 1.99,
            'pix_fee_fixed' => 0,
            'cc_fee_percent' => 4.99,
            'cc_fee_fixed' => 50,
            'withdraw_fee_fixed' => 300,
            'min_withdraw_amount' => 5000,
            'settlement_days' => 14,
        ]);

        PlanQuota::firstOrCreate(['plan_id' => $free->id], [
            'api_requests_per_month' => 1000,
            'webhooks_per_month' => 500,
            'logs_retention_days' => 7,
            'api_rate_limit_rpm' => 30,
        ]);

        // Add features to free
        $basicFeatures = FeatureCatalog::whereIn('slug', ['api_rest', 'sandbox', 'pix_processing'])->get();
        foreach ($basicFeatures as $feat) {
            $free->features()->firstOrCreate([
                'feature_catalog_id' => $feat->id
            ], [
                'is_active' => true
            ]);
        }


        // Starter Plan
        $starter = Plan::firstOrCreate(
            ['slug' => 'starter'],
            [
                'name' => 'Starter',
                'description' => 'Para negócios em crescimento.',
                'monthly_price' => 9900,
                'annual_price' => 99000,
                'is_active' => true,
                'is_recommended' => true,
                'sort_order' => 2,
            ]
        );

        PlanRate::firstOrCreate(['plan_id' => $starter->id], [
            'pix_fee_percent' => 1.49,
            'pix_fee_fixed' => 0,
            'cc_fee_percent' => 3.99,
            'cc_fee_fixed' => 30,
            'withdraw_fee_fixed' => 150,
            'min_withdraw_amount' => 1000,
            'settlement_days' => 2,
        ]);

        PlanQuota::firstOrCreate(['plan_id' => $starter->id], [
            'api_requests_per_month' => 50000,
            'webhooks_per_month' => 10000,
            'logs_retention_days' => 30,
            'api_rate_limit_rpm' => 120,
        ]);

        $starterFeatures = FeatureCatalog::whereIn('slug', ['api_rest', 'sandbox', 'production', 'pix_processing', 'credit_card_processing', 'webhooks', 'refunds'])->get();
        foreach ($starterFeatures as $feat) {
            $starter->features()->firstOrCreate(['feature_catalog_id' => $feat->id], ['is_active' => true]);
        }


        // Enterprise Plan
        $enterprise = Plan::firstOrCreate(
            ['slug' => 'enterprise'],
            [
                'name' => 'Enterprise',
                'description' => 'Volumes massivos e taxas customizadas.',
                'monthly_price' => 49900,
                'annual_price' => 499000,
                'is_active' => true,
                'is_enterprise' => true,
                'sort_order' => 3,
            ]
        );

        PlanRate::firstOrCreate(['plan_id' => $enterprise->id], [
            'pix_fee_percent' => 0.89,
            'pix_fee_fixed' => 0,
            'cc_fee_percent' => 2.99,
            'cc_fee_fixed' => 0,
            'withdraw_fee_fixed' => 0,
            'min_withdraw_amount' => 0,
            'settlement_days' => 1,
        ]);

        PlanQuota::firstOrCreate(['plan_id' => $enterprise->id], [
            'api_requests_per_month' => null, // Unlimited
            'webhooks_per_month' => null,
            'logs_retention_days' => 365,
            'api_rate_limit_rpm' => 1000,
        ]);

        $allFeatures = FeatureCatalog::all();
        foreach ($allFeatures as $feat) {
            $enterprise->features()->firstOrCreate(['feature_catalog_id' => $feat->id], ['is_active' => true]);
        }
    }
}
