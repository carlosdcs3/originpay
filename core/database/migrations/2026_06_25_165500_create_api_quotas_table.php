<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('api_quotas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            
            // Rate Limit per minute
            $table->integer('rate_limit_general')->default(300); // GET, etc.
            $table->integer('rate_limit_financial')->default(30); // POST /payments, /refunds, /payouts

            // Quotas
            $table->bigInteger('quota_daily')->default(1000); // 0 = unlimited
            $table->bigInteger('quota_monthly')->default(30000); // 0 = unlimited
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('api_quotas');
    }
};
