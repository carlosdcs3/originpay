<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('platform_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('category')->index(); // Gateway, API, Compliance, Financeiro, Sistema, Segurança
            $table->string('severity')->index(); // Info, Warning, Critical
            $table->string('source')->nullable(); // Ex: EfiAdapter, WebhookDispatcher
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('recommended_action')->nullable();
            $table->string('related_link')->nullable();
            $table->string('status')->default('active')->index(); // active, resolved, ignored
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('platform_alerts');
    }
};
