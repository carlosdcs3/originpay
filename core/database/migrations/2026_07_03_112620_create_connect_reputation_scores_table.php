<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('connect_reputation_scores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('merchant_id');
            $table->string('channel');
            $table->integer('score')->default(100);
            $table->string('health_status')->default('good');
            $table->index('merchant_id');
            $table->index('channel');
            $table->timestamps();
            
        });
    }

    public function down()
    {
        Schema::dropIfExists('connect_reputation_scores');
    }
};
