<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_dlqs', function (Blueprint $table) {
            $table->id();
            $table->string('provider');
            $table->string('event_id')->nullable();
            $table->string('external_reference')->nullable();
            $table->longText('payload');
            $table->longText('headers')->nullable();
            $table->text('error_message');
            $table->string('error_class');
            $table->integer('attempts')->default(0);
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_dlqs');
    }
};
