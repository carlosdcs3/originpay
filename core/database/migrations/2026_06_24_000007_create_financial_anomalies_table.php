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
        Schema::create('financial_anomalies', function (Blueprint $table) {
            $table->id();
            $table->string('type', 100)->index();
            $table->string('severity', 20)->index(); // CRITICAL, HIGH, MEDIUM, LOW, INFO
            $table->string('entity_type', 100)->nullable();
            $table->string('entity_id', 100)->nullable();
            $table->string('fingerprint', 255)->unique(); // ledger_mismatch:user_123
            $table->text('description');
            $table->json('metadata')->nullable(); // Including suggested_action array
            $table->json('suggested_actions')->nullable(); // structured array of strings
            
            // Resolution
            $table->timestamp('detected_at');
            $table->timestamp('resolved_at')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->text('resolution_notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_anomalies');
    }
};
