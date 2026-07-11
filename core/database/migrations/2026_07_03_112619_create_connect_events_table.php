<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('connect_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('merchant_id');
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->string('event_type');
            $table->unsignedBigInteger('message_log_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->index(['merchant_id', 'event_type', 'occurred_at']);
            $table->timestamps();
            
        });
    }

    public function down()
    {
        Schema::dropIfExists('connect_events');
    }
};
