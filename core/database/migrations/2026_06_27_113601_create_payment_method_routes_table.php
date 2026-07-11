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
        Schema::create('payment_method_routes', function (Blueprint $table) {
            $table->id();
            $table->string('payment_method')->unique(); // pix, boleto, card, crypto
            $table->unsignedBigInteger('primary_gateway_id')->nullable();
            $table->json('fallback_gateway_ids')->nullable(); // [2, 4, 1]
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->foreign('primary_gateway_id')->references('id')->on('payment_gateways')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_method_routes');
    }
};
