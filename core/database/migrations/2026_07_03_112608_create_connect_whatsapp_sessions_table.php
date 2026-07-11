<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('connect_whatsapp_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instance_id')->constrained('connect_whatsapp_instances')->cascadeOnDelete();
            $table->text('session_token');
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            
            $table->timestamps();
            
        });
    }

    public function down()
    {
        Schema::dropIfExists('connect_whatsapp_sessions');
    }
};
