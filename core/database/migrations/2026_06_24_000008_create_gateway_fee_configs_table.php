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
        Schema::create('gateway_fee_configs', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->unique()->comment('e.g., EFI, NEW_PROVIDER');
            
            // Transaction (Deposit / Receive)
            $table->enum('transaction_fee_type', ['fixed', 'percent', 'fixed_plus_percent'])->default('fixed');
            $table->decimal('transaction_fixed_fee', 28, 8)->default(0);
            $table->decimal('transaction_percent_fee', 5, 2)->default(0);

            // Withdraw (Payout)
            $table->enum('withdraw_fee_type', ['fixed', 'percent', 'fixed_plus_percent'])->default('fixed');
            $table->decimal('withdraw_fixed_fee', 28, 8)->default(0);
            $table->decimal('withdraw_percent_fee', 5, 2)->default(0);

            // Refund
            $table->enum('refund_fee_type', ['fixed', 'percent', 'fixed_plus_percent'])->default('fixed');
            $table->decimal('refund_fixed_fee', 28, 8)->default(0);
            $table->decimal('refund_percent_fee', 5, 2)->default(0);

            // Provider specific fees (Gateway's own cut)
            $table->enum('provider_fee_mode', ['estimated', 'real', 'manual'])->default('estimated');
            $table->decimal('provider_fixed_fee', 28, 8)->default(0);
            $table->decimal('provider_percent_fee', 5, 2)->default(0);

            $table->string('currency')->default('BRL');
            $table->boolean('is_active')->default(true);
            $table->integer('updated_by')->nullable(); // Admin ID
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gateway_fee_configs');
    }
};
