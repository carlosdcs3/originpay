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
        Schema::create('payment_link_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_link_id')->constrained('payment_links')->cascadeOnDelete();
            $table->string('session_id')->nullable();
            $table->string('visitor_hash', 64)->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('referer')->nullable();
            
            // UTMs
            $table->string('utm_source')->nullable()->index();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_content')->nullable();
            $table->string('utm_term')->nullable();
            
            // Device/Browser/OS
            $table->string('device')->nullable();
            $table->string('browser')->nullable();
            $table->string('platform')->nullable();
            
            // GeoIP (nullable for V1)
            $table->string('country')->nullable();
            $table->string('state')->nullable();
            $table->string('city')->nullable();
            
            $table->boolean('is_bot')->default(false);
            $table->timestamp('converted_at')->nullable()->index();
            
            $table->timestamps();
            
            // Indexes for fast analytics queries
            $table->index(['payment_link_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_link_visits');
    }
};
