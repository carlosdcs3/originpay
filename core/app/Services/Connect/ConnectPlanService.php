<?php
namespace App\Services\Connect;

class ConnectPlanService
{
    public function getStarterPlan()
    {
        return [
            'name' => 'Origin Connect',
            'price' => 39.90,
            'limits' => [
                'max_domains' => 1,
                'max_whatsapp_instances' => 1,
                'monthly_email_limit' => 10000,
                'max_active_automations' => 10,
                'max_active_campaigns' => 20,
                'max_contacts' => 10000,
                'max_templates' => 50,
            ]
        ];
    }
}
