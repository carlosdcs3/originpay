<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webhook_endpoint_id')->constrained()->onDelete('cascade');
            $table->string('event_type');
            $table->string('idempotency_key')->nullable();
            $table->json('payload');
            $table->integer('status_code')->nullable();
            $table->text('response_body')->nullable();
            $table->integer('attempt')->default(1);
            $table->boolean('successful')->default(false);
            $table->timestamps();

            $table->unique(['webhook_endpoint_id', 'idempotency_key'], 'webhook_delivery_endpoint_idem_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('webhook_deliveries');
    }
};
