<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('connect_message_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('merchant_id');
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->unsignedBigInteger('campaign_id')->nullable();
            $table->unsignedBigInteger('automation_id')->nullable();
            $table->string('channel');
            $table->string('direction')->default('outbound');
            $table->string('status');
            $table->string('provider_message_id')->nullable();
            $table->text('error_reason')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->index('merchant_id');
            $table->index('channel');
            $table->index('status');
            $table->index('created_at');
            $table->timestamps();
            
        });
    }

    public function down()
    {
        Schema::dropIfExists('connect_message_logs');
    }
};
