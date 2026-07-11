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
        Schema::create('wallet_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained('wallets')->cascadeOnDelete();
            $table->foreignId('gateway_id')->constrained('payment_gateways')->cascadeOnDelete();
            
            // Saldos
            $table->decimal('available', 15, 2)->default(0);
            $table->decimal('pending', 15, 2)->default(0);
            $table->decimal('blocked', 15, 2)->default(0);
            
            // Garantir unicidade de carteira + gateway
            $table->unique(['wallet_id', 'gateway_id']);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_balances');
    }
};
