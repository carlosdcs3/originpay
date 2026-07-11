<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('connect_journeys', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('merchant_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('DRAFT'); // DRAFT, PUBLISHED, PAUSED, ARCHIVED
            $table->unsignedInteger('version')->default(1);
            $table->timestamp('published_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['merchant_id', 'status'], 'cj_merchant_status_idx');
        });

        Schema::create('connect_journey_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journey_id')->constrained('connect_journeys')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->json('graph'); // nodes, edges
            $table->string('checksum')->nullable();
            $table->timestamp('published_at')->useCurrent();
            $table->timestamps();
            
            $table->unique(['journey_id', 'version'], 'cjv_journey_version_unique');
        });

        Schema::create('connect_journey_instances', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('journey_id')->constrained('connect_journeys')->cascadeOnDelete();
            $table->foreignId('version_id')->constrained('connect_journey_versions')->cascadeOnDelete();
            $table->foreignId('merchant_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained('connect_contacts')->cascadeOnDelete();
            
            $table->string('current_node')->nullable();
            $table->string('status')->default('RUNNING'); // RUNNING, DELAYED, COMPLETED, CANCELLED, FAILED
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('finished_at')->nullable();
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            $table->index(['journey_id', 'status'], 'cji_journey_status_idx');
            $table->index(['contact_id', 'status'], 'cji_contact_status_idx');
        });

        Schema::create('connect_journey_trigger_locks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('journey_id')->constrained('connect_journeys')->cascadeOnDelete();
            $table->foreignId('version_id')->constrained('connect_journey_versions')->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained('connect_contacts')->cascadeOnDelete();
            $table->foreignId('instance_id')->constrained('connect_journey_instances')->cascadeOnDelete();
            
            $table->string('event_type');
            $table->string('event_id');
            
            $table->timestamps();
            
            $table->unique(['journey_id', 'event_type', 'event_id', 'contact_id'], 'idx_trigger_idempotency');
        });

        Schema::create('connect_journey_scheduled_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instance_id')->constrained('connect_journey_instances')->cascadeOnDelete();
            $table->string('node_id');
            $table->timestamp('resume_at');
            $table->string('status')->default('PENDING'); // PENDING, PROCESSED, CANCELLED
            $table->json('payload')->nullable();
            $table->timestamps();
            
            $table->index(['resume_at', 'status'], 'cjs_resume_status_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('connect_journey_scheduled_tasks');
        Schema::dropIfExists('connect_journey_trigger_locks');
        Schema::dropIfExists('connect_journey_instances');
        Schema::dropIfExists('connect_journey_versions');
        Schema::dropIfExists('connect_journeys');
    }
};
