<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('connect_campaigns')) {
            Schema::create('connect_campaigns', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('merchant_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('segment_id')->constrained('connect_segments')->restrictOnDelete();
                $table->foreignId('template_id')->constrained('connect_templates')->restrictOnDelete();
                $table->string('channel');
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('status')->default('draft');
                $table->string('schedule_type')->default('immediate');
                $table->timestamp('scheduled_at')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('finished_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->unsignedBigInteger('snapshot_template_id')->nullable();
                $table->integer('snapshot_template_version')->nullable();
                $table->unsignedBigInteger('snapshot_segment_id')->nullable();
                $table->integer('snapshot_segment_version')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->index('merchant_id');
                $table->index('status');
                $table->index('scheduled_at');
                $table->index('channel');
            });

            return;
        }

        Schema::table('connect_campaigns', function (Blueprint $table) {
            if (! Schema::hasColumn('connect_campaigns', 'uuid')) {
                $table->uuid('uuid')->nullable()->after('id');
            }
            if (! Schema::hasColumn('connect_campaigns', 'channel')) {
                $table->string('channel')->default('email')->after('template_id');
            }
            if (! Schema::hasColumn('connect_campaigns', 'description')) {
                $table->text('description')->nullable()->after('name');
            }
            if (! Schema::hasColumn('connect_campaigns', 'schedule_type')) {
                $table->string('schedule_type')->default('immediate')->after('status');
            }
            if (! Schema::hasColumn('connect_campaigns', 'started_at')) {
                $table->timestamp('started_at')->nullable()->after('scheduled_at');
            }
            if (! Schema::hasColumn('connect_campaigns', 'finished_at')) {
                $table->timestamp('finished_at')->nullable()->after('started_at');
            }
            if (! Schema::hasColumn('connect_campaigns', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('finished_at');
            }
            if (! Schema::hasColumn('connect_campaigns', 'snapshot_template_id')) {
                $table->unsignedBigInteger('snapshot_template_id')->nullable()->after('cancelled_at');
            }
            if (! Schema::hasColumn('connect_campaigns', 'snapshot_template_version')) {
                $table->integer('snapshot_template_version')->nullable()->after('snapshot_template_id');
            }
            if (! Schema::hasColumn('connect_campaigns', 'snapshot_segment_id')) {
                $table->unsignedBigInteger('snapshot_segment_id')->nullable()->after('snapshot_template_version');
            }
            if (! Schema::hasColumn('connect_campaigns', 'snapshot_segment_version')) {
                $table->integer('snapshot_segment_version')->nullable()->after('snapshot_segment_id');
            }
            if (! Schema::hasColumn('connect_campaigns', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('snapshot_segment_version')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('connect_campaigns', 'updated_by')) {
                $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('connect_campaigns', 'metadata')) {
                $table->json('metadata')->nullable()->after('updated_by');
            }
        });
    }

    public function down()
    {
        Schema::dropIfExists('connect_campaigns');
    }
};
