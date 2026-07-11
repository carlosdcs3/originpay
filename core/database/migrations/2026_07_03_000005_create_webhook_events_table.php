<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('webhook_events')) {
            Schema::create('webhook_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('merchant_id')->constrained('merchants')->onDelete('cascade');
                $table->string('event_id')->unique();
                $table->string('event_type')->index();
                $table->string('api_version');
                $table->string('environment')->index();
                $table->json('payload');
                $table->timestamp('created_at')->useCurrent()->index();

                $table->index('merchant_id');
            });

            return;
        }

        $missingColumns = array_filter(
            ['merchant_id', 'api_version', 'environment', 'payload'],
            fn (string $column) => ! Schema::hasColumn('webhook_events', $column)
        );

        if ($missingColumns === []) {
            return;
        }

        Schema::table('webhook_events', function (Blueprint $table) use ($missingColumns) {
            if (in_array('merchant_id', $missingColumns, true)) {
                $table->unsignedBigInteger('merchant_id')->nullable()->after('id');
            }

            if (in_array('api_version', $missingColumns, true)) {
                $table->string('api_version')->nullable()->after('event_type');
            }

            if (in_array('environment', $missingColumns, true)) {
                $table->string('environment')->nullable()->after('api_version');
            }

            if (in_array('payload', $missingColumns, true)) {
                $table->json('payload')->nullable()->after('environment');
            }
        });
    }

    public function down()
    {
        Schema::dropIfExists('webhook_events');
    }
};
