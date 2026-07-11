<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('webhook_endpoints', function (Blueprint $table) {
            $table->string('old_secret')->nullable();
            $table->timestamp('old_secret_expires_at')->nullable();
        });
    }

    public function down()
    {
        Schema::table('webhook_endpoints', function (Blueprint $table) {
            $table->dropColumn(['old_secret', 'old_secret_expires_at']);
        });
    }
};
