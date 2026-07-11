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
        Schema::table('charges', function (Blueprint $table) {
            $table->string('correlation_id')->nullable()->after('uuid');
            $table->string('idempotency_key')->nullable()->unique()->after('correlation_id');
        });

        Schema::table('payment_gateways', function (Blueprint $table) {
            $table->boolean('is_maintenance')->default(false)->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('charges', function (Blueprint $table) {
            $table->dropColumn(['correlation_id', 'idempotency_key']);
        });

        Schema::table('payment_gateways', function (Blueprint $table) {
            $table->dropColumn('is_maintenance');
        });
    }
};
