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
        Schema::table('payment_gateways', function (Blueprint $table) {
            if (!Schema::hasColumn('payment_gateways', 'provider')) {
                $table->string('provider')->nullable()->default('custom')->after('id');
            }
            if (!Schema::hasColumn('payment_gateways', 'adapter')) {
                $table->string('adapter')->nullable()->default('CustomGatewayAdapter')->after('provider');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_gateways', function (Blueprint $table) {
            if (Schema::hasColumn('payment_gateways', 'provider')) {
                $table->dropColumn('provider');
            }
            if (Schema::hasColumn('payment_gateways', 'adapter')) {
                $table->dropColumn('adapter');
            }
        });
    }
};
