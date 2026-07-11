<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Currency;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // Add a role or system flag to users table so we can identify system users
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'is_system_account')) {
                $table->boolean('is_system_account')->default(false);
            }
        });

        // We can't safely use Eloquent models in migrations for complex logic, 
        // but we can use DB builder for simple inserts.
        $systemUserEmail = 'system@ledger.internal';
        
        $systemUserId = DB::table('users')->insertGetId([
            'first_name' => 'System',
            'last_name' => 'Ledger',
            'username' => 'system_ledger',
            'email' => $systemUserEmail,
            'password' => bcrypt(Str::random(32)),
            'is_system_account' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Get the default currency
        $defaultCurrencyId = DB::table('currencies')->where('default', 1)->value('id');
        
        if (!$defaultCurrencyId) {
            // Fallback
            $defaultCurrencyId = DB::table('currencies')->value('id');
        }

        if ($defaultCurrencyId) {
            $wallets = [
                'EXTERNAL_BANKING_NETWORK',
                'EXTERNAL_GATEWAY_NETWORK',
                'GATEWAY_STRIPE_HOLDING',
                'GATEWAY_PAYPAL_HOLDING',
                'GATEWAY_PIX_HOLDING',
                'GATEWAY_PIX_PAYOUT_HOLDING',
                'SYSTEM_REVENUE',
                'SYSTEM_REVENUE_FX',
                'SYSTEM_REFUND',
                'SYSTEM_CHARGEBACK',
                'SYSTEM_ADJUSTMENT',
                'SYSTEM_ADJUSTMENT_LEGACY',
                'SYSTEM_VOUCHER',
                'SYSTEM_CASHBACK',
                // We keep SYSTEM-GENERAL for backward compatibility until everything is migrated
                'SYSTEM-GENERAL' 
            ];

            foreach ($wallets as $uuidStr) {
                DB::table('wallets')->insert([
                    'currency_id' => $defaultCurrencyId,
                    'user_id' => $systemUserId,
                    'uuid' => $uuidStr, // Unique, human readable UUIDs for system wallets
                    'balance' => 0.0,
                    'status' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        $systemUserId = DB::table('users')->where('email', 'system@ledger.internal')->value('id');
        
        if ($systemUserId) {
            DB::table('wallets')->where('user_id', $systemUserId)->delete();
            DB::table('users')->where('id', $systemUserId)->delete();
        }

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'is_system_account')) {
                $table->dropColumn('is_system_account');
            }
        });
    }
};
