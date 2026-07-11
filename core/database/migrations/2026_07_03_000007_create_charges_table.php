<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('api_charges', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->nullable();
            $table->uuid('correlation_id')->nullable();
            $table->string('idempotency_key')->nullable()->index();
            $table->string('charge_number')->unique()->nullable();
            $table->string('charge_id')->unique()->nullable();
            $table->unsignedBigInteger('merchant_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('wallet_id')->nullable();
            $table->unsignedBigInteger('currency_id')->nullable();
            $table->unsignedBigInteger('session_id')->nullable();
            $table->unsignedBigInteger('payment_method_id')->nullable();
            $table->string('gateway_id')->nullable();
            $table->string('gateway_charge_id')->nullable();
            $table->string('gateway_reference')->nullable();

            $table->string('payment_method')->nullable();
            $table->decimal('amount', 28, 8);
            $table->string('currency')->default('BRL');
            $table->decimal('platform_fee', 28, 8)->default(0);
            $table->decimal('gateway_fee', 28, 8)->default(0);
            $table->unsignedBigInteger('fee_rule_id')->nullable();
            $table->json('fee_snapshot')->nullable();
            $table->decimal('net_amount', 28, 8)->default(0);

            $table->string('description')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_document')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_link')->nullable();
            $table->string('boleto_url')->nullable();
            $table->string('boleto_pdf_url')->nullable();
            $table->string('barcode')->nullable();
            $table->string('digitable_line')->nullable();
            $table->text('qr_code')->nullable();
            $table->text('pix_copy_paste')->nullable();

            $table->string('status')->default('pending')->index();
            $table->string('failure_code')->nullable();
            $table->string('failure_message')->nullable();
            $table->json('merchant_metadata')->nullable();
            $table->json('internal_metadata')->nullable();
            $table->json('metadata')->nullable();
            $table->string('environment')->default('sandbox')->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['merchant_id', 'charge_id']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('api_charges');
    }
};
