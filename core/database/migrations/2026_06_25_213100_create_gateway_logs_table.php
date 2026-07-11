<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('gateway_logs', function (Blueprint $table) {
            $table->id();
            $table->string('gateway_code'); // efi, asaas, mock
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->integer('http_status')->nullable();
            $table->integer('execution_time_ms')->nullable();
            $table->string('correlation_id')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('gateway_logs');
    }
};
