<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('connect_domain_dns_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->constrained('connect_domains')->cascadeOnDelete();
            $table->string('type');
            $table->string('name');
            $table->string('expected_value')->nullable();
            $table->string('current_value')->nullable();
            $table->string('status')->default('pending');
            
            $table->timestamps();
            
        });
    }

    public function down()
    {
        Schema::dropIfExists('connect_domain_dns_records');
    }
};
