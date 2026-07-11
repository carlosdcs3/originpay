<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('gateway_id')->nullable()->constrained('payment_gateways')->nullOnDelete();
            $table->string('operation')->nullable()->comment('Ex: PIX_CHARGE, PIX_WITHDRAW, LEGACY_MIGRATION');
            $table->string('provider_reference')->nullable()->comment('Reference inside the gateway (e.g. Efi TxId)');
            $table->unsignedBigInteger('charge_id')->nullable();
            $table->unsignedBigInteger('withdraw_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['gateway_id']);
            $table->dropColumn([
                'gateway_id',
                'operation',
                'provider_reference',
                'charge_id',
                'withdraw_id',
            ]);
        });
    }
};
