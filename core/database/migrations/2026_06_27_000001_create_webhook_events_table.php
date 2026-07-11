<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('webhook_events')) {
            return;
        }

        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('gateway');
            $table->string('provider_reference')->nullable();
            $table->string('event_id')->nullable();
            $table->string('event_type')->nullable();
            $table->string('status')->default('received'); 
            $table->string('payload_hash');
            $table->string('payload_version')->default('v1');
            $table->json('raw_payload')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('attempts')->default(0);
            $table->uuid('correlation_id')->nullable();
            $table->timestamps();
            
            // Unique index para idempotencia absoluta
            $table->unique(['gateway', 'event_id', 'provider_reference', 'payload_hash'], 'webhook_idempotency_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('webhook_events');
    }
};
