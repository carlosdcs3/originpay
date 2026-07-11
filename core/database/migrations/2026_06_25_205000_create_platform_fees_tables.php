<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('platform_fee_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('small_transaction_limit', 28, 8)->default(10.00);
            $table->decimal('small_transaction_fixed_fee', 28, 8)->default(0.35);
            $table->decimal('standard_percentage_fee', 5, 2)->default(2.00);
            $table->decimal('standard_fixed_fee', 28, 8)->default(0.30);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('platform_fee_audits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values');
            $table->string('reason')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('platform_fee_audits');
        Schema::dropIfExists('platform_fee_settings');
    }
};
