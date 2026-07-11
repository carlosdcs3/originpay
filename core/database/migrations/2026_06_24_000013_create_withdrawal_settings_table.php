<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withdrawal_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('withdraw_enabled')->default(true);
            $table->boolean('auto_approve_enabled')->default(false);
            $table->decimal('minimum_amount', 18, 2)->default(10.00);
            $table->decimal('maximum_amount', 18, 2)->default(10000.00);
            $table->decimal('daily_amount_limit', 18, 2)->default(50000.00);
            $table->integer('daily_count_limit')->default(5);
            $table->timestamps();
        });

        // Insert default row
        \Illuminate\Support\Facades\DB::table('withdrawal_settings')->insert([
            'withdraw_enabled' => true,
            'auto_approve_enabled' => false,
            'minimum_amount' => 10.00,
            'maximum_amount' => 10000.00,
            'daily_amount_limit' => 50000.00,
            'daily_count_limit' => 5,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawal_settings');
    }
};
