<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCommercialPlansTable extends Migration
{
    public function up()
    {
        Schema::create('commercial_plans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            
            $table->string('color')->nullable();
            $table->string('icon')->nullable();
            $table->string('badge')->nullable();

            $table->timestamps();

            // $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });

        Schema::create('plan_versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('plan_id');
            $table->unsignedBigInteger('product_version_id');
            $table->integer('version_number');
            
            $table->boolean('is_active')->default(true);
            $table->timestamp('deprecated_at')->nullable();
            
            $table->timestamps();

            // $table->foreign('plan_id')->references('id')->on('commercial_plans')->onDelete('cascade');
            // $table->foreign('product_version_id')->references('id')->on('product_versions')->onDelete('cascade');
            $table->unique(['plan_id', 'version_number']);
        });

        Schema::create('prices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('plan_version_id');
            
            $table->string('currency')->default('BRL');
            $table->bigInteger('amount')->default(0); // in cents
            $table->string('billing_period')->default('monthly'); // monthly, annual, lifetime, one_time
            
            $table->bigInteger('setup_fee')->default(0);
            $table->integer('trial_days')->default(0);
            
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // $table->foreign('plan_version_id')->references('id')->on('plan_versions')->onDelete('cascade');
        });

        Schema::create('plan_version_features', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('plan_version_id');
            $table->unsignedBigInteger('commercial_feature_id');
            
            // For boolean features, 'is_enabled' is enough. 
            // For limit/rate features, 'value' holds the config (e.g. "100", "0.99")
            $table->boolean('is_enabled')->default(true);
            $table->string('value')->nullable();
            
            $table->timestamps();

            // $table->foreign('plan_version_id')->references('id')->on('plan_versions')->onDelete('cascade');
            // $table->foreign('commercial_feature_id')->references('id')->on('commercial_features')->onDelete('cascade');
            
            $table->unique(['plan_version_id', 'commercial_feature_id'], 'pvf_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('plan_version_features');
        Schema::dropIfExists('prices');
        Schema::dropIfExists('plan_versions');
        Schema::dropIfExists('commercial_plans');
    }
}
