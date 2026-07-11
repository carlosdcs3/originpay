<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('connect_automation_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_id')->constrained('connect_automations')->cascadeOnDelete();
            $table->string('type');
            $table->integer('delay_minutes')->default(0);
            $table->foreignId('template_id')->nullable()->constrained('connect_templates')->nullOnDelete()->nullable();
            $table->integer('order')->default(1);
            
            $table->timestamps();
            
        });
    }

    public function down()
    {
        Schema::dropIfExists('connect_automation_steps');
    }
};
