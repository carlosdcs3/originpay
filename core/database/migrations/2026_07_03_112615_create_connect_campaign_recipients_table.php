<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('connect_campaign_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('connect_campaigns')->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained('connect_contacts')->cascadeOnDelete();
            $table->unsignedBigInteger('message_log_id')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->unique(['campaign_id', 'contact_id']);
            $table->index('status');
            $table->timestamps();
            
        });
    }

    public function down()
    {
        Schema::dropIfExists('connect_campaign_recipients');
    }
};
