<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('charges', function (Blueprint $table) {
            // Add wallet_id and currency_id
            $table->foreignId('wallet_id')->nullable()->after('user_id')->constrained('wallets')->onDelete('restrict');
            $table->foreignId('currency_id')->nullable()->after('wallet_id')->constrained('currencies')->onDelete('restrict');

            // Add indexes for performance
            $table->index('gateway_charge_id');
            $table->index('user_id');
            $table->index('status');
            $table->index('payment_method');
            $table->index('created_at');
            $table->index('gateway_id');
        });
    }

    public function down(): void
    {
        Schema::table('charges', function (Blueprint $table) {
            $table->dropForeign(['wallet_id']);
            $table->dropForeign(['currency_id']);
            $table->dropColumn(['wallet_id', 'currency_id']);

            $table->dropIndex(['gateway_charge_id']);
            $table->dropIndex(['user_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['payment_method']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['gateway_id']);
        });
    }
};
