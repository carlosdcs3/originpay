<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('connect_campaigns', function (Blueprint $table) {
            if (! Schema::hasColumn('connect_campaigns', 'execution_uuid')) {
                $table->uuid('execution_uuid')->nullable()->after('uuid');
            }
            if (! Schema::hasColumn('connect_campaigns', 'campaign_version')) {
                $table->integer('campaign_version')->default(1)->after('name');
            }
            if (! Schema::hasColumn('connect_campaigns', 'estimated_audience_count')) {
                $table->unsignedInteger('estimated_audience_count')->nullable()->after('campaign_version');
            }
            if (! Schema::hasColumn('connect_campaigns', 'max_attempts')) {
                $table->unsignedInteger('max_attempts')->default(3)->after('status');
            }
            if (! Schema::hasColumn('connect_campaigns', 'attempts')) {
                $table->unsignedInteger('attempts')->default(0)->after('max_attempts');
            }
            if (! Schema::hasColumn('connect_campaigns', 'last_attempt_at')) {
                $table->timestamp('last_attempt_at')->nullable()->after('attempts');
            }
        });
    }

    public function down()
    {
        Schema::table('connect_campaigns', function (Blueprint $table) {
            $table->dropColumn([
                'execution_uuid',
                'campaign_version',
                'estimated_audience_count',
                'max_attempts',
                'attempts',
                'last_attempt_at'
            ]);
        });
    }
};
