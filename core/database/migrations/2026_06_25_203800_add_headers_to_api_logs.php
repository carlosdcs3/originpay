<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('api_logs', function (Blueprint $table) {
            $table->json('request_headers')->nullable();
            $table->json('response_headers')->nullable();
        });
    }

    public function down()
    {
        Schema::table('api_logs', function (Blueprint $table) {
            $table->dropColumn(['request_headers', 'response_headers']);
        });
    }
};
