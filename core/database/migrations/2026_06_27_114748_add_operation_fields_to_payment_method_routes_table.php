<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds the enterprise routing fields to `payment_method_routes` and
     * migrates existing `payment_method` values to the new `payment_operation`
     * column without breaking any existing configuration.
     */
    public function up(): void
    {
        Schema::table('payment_method_routes', function (Blueprint $table) {
            // The new primary routing key. More granular than payment_method.
            // e.g. pix_charge, pix_withdraw, card_credit, card_debit, etc.
            $table->string('payment_operation')->nullable()->after('payment_method');

            // Routing strategy. Only 'manual' is implemented; others are future-ready.
            $table->string('routing_strategy')->default('manual')->after('fallback_gateway_ids');

            // Weight map for the 'weighted' strategy. e.g. {"1": 70, "2": 30}
            // Not editable via UI yet — reserved for a future release.
            $table->json('gateway_weights')->nullable()->after('routing_strategy');
        });

        // ─── Data Migration ───────────────────────────────────────────────────
        // Populate `payment_operation` from existing `payment_method` values.
        // This ensures zero downtime and backward compatibility.
        $map = [
            'pix'    => 'pix_charge',
            'boleto' => 'boleto',
            'card'   => 'card_credit',
            'crypto' => 'crypto',
        ];

        foreach ($map as $oldMethod => $newOperation) {
            DB::table('payment_method_routes')
                ->where('payment_method', $oldMethod)
                ->update(['payment_operation' => $newOperation]);
        }

        // Add the unique index on payment_operation for new queries.
        // payment_method retains its existing unique index for legacy compatibility.
        Schema::table('payment_method_routes', function (Blueprint $table) {
            $table->unique('payment_operation', 'pmr_payment_operation_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_method_routes', function (Blueprint $table) {
            $table->dropUnique('pmr_payment_operation_unique');
            $table->dropColumn(['payment_operation', 'routing_strategy', 'gateway_weights']);
        });
    }
};
