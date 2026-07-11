<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('api_request_logs', function (Blueprint $table) {
            $table->id();
            $table->string('request_id')->index();
            $table->foreignId('merchant_id')->nullable()->constrained('merchants')->onDelete('cascade');
            $table->foreignId('api_key_id')->nullable()->constrained('api_credentials')->onDelete('set null');
            $table->string('api_version')->nullable();
            $table->string('endpoint');
            $table->string('method');
            $table->integer('status_code');
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('country')->nullable();
            $table->float('duration_ms')->nullable();
            $table->integer('request_size')->nullable();
            $table->integer('response_size')->nullable();
            $table->string('error_type')->nullable();
            $table->string('error_code')->nullable();
            $table->timestamp('created_at')->nullable()->index();
            
            $table->index('merchant_id');
            $table->index('api_key_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('api_request_logs');
    }
};
