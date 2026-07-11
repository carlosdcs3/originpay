<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCommercialFeaturesTable extends Migration
{
    public function up()
    {
        Schema::create('commercial_features', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('category')->default('general');
            $table->text('description')->nullable();
            
            $table->string('type')->default('boolean'); // boolean, limit, rate
            $table->string('icon')->nullable();
            
            $table->boolean('is_visible')->default(true);
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('commercial_features');
    }
}
