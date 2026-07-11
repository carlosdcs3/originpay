<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('connect_whatsapp_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained('users')->cascadeOnDelete();
            $table->string('phone_number');
            $table->string('provider_id')->nullable();
            $table->string('status')->default('disconnected');
            $table->unique(['merchant_id', 'phone_number']);
            $table->index('status');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('connect_whatsapp_instances');
    }
};
