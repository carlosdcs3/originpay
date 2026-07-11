<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('reconciliation_histories', function (Blueprint $table) {
            $table->id();
            $table->string('gateway_code')->index();
            $table->integer('processed_count')->default(0);
            $table->integer('divergences_count')->default(0);
            $table->integer('duration_ms')->default(0);
            $table->string('status')->default('success'); // success, failed
            $table->text('error_message')->nullable();
            $table->json('divergences_details')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('reconciliation_histories');
    }
};
