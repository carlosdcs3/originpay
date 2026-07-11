<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        // $this->call(PermissionTableSeeder::class);
        // $this->call(WithdrawScheduleSeeder::class);
        // $this->call(VirtualCardProviderSeeder::class);
        // $this->call(PaymentGatewaySeeder::class);
        // $this->call(NotificationTemplateSeeder::class);

        $this->call([
            CurrencySeeder::class,
            PlatformFeeSettingSeeder::class,
            PlatformFeeRuleSeeder::class,
            CommercialEngineSeeder::class,
        ]);
    }
}
