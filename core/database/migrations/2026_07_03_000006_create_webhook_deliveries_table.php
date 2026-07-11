<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('webhook_deliveries')) {
            Schema::create('webhook_deliveries', function (Blueprint $table) {
                $table->id();
                $table->string('delivery_id')->unique();
                $table->foreignId('webhook_endpoint_id')->constrained('webhook_endpoints')->onDelete('cascade');
                $table->foreignId('webhook_event_id')->constrained('webhook_events')->onDelete('cascade');
                $table->string('status')->default('pending')->index();
                $table->integer('attempt_count')->default(0);
                $table->timestamp('next_attempt_at')->nullable()->index();
                $table->timestamp('last_attempt_at')->nullable();
                $table->integer('response_status')->nullable();
                $table->text('response_body')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamps();

                $table->index(['webhook_endpoint_id', 'webhook_event_id']);
            });

            return;
        }

        Schema::table('webhook_deliveries', function (Blueprint $table) {
            if (! Schema::hasColumn('webhook_deliveries', 'delivery_id')) {
                $table->string('delivery_id')->nullable()->after('id');
            }

            if (! Schema::hasColumn('webhook_deliveries', 'webhook_event_id')) {
                $table->unsignedBigInteger('webhook_event_id')->nullable()->after('webhook_endpoint_id');
            }

            if (! Schema::hasColumn('webhook_deliveries', 'status')) {
                $table->string('status')->default('pending')->after('webhook_event_id');
            }

            if (! Schema::hasColumn('webhook_deliveries', 'attempt_count')) {
                $table->integer('attempt_count')->default(0)->after('status');
            }

            if (! Schema::hasColumn('webhook_deliveries', 'next_attempt_at')) {
                $table->timestamp('next_attempt_at')->nullable()->after('attempt_count');
            }

            if (! Schema::hasColumn('webhook_deliveries', 'last_attempt_at')) {
                $table->timestamp('last_attempt_at')->nullable()->after('next_attempt_at');
            }

            if (! Schema::hasColumn('webhook_deliveries', 'response_status')) {
                $table->integer('response_status')->nullable()->after('last_attempt_at');
            }

            if (! Schema::hasColumn('webhook_deliveries', 'error_message')) {
                $table->text('error_message')->nullable()->after('response_body');
            }
        });
    }

    public function down()
    {
        Schema::dropIfExists('webhook_deliveries');
    }
};
