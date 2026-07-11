<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('idempotency_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('api_key_id')->nullable()->constrained('api_keys')->onDelete('set null');
            $table->string('idempotency_key');
            $table->string('method');
            $table->string('endpoint');
            $table->string('request_hash');
            $table->json('response_body')->nullable();
            $table->integer('response_status')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            // Compound unique index
            $table->unique(['user_id', 'idempotency_key']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('idempotency_keys');
    }
};
