<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('support_chats', function (Blueprint $table) {
            if (!Schema::hasColumn('support_chats', 'is_system')) {
                $table->boolean('is_system')->default(false)->after('sender');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('support_chats', function (Blueprint $table) {
            $table->dropColumn('is_system');
        });
    }
};
