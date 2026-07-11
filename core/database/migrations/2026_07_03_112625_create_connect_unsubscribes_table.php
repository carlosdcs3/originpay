<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('connect_unsubscribes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('merchant_id');
            $table->unsignedBigInteger('contact_id');
            $table->string('channel');
            $table->unique(['merchant_id', 'contact_id', 'channel'], 'unsubscribe_unique');
            $table->index('merchant_id');
            $table->timestamps();
            
        });
    }

    public function down()
    {
        Schema::dropIfExists('connect_unsubscribes');
    }
};
