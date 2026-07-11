<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('disputes', function (Blueprint $table) {
            $table->string('action_queue')->nullable()->index();
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->unsignedBigInteger('assigned_by')->nullable();
            $table->integer('health_score')->nullable();

            $table->foreign('owner_id')->references('id')->on('admins')->onDelete('set null');
            $table->foreign('assigned_by')->references('id')->on('admins')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('disputes', function (Blueprint $table) {
            $table->dropForeign(['owner_id']);
            $table->dropForeign(['assigned_by']);
            $table->dropColumn(['action_queue', 'owner_id', 'assigned_at', 'assigned_by', 'health_score']);
        });
    }
};
