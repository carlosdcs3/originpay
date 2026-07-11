<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('merchant_gateways', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained('merchants')->onDelete('cascade');
            $table->string('gateway_name');
            $table->string('environment'); // sandbox, production
            $table->integer('priority')->default(0);
            $table->boolean('enabled')->default(true);
            $table->json('configuration')->nullable();
            
            $table->timestamps();
            
            $table->unique(['merchant_id', 'gateway_name', 'environment'], 'merchant_gateway_env_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('merchant_gateways');
    }
};
