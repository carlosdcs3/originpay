<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\CommercialPlan;
use App\Models\Price;
use App\Models\CommercialFeature;
use App\Models\PlanVersionFeature;
use Carbon\Carbon;

class CommercialEngineSeeder extends Seeder
{
    public function run()
    {
        // 1. Create Core Product
        $gatewayProduct = Product::create([
            'name' => 'Payment Gateway',
            'slug' => 'payment-gateway',
            'description' => 'Acesso completo às APIs de processamento de pagamentos.',
            'icon' => 'fas fa-money-check-alt',
        ]);

        $gatewayV1 = $gatewayProduct->versions()->create([
            'version_number' => 1,
            'is_active' => true,
        ]);

        // 2. Create Features (Catalog)
        $featuresData = [
            ['slug' => 'pix_processing', 'name' => 'Processamento PIX', 'type' => 'boolean'],
            ['slug' => 'cc_processing', 'name' => 'Processamento Cartão', 'type' => 'boolean'],
            ['slug' => 'split_payments', 'name' => 'Split de Pagamentos', 'type' => 'boolean'],
            ['slug' => 'api_requests', 'name' => 'Requisições API (Mensal)', 'type' => 'limit'],
            ['slug' => 'webhooks', 'name' => 'Webhooks Disparados', 'type' => 'limit'],
            ['slug' => 'pix_fee_percent', 'name' => 'Taxa PIX (%)', 'type' => 'rate'],
            ['slug' => 'cc_fee_percent', 'name' => 'Taxa Cartão (%)', 'type' => 'rate'],
            ['slug' => 'cc_fee_fixed', 'name' => 'Taxa Cartão Fixa (Centavos)', 'type' => 'rate'],
            ['slug' => 'sandbox_access', 'name' => 'Ambiente Sandbox', 'type' => 'boolean'],
            ['slug' => 'priority_support', 'name' => 'Suporte Prioritário', 'type' => 'boolean'],
        ];

        $features = [];
        foreach ($featuresData as $data) {
            $features[$data['slug']] = CommercialFeature::create($data);
        }

        // 3. Create Plans & Plan Versions
        $plansDef = [
            [
                'name' => 'Free', 'slug' => 'free', 'price' => 0, 'recommended' => true,
                'limits' => ['api_requests' => '1000', 'webhooks' => '500'],
                'rates' => ['pix_fee_percent' => '0', 'cc_fee_percent' => '0', 'cc_fee_fixed' => '0'] // We'll use PlatformFeeSetting
            ],
            [
                'name' => 'Enterprise', 'slug' => 'enterprise', 'price' => 99900, 'recommended' => false,
                'limits' => ['api_requests' => null, 'webhooks' => null], // unlimited
                'rates' => ['pix_fee_percent' => '0', 'cc_fee_percent' => '0', 'cc_fee_fixed' => '0'] // Custom/Sob Consulta
            ],
        ];

        foreach ($plansDef as $index => $pd) {
            $plan = CommercialPlan::create([
                'product_id' => $gatewayProduct->id,
                'name' => $pd['name'],
                'slug' => $pd['slug'],
                'sort_order' => $index + 1,
            ]);

            $planVersion = $plan->versions()->create([
                'product_version_id' => $gatewayV1->id,
                'version_number' => 1,
                'is_active' => true,
            ]);

            // Prices
            Price::create([
                'plan_version_id' => $planVersion->id,
                'amount' => $pd['price'],
                'billing_period' => 'monthly',
                'currency' => 'BRL',
            ]);

            if ($pd['price'] > 0) {
                Price::create([
                    'plan_version_id' => $planVersion->id,
                    'amount' => $pd['price'] * 10, // 2 months free
                    'billing_period' => 'annual',
                    'currency' => 'BRL',
                ]);
            }

            // Features assignment
            $featureAssignments = [
                'pix_processing' => ['enabled' => true, 'value' => null],
                'cc_processing' => ['enabled' => true, 'value' => null],
                'sandbox_access' => ['enabled' => true, 'value' => null],
                'split_payments' => ['enabled' => $pd['slug'] !== 'free', 'value' => null],
                'priority_support' => ['enabled' => $pd['slug'] === 'enterprise', 'value' => null],
                'api_requests' => ['enabled' => true, 'value' => $pd['limits']['api_requests']],
                'webhooks' => ['enabled' => true, 'value' => $pd['limits']['webhooks']],
                'pix_fee_percent' => ['enabled' => true, 'value' => $pd['rates']['pix_fee_percent']],
                'cc_fee_percent' => ['enabled' => true, 'value' => $pd['rates']['cc_fee_percent']],
                'cc_fee_fixed' => ['enabled' => true, 'value' => $pd['rates']['cc_fee_fixed']],
            ];

            foreach ($featureAssignments as $fSlug => $config) {
                PlanVersionFeature::create([
                    'plan_version_id' => $planVersion->id,
                    'commercial_feature_id' => $features[$fSlug]->id,
                    'is_enabled' => $config['enabled'],
                    'value' => $config['value'],
                ]);
            }
        }
    }
}
