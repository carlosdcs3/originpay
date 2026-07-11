<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('platform_incidents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('severity')->index(); // high, critical, minor
            $table->string('status')->index(); // open, investigating, monitoring, resolved
            $table->timestamp('started_at');
            $table->timestamp('resolved_at')->nullable();
            $table->bigInteger('duration_ms')->nullable();
            $table->text('root_cause')->nullable();
            $table->text('resolution')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('resolved_by')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('platform_incidents');
    }
};
