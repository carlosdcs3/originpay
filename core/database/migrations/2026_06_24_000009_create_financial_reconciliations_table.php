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
        Schema::create('financial_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->index();
            $table->decimal('expected_balance', 28, 8)->default(0);
            $table->decimal('actual_balance', 28, 8)->default(0);
            $table->decimal('difference', 28, 8)->default(0);
            $table->string('status')->comment('GREEN, LOW, MEDIUM, HIGH, CRITICAL');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_reconciliations');
    }
};
