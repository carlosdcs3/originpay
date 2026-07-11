<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCommercialProductsTable extends Migration
{
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            
            $table->string('icon')->nullable();
            $table->string('badge')->nullable();

            $table->timestamps();
        });

        Schema::create('product_versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->integer('version_number');
            
            $table->boolean('is_active')->default(true);
            $table->timestamp('deprecated_at')->nullable();
            
            $table->timestamps();

            // $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->unique(['product_id', 'version_number']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_versions');
        Schema::dropIfExists('products');
    }
}
