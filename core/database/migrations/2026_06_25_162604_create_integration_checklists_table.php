<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('integration_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->boolean('has_api_key')->default(false);
            $table->boolean('has_webhook')->default(false);
            $table->boolean('has_test_charge')->default(false);
            $table->boolean('has_simulated_payment')->default(false);
            $table->boolean('has_received_webhook')->default(false);
            $table->boolean('is_production_active')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('integration_checklists');
    }
};
