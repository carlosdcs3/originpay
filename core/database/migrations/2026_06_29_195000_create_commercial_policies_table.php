<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('commercial_policies', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('admin_id')->nullable(); // nullable just in case of system migration
            $table->json('payload'); // Contains PIX, Boleto, Credit Card info
            $table->json('changes')->nullable(); // Diff before/after
            $table->string('reason');
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('applied_at');
            $table->timestamps();
        });

        // Migrate legacy data
        $legacySetting = DB::table('platform_fee_settings')->first();
        if ($legacySetting) {
            // Reconstruct the JSON structure from legacy columns
            // small_transaction_limit, small_transaction_fixed_fee, standard_percentage_fee, standard_fixed_fee
            $payload = [
                'pix' => [
                    'mode' => 'range',
                    'universal_fee' => 0.00,
                    'range_limit' => (float) $legacySetting->small_transaction_limit,
                    'range_fixed_fee' => (float) $legacySetting->small_transaction_fixed_fee,
                    'range_percentage_fee' => (float) $legacySetting->standard_percentage_fee,
                    'range_additional_fixed_fee' => (float) $legacySetting->standard_fixed_fee,
                ],
                'boleto' => [
                    'fixed_fee' => 0.00,
                    'percentage_fee' => 0.00,
                    'min_value' => 0.00
                ],
                'credit_card' => [
                    'percentage_fee' => 0.00,
                    'fixed_fee' => 0.00
                ]
            ];

            DB::table('commercial_policies')->insert([
                'admin_id' => null,
                'payload' => json_encode($payload),
                'changes' => json_encode(['migrated' => true, 'legacy_data' => (array) $legacySetting]),
                'reason' => 'Migração automática da configuração legada',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'System Migration',
                'applied_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('commercial_policies');
    }
};
