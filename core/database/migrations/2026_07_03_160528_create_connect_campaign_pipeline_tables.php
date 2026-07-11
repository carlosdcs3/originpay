<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('connect_campaign_executions')) {
            Schema::create('connect_campaign_executions', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('campaign_id')->constrained('connect_campaigns')->cascadeOnDelete();
                $table->foreignId('merchant_id')->constrained('users')->cascadeOnDelete();
                $table->string('status')->default('pending');
                $table->timestamp('started_at')->nullable();
                $table->timestamp('finished_at')->nullable();
                $table->unsignedInteger('total_audience')->default(0);
                $table->unsignedInteger('queued_count')->default(0);
                $table->unsignedInteger('processed_count')->default(0);
                $table->unsignedInteger('failed_count')->default(0);
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->index('campaign_id');
                $table->index('merchant_id');
                $table->index('status');
            });
        }

        if (! Schema::hasTable('connect_campaign_recipients')) {
            Schema::create('connect_campaign_recipients', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('execution_id')->constrained('connect_campaign_executions')->cascadeOnDelete();
                $table->foreignId('campaign_id')->constrained('connect_campaigns')->cascadeOnDelete();
                $table->foreignId('merchant_id')->constrained('users')->cascadeOnDelete();
                $table->unsignedBigInteger('contact_id');
                $table->string('channel');
                $table->string('status')->default('pending');
                $table->unsignedInteger('attempts')->default(0);
                $table->timestamp('last_attempt_at')->nullable();
                $table->timestamp('scheduled_at')->nullable();
                $table->timestamp('processed_at')->nullable();
                $table->text('failed_reason')->nullable();
                $table->longText('payload_snapshot')->nullable();
                $table->timestamps();
                $table->index('execution_id');
                $table->index('campaign_id');
                $table->index('status');
                $table->index('channel');
                $table->unique(['execution_id', 'contact_id']);
            });

            return;
        }

        $missingColumns = array_filter(
            [
                'uuid',
                'execution_id',
                'merchant_id',
                'channel',
                'attempts',
                'last_attempt_at',
                'scheduled_at',
                'processed_at',
                'failed_reason',
                'payload_snapshot',
            ],
            fn (string $column) => ! Schema::hasColumn('connect_campaign_recipients', $column)
        );

        if ($missingColumns === []) {
            return;
        }

        Schema::table('connect_campaign_recipients', function (Blueprint $table) use ($missingColumns) {
            if (in_array('uuid', $missingColumns, true)) {
                $table->uuid('uuid')->nullable()->after('id');
            }
            if (in_array('execution_id', $missingColumns, true)) {
                $table->foreignId('execution_id')->nullable()->after('uuid')->constrained('connect_campaign_executions')->cascadeOnDelete();
            }
            if (in_array('merchant_id', $missingColumns, true)) {
                $table->foreignId('merchant_id')->nullable()->after('campaign_id')->constrained('users')->cascadeOnDelete();
            }
            if (in_array('channel', $missingColumns, true)) {
                $table->string('channel')->default('email')->after('contact_id');
            }
            if (in_array('attempts', $missingColumns, true)) {
                $table->unsignedInteger('attempts')->default(0)->after('status');
            }
            if (in_array('last_attempt_at', $missingColumns, true)) {
                $table->timestamp('last_attempt_at')->nullable()->after('attempts');
            }
            if (in_array('scheduled_at', $missingColumns, true)) {
                $table->timestamp('scheduled_at')->nullable()->after('last_attempt_at');
            }
            if (in_array('processed_at', $missingColumns, true)) {
                $table->timestamp('processed_at')->nullable()->after('scheduled_at');
            }
            if (in_array('failed_reason', $missingColumns, true)) {
                $table->text('failed_reason')->nullable()->after('processed_at');
            }
            if (in_array('payload_snapshot', $missingColumns, true)) {
                $table->longText('payload_snapshot')->nullable()->after('failed_reason');
            }
        });
    }

    public function down()
    {
        Schema::dropIfExists('connect_campaign_recipients');
        Schema::dropIfExists('connect_campaign_executions');
    }
};
