<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FeatureCatalog;

class FeatureCatalogSeeder extends Seeder
{
    public function run()
    {
        $features = [
            ['name' => 'API REST', 'slug' => 'api_rest', 'type' => 'boolean', 'description' => 'Acesso básico à API'],
            ['name' => 'Sandbox', 'slug' => 'sandbox', 'type' => 'boolean', 'description' => 'Ambiente de testes'],
            ['name' => 'Produção', 'slug' => 'production', 'type' => 'boolean', 'description' => 'Acesso à produção'],
            ['name' => 'PIX', 'slug' => 'pix_processing', 'type' => 'boolean', 'description' => 'Processamento de PIX'],
            ['name' => 'Cartão de Crédito', 'slug' => 'credit_card_processing', 'type' => 'boolean', 'description' => 'Processamento de cartão'],
            ['name' => 'Webhooks', 'slug' => 'webhooks', 'type' => 'boolean', 'description' => 'Acesso a webhooks'],
            ['name' => 'Saques Automáticos', 'slug' => 'auto_payouts', 'type' => 'boolean', 'description' => 'Saques via API'],
            ['name' => 'Reembolsos', 'slug' => 'refunds', 'type' => 'boolean', 'description' => 'Acesso à API de reembolsos'],
            ['name' => 'Suporte Prioritário', 'slug' => 'priority_support', 'type' => 'boolean', 'description' => 'SLA diferenciado no suporte'],
            ['name' => 'Custom Domains', 'slug' => 'custom_domains', 'type' => 'boolean', 'description' => 'Domínios personalizados para faturas'],
            ['name' => 'White Label', 'slug' => 'white_label', 'type' => 'boolean', 'description' => 'Remover marca DigiSynk'],
        ];

        foreach ($features as $f) {
            FeatureCatalog::firstOrCreate(['slug' => $f['slug']], $f);
        }
    }
}
