<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Settlement;
use App\Models\Chargeback;
use App\Models\FeeRecord;
use App\Models\User;
use App\Models\PaymentGateway;

class FinanceModelsSeeder extends Seeder
{
    public function run()
    {
        $user = User::first();
        $gateway = PaymentGateway::first();
        
        if (!$user || !$gateway) {
            return;
        }

        Settlement::create([
            'user_id' => $user->id,
            'gateway_id' => $gateway->id,
            'destination' => 'Bank Account 1234',
            'gross_amount' => 1050.00,
            'fees' => 50.00,
            'net_amount' => 1000.00,
            'status' => 'pending',
            'scheduled_date' => now()->addDays(2)
        ]);

        Chargeback::create([
            'user_id' => $user->id,
            'gateway_id' => $gateway->id,
            'provider_reference' => 'CHG_987654321',
            'amount' => 150.00,
            'reason' => 'Fraudulent Transaction',
            'status' => 'open',
            'deadline' => now()->addDays(15)
        ]);

        FeeRecord::create([
            'user_id' => $user->id,
            'gateway_id' => $gateway->id,
            'operation_type' => 'charge',
            'reference_id' => 1,
            'gross_amount' => 200.00,
            'gateway_cost' => 1.50,
            'merchant_fee' => 5.00,
            'net_amount' => 195.00,
            'margin' => 3.50,
            'status' => 'expected'
        ]);
    }
}
