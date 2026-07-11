<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1. Add nullable UUID column
        Schema::table('support_chat_attachments', function (Blueprint $table) {
            if (!Schema::hasColumn('support_chat_attachments', 'uuid')) {
                $table->uuid('uuid')->nullable()->after('id');
            }
        });

        // 2. Populate UUID for existing records
        $attachments = DB::table('support_chat_attachments')->whereNull('uuid')->get();
        foreach ($attachments as $attachment) {
            DB::table('support_chat_attachments')
                ->where('id', $attachment->id)
                ->update(['uuid' => (string) Str::uuid()]);
        }

        // 3. Make column not nullable and unique
        Schema::table('support_chat_attachments', function (Blueprint $table) {
            // Check if column is already unique to prevent errors if re-run
            $table->uuid('uuid')->nullable(false)->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('support_chat_attachments', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }
};
