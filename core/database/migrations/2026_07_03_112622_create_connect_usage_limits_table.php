<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('connect_usage_limits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained('users')->cascadeOnDelete();
            $table->string('channel');
            $table->integer('monthly_limit')->default(0);
            $table->integer('current_usage')->default(0);
            $table->timestamp('resets_at')->nullable();
            $table->index('channel');
            $table->timestamps();
            
        });
    }

    public function down()
    {
        Schema::dropIfExists('connect_usage_limits');
    }
};
