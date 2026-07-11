<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('connect_analytics_rollups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('merchant_id');
            $table->string('period');
            $table->string('metric_name');
            $table->string('channel');
            $table->decimal('value', 15, 2)->default(0);
            $table->json('dimensions')->nullable();
            $table->unique(['merchant_id', 'period', 'metric_name', 'channel'], 'analytics_unique');
            $table->timestamps();
            
        });
    }

    public function down()
    {
        Schema::dropIfExists('connect_analytics_rollups');
    }
};
