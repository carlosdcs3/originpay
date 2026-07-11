<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_document')->nullable();
            $table->string('status', 20)->default('pending');
            $table->decimal('amount', 18, 2);
            $table->string('currency', 3)->default('BRL');
            $table->string('payment_method', 30);
            $table->string('interval', 20);
            $table->unsignedInteger('interval_count')->default(1);
            $table->string('description')->nullable();
            $table->timestamp('start_at');
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->timestamp('next_billing_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->boolean('cancel_at_period_end')->default(false);
            $table->json('metadata')->nullable();
            $table->text('last_error')->nullable();
            $table->string('idempotency_key')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'idempotency_key'], 'cs_user_idempotency_unique');
            $table->index(['user_id', 'status'], 'cs_user_status_idx');
            $table->index(['status', 'next_billing_at'], 'cs_status_next_billing_idx');
        });

        Schema::create('customer_subscription_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_subscription_id')->constrained('customer_subscriptions')->cascadeOnDelete();
            $table->string('description')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_amount', 18, 2);
            $table->decimal('total_amount', 18, 2);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('subscription_invoices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('customer_subscription_id')->constrained('customer_subscriptions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('charge_id')->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamp('period_start');
            $table->timestamp('period_end');
            $table->decimal('amount_due', 18, 2);
            $table->decimal('amount_paid', 18, 2)->default(0);
            $table->string('currency', 3)->default('BRL');
            $table->timestamp('due_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->text('last_error')->nullable();
            $table->string('idempotency_key')->nullable();
            $table->timestamps();

            $table->index(['customer_subscription_id', 'status'], 'si_subscription_status_idx');
            $table->index(['user_id', 'status'], 'si_user_status_idx');
            $table->index('charge_id', 'si_charge_idx');
            $table->unique(['customer_subscription_id', 'idempotency_key'], 'si_subscription_idempotency_unique');
        });

        Schema::create('subscription_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_invoice_id')->constrained('subscription_invoices')->cascadeOnDelete();
            $table->foreignId('customer_subscription_item_id')->nullable()->constrained('customer_subscription_items')->nullOnDelete();
            $table->string('description')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_amount', 18, 2);
            $table->decimal('total_amount', 18, 2);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_invoice_items');
        Schema::dropIfExists('subscription_invoices');
        Schema::dropIfExists('customer_subscription_items');
        Schema::dropIfExists('customer_subscriptions');
    }
};
