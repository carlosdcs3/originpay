<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('webhook_events', function (Blueprint $table) {
            $table->unsignedBigInteger('resolution_admin_id')->nullable();
            $table->text('resolution_reason')->nullable();
        });

        Schema::table('webhook_dlqs', function (Blueprint $table) {
            $table->unsignedBigInteger('resolution_admin_id')->nullable();
            $table->text('resolution_reason')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('webhook_events', function (Blueprint $table) {
            $table->dropColumn(['resolution_admin_id', 'resolution_reason']);
        });

        Schema::table('webhook_dlqs', function (Blueprint $table) {
            $table->dropColumn(['resolution_admin_id', 'resolution_reason']);
        });
    }
};
