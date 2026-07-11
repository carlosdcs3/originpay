<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('webhook_dead_letters')) {
            Schema::table('webhook_dead_letters', function (Blueprint $table) {
                if (! Schema::hasColumn('webhook_dead_letters', 'webhook_event_id')) {
                    $table->unsignedBigInteger('webhook_event_id')->nullable()->after('id')->index();
                }
                if (! Schema::hasColumn('webhook_dead_letters', 'headers')) {
                    $table->json('headers')->nullable()->after('payload');
                }
                if (! Schema::hasColumn('webhook_dead_letters', 'received_at')) {
                    $table->timestamp('received_at')->nullable()->after('status');
                }
                if (! Schema::hasColumn('webhook_dead_letters', 'signature')) {
                    $table->string('signature')->nullable()->after('headers');
                }
                if (! Schema::hasColumn('webhook_dead_letters', 'provider_timestamp')) {
                    $table->string('provider_timestamp')->nullable()->after('signature');
                }
            });
        }

        if (Schema::hasTable('webhook_events')) {
            Schema::table('webhook_events', function (Blueprint $table) {
                if (! Schema::hasColumn('webhook_events', 'failed_at')) {
                    $table->timestamp('failed_at')->nullable()->after('processed_at');
                }
                if (! Schema::hasColumn('webhook_events', 'payload_hash')) {
                    $table->string('payload_hash')->nullable()->after('headers');
                }
                if (! Schema::hasColumn('webhook_events', 'correlation_id')) {
                    $table->uuid('correlation_id')->nullable()->after('payload_hash')->index();
                }
            });
        }
    }

    public function down(): void
    {
        // Intentionally non-destructive for production compatibility.
    }
};
