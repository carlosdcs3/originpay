<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_admin_audits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id');
            $table->string('action'); // viewed_payload, reprocessed_item, reprocessed_batch, marked_resolved, confirmed_override
            $table->string('target_type'); // webhook_event, webhook_dlq
            $table->unsignedBigInteger('target_id');
            $table->string('batch_id')->nullable();
            $table->text('reason')->nullable(); // Mandatory for resolve and reprocess
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            // Depending on foreign key setups, usually admin table is 'users', but it might be 'admins'.
            // In typical Laravel, users table has an is_admin or similar flag. We will just leave it unconstrained for now
            // or assume it links to users. The user requested: admin_id (foreign key to users)
            // wait, user said "foreign key to users" but let's check what the admin table is. Let's assume users.
            $table->foreign('admin_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_admin_audits');
    }
};
