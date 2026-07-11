<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('connect_campaign_delivery_attempts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('recipient_id')->constrained('connect_campaign_recipients')->cascadeOnDelete();
            $table->foreignId('execution_id')->constrained('connect_campaign_executions')->cascadeOnDelete();
            
            $table->string('provider');
            $table->string('status'); // success, failed
            $table->json('response_payload')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedInteger('latency_ms')->default(0);

            $table->timestamps();
            
            $table->index('recipient_id');
            $table->index('execution_id');
            $table->index('status');
        });

        Schema::create('connect_campaign_dlq', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('recipient_id')->constrained('connect_campaign_recipients')->cascadeOnDelete();
            $table->foreignId('execution_id')->constrained('connect_campaign_executions')->cascadeOnDelete();
            $table->foreignId('merchant_id')->constrained('users')->cascadeOnDelete();
            
            $table->string('channel');
            $table->text('last_error')->nullable();
            $table->longText('payload_snapshot')->nullable();

            $table->timestamps();
            
            $table->index('recipient_id');
            $table->index('merchant_id');
            $table->index('channel');
        });
    }

    public function down()
    {
        Schema::dropIfExists('connect_campaign_dlq');
        Schema::dropIfExists('connect_campaign_delivery_attempts');
    }
};
