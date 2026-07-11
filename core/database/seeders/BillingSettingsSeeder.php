<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BillingSetting;

class BillingSettingsSeeder extends Seeder
{
    public function run()
    {
        BillingSetting::firstOrCreate(
            ['id' => 1],
            [
                'fallback_plan_id' => 1, // Will map to Free plan ID
                'grace_period_days' => 3,
                'downgrade_behavior' => 'next_cycle',
                'cancel_behavior' => 'end_of_cycle',
                'trial_behavior' => 'fallback',
            ]
        );
    }
}
