<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('webhook_deliveries', function (Blueprint $table) {
            if (! Schema::hasColumn('webhook_deliveries', 'idempotency_key')) {
                $table->string('idempotency_key')->nullable()->after('event_type');
                $table->unique(['webhook_endpoint_id', 'idempotency_key'], 'webhook_delivery_endpoint_idem_unique');
            }
        });
    }

    public function down(): void
    {
        Schema::table('webhook_deliveries', function (Blueprint $table) {
            if (Schema::hasColumn('webhook_deliveries', 'idempotency_key')) {
                $table->dropUnique('webhook_delivery_endpoint_idem_unique');
                $table->dropColumn('idempotency_key');
            }
        });
    }
};
