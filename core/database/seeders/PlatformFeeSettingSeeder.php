<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PlatformFeeSetting;

class PlatformFeeSettingSeeder extends Seeder
{
    public function run()
    {
        PlatformFeeSetting::create([
            'small_transaction_limit' => 10.00,
            'small_transaction_fixed_fee' => 0.35,
            'standard_percentage_fee' => 2.00,
            'standard_fixed_fee' => 0.30,
            'is_active' => true,
        ]);
    }
}
