<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('api_gateway_logs', function (Blueprint $table) {
            $table->id();
            $table->string('request_id')->index();
            $table->foreignId('merchant_id')->constrained('merchants')->onDelete('cascade');
            $table->string('charge_id')->index();
            $table->string('gateway');
            $table->string('operation'); // authorize, capture, etc.
            $table->integer('duration_ms')->nullable();
            $table->string('status'); // success, error
            $table->string('response_code')->nullable();
            $table->text('error')->nullable();
            
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down()
    {
        Schema::dropIfExists('api_gateway_logs');
    }
};
