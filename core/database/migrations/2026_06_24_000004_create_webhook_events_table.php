<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider');
            $table->string('event_id')->nullable();
            $table->string('external_reference')->nullable();
            $table->string('event_type')->nullable();
            $table->longText('payload');
            $table->longText('headers')->nullable();
            $table->string('status')->default('RECEIVED'); // RECEIVED, PROCESSING, PROCESSED, FAILED
            $table->integer('attempts')->default(0);
            $table->timestamp('processed_at')->nullable();
            $table->text('last_error')->nullable();
            $table->json('metadata')->nullable(); // Useful for original_dlq_id, etc.
            $table->timestamps();

            // Compound index for idempotency
            $table->unique(['provider', 'event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
