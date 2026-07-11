<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('settlements')) {
            Schema::create('settlements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('gateway_id')->nullable()->constrained('payment_gateways')->nullOnDelete();
                $table->string('destination');
                $table->decimal('gross_amount', 18, 8);
                $table->decimal('fees', 18, 8)->default(0);
                $table->decimal('net_amount', 18, 8);
                $table->string('status', 30)->default('pending');
                $table->timestamp('scheduled_date')->nullable();
                $table->timestamp('settled_date')->nullable();
                $table->unsignedBigInteger('split_rule_id')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status']);
            });
        }

        if (!Schema::hasTable('chargebacks')) {
            Schema::create('chargebacks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->unsignedBigInteger('charge_id')->nullable();
                $table->foreignId('gateway_id')->nullable()->constrained('payment_gateways')->nullOnDelete();
                $table->string('provider_reference')->nullable();
                $table->decimal('amount', 18, 8);
                $table->string('reason')->nullable();
                $table->string('status', 30)->default('open');
                $table->timestamp('deadline')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status']);
            });
        }

        if (!Schema::hasTable('fee_records')) {
            Schema::create('fee_records', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('gateway_id')->nullable()->constrained('payment_gateways')->nullOnDelete();
                $table->string('operation_type', 30);
                $table->unsignedBigInteger('reference_id')->nullable();
                $table->decimal('gross_amount', 18, 8)->default(0);
                $table->decimal('gateway_cost', 18, 8)->default(0);
                $table->decimal('merchant_fee', 18, 8)->default(0);
                $table->decimal('net_amount', 18, 8)->default(0);
                $table->decimal('margin', 18, 8)->default(0);
                $table->string('status', 30)->default('expected');
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'operation_type']);
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('fee_records');
        Schema::dropIfExists('chargebacks');
        Schema::dropIfExists('settlements');
    }
};
