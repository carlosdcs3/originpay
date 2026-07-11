<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('connect_warmup_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('merchant_id');
            $table->unsignedBigInteger('domain_id')->nullable();
            $table->unsignedBigInteger('whatsapp_instance_id')->nullable();
            $table->string('channel');
            $table->integer('current_volume')->default(0);
            $table->integer('target_volume')->default(0);
            $table->integer('step_day')->default(1);
            $table->index('merchant_id');
            $table->timestamps();
            
        });
    }

    public function down()
    {
        Schema::dropIfExists('connect_warmup_schedules');
    }
};
