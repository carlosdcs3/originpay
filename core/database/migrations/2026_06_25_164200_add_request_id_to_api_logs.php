<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('api_logs', function (Blueprint $table) {
            $table->string('request_id')->nullable()->after('id');
        });
    }

    public function down()
    {
        Schema::table('api_logs', function (Blueprint $table) {
            $table->dropColumn('request_id');
        });
    }
};
