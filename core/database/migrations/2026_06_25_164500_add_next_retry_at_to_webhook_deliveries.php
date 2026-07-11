<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('webhook_deliveries', function (Blueprint $table) {
            $table->timestamp('next_retry_at')->nullable();
        });
    }

    public function down()
    {
        Schema::table('webhook_deliveries', function (Blueprint $table) {
            $table->dropColumn('next_retry_at');
        });
    }
};
