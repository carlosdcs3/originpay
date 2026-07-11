<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('api_credential_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_credential_id')->constrained('api_credentials')->onDelete('cascade');
            $table->string('action')->index(); // created, rotated, revoked, used, expired, permission_changed
            $table->string('performed_by')->nullable(); // Admin ID or system
            $table->string('ip_address')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
            
            $table->index('api_credential_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('api_credential_events');
    }
};
