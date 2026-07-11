<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('charges', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('gateway_id')->nullable(); // Identifier of the PSP (e.g. efi, mock, dock)
            $table->string('gateway_charge_id')->nullable(); // ID returned by the PSP
            $table->string('payment_method'); // pix, card
            
            $table->decimal('amount', 28, 8);
            $table->decimal('platform_fee', 28, 8)->default(0);
            $table->decimal('gateway_fee', 28, 8)->default(0);
            $table->decimal('net_amount', 28, 8);
            
            $table->string('description')->nullable();
            
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_document')->nullable();
            
            $table->timestamp('expires_at')->nullable();
            
            $table->string('payment_link')->nullable();
            $table->text('qr_code')->nullable();
            $table->text('pix_copy_paste')->nullable();
            
            $table->string('status')->default('pending'); // pending, waiting_payment, paid, expired, cancelled, refunded
            
            $table->timestamps();
        });

        Schema::create('charge_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('charge_id');
            $table->string('gateway_event_id')->nullable(); // Important for idempotency
            $table->string('event');
            $table->json('payload')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            
            // For Idempotency
            $table->unique(['gateway_event_id']);
            $table->index('charge_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('charge_events');
        Schema::dropIfExists('charges');
    }
};
