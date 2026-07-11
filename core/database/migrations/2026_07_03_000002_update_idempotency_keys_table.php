<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::dropIfExists('idempotency_keys');

        Schema::create('idempotency_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained('merchants')->onDelete('cascade');
            $table->string('idempotency_key');
            $table->string('request_method');
            $table->string('request_path');
            $table->string('request_hash');
            $table->integer('response_status')->nullable();
            $table->json('response_body')->nullable(); // Optional: if empty, omit it.
            $table->timestamp('locked_until')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['merchant_id', 'idempotency_key']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('idempotency_keys');
    }
};
