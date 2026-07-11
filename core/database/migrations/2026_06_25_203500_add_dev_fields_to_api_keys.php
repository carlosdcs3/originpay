<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('api_keys', function (Blueprint $table) {
            $table->timestamp('rotated_at')->nullable();
            $table->string('last_ip', 45)->nullable();
        });
    }

    public function down()
    {
        Schema::table('api_keys', function (Blueprint $table) {
            $table->dropColumn(['rotated_at', 'last_ip']);
        });
    }
};
