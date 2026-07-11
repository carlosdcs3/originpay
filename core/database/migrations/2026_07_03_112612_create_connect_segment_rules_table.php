<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('connect_segment_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('segment_id')->nullable()->constrained('connect_segments')->nullOnDelete();
            $table->string('rule_type');
            $table->string('rule_value');
            $table->string('operator')->default('equals');
        });
    }

    public function down()
    {
        Schema::dropIfExists('connect_segment_rules');
    }
};
