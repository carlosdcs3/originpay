<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('connect_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->foreignId('segment_id')->nullable()->constrained('connect_segments')->nullOnDelete()->nullable();
            $table->foreignId('template_id')->nullable()->constrained('connect_templates')->nullOnDelete()->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('scheduled_at')->nullable();
            $table->index('status');
            $table->index('scheduled_at');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('connect_campaigns');
    }
};
