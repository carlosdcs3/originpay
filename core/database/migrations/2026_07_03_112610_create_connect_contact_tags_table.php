<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('connect_contact_tags', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('merchant_id');
            $table->unsignedBigInteger('contact_id');
            $table->string('tag_name');
            $table->unique(['merchant_id', 'contact_id', 'tag_name']);
            $table->index('merchant_id');
            $table->index('contact_id');
            $table->timestamps();
            
        });
    }

    public function down()
    {
        Schema::dropIfExists('connect_contact_tags');
    }
};
