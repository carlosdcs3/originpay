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
        if (!Schema::hasTable('processed_events')) {
            Schema::create('processed_events', function (Blueprint $table) {
                $table->id();
                $table->string('idempotency_key', 255)->unique();
                $table->string('event_type');
                $table->string('source')->nullable(); // Ex: efi, stripe, system
                $table->string('source_id')->nullable(); // ID original do PSP
                $table->string('status')->default('processed'); // processed, failed, ignored
                $table->string('payload_hash')->nullable();
                $table->timestamp('processed_at')->useCurrent();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('processed_events');
    }
};
