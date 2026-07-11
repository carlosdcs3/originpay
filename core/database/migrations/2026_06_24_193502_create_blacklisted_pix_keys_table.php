<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('blacklisted_pix_keys', function (Blueprint $table) {
            $table->id();
            $table->string('pix_key')->unique();
            $table->string('reason')->nullable();
            $table->string('risk_level')->default('HIGH');
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->timestamps();

            $table->foreign('admin_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('blacklisted_pix_keys');
    }
};
