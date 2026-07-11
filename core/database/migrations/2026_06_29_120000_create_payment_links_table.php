<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_links', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('slug', 64)->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 30);
            $table->unsignedBigInteger('charge_id')->nullable();
            $table->foreignId('customer_subscription_id')->nullable()->constrained('customer_subscriptions')->nullOnDelete();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->decimal('amount', 18, 2);
            $table->string('currency', 3)->default('BRL');
            $table->string('payment_method', 30);
            $table->json('allowed_payment_methods')->nullable();
            $table->string('title');
            $table->string('description')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type', 'status'], 'payment_links_user_type_status_idx');
            $table->index(['user_id', 'status', 'expires_at'], 'payment_links_user_status_exp_idx');
            $table->index(['status', 'expires_at'], 'payment_links_status_exp_idx');
            $table->index(['charge_id', 'status'], 'payment_links_charge_status_idx');
            $table->index(['customer_subscription_id', 'status'], 'payment_links_subscription_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_links');
    }
};
