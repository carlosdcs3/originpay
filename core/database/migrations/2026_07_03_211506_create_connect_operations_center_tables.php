<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. Snapshots
        if (! Schema::hasTable('connect_metrics_snapshots')) {
            Schema::create('connect_metrics_snapshots', function (Blueprint $table) {
                $table->id();
                $table->foreignId('merchant_id')->constrained('users')->cascadeOnDelete();
                $table->string('resolution'); // hourly, daily
                $table->timestamp('bucket_start');
                $table->timestamp('bucket_end');
                $table->string('metric_type'); // latency, throughput, success_rate, volume
                $table->decimal('value', 15, 2)->default(0);
                $table->decimal('p50', 15, 2)->nullable();
                $table->decimal('p90', 15, 2)->nullable();
                $table->decimal('p95', 15, 2)->nullable();
                $table->decimal('p99', 15, 2)->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->index(['merchant_id', 'resolution', 'bucket_start'], 'cms_merchant_resolution_bucket_idx');
                $table->index('metric_type');
            });
        }

        // 2. Event Store
        if (! Schema::hasTable('connect_event_log')) {
            Schema::create('connect_event_log', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('merchant_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('aggregate_type'); // Campaign, Execution, Recipient, Provider
                $table->unsignedBigInteger('aggregate_id');
                $table->string('event_type');
                $table->json('payload')->nullable();
                $table->timestamp('occurred_at');
                $table->timestamps();
                $table->index(['aggregate_type', 'aggregate_id'], 'cel_aggregate_idx');
                $table->index('event_type');
                $table->index('merchant_id');
                $table->index('occurred_at');
            });
        }

        // 3. Alert Rules
        if (! Schema::hasTable('connect_alert_rules')) {
            Schema::create('connect_alert_rules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('merchant_id')->constrained('users')->cascadeOnDelete();
                $table->string('rule_type'); // threshold, state
                $table->string('target'); // latency, dlq, failure_rate, circuit_breaker
                $table->string('operator')->nullable(); // >, <, =
                $table->decimal('threshold_value', 10, 2)->nullable();
                $table->string('expected_state')->nullable(); // OPEN, OFFLINE
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 4. Alerts
        if (! Schema::hasTable('connect_alerts')) {
            Schema::create('connect_alerts', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('merchant_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('rule_id')->nullable()->constrained('connect_alert_rules')->nullOnDelete();
                $table->string('severity'); // info, warning, critical
                $table->string('title');
                $table->text('message');
                $table->json('context')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();
                $table->index('merchant_id');
                $table->index('severity');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('connect_alerts');
        Schema::dropIfExists('connect_alert_rules');
        Schema::dropIfExists('connect_event_log');
        Schema::dropIfExists('connect_metrics_snapshots');
    }
};
