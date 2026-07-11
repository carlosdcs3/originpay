<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('withdrawal_approvals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('withdrawal_request_id');
            $table->unsignedBigInteger('admin_id');
            $table->integer('approval_level')->default(1);
            $table->string('role_at_approval')->nullable();
            $table->timestamps();

            $table->foreign('withdrawal_request_id')->references('id')->on('withdrawal_requests')->onDelete('cascade');
            $table->foreign('admin_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('withdrawal_approvals');
    }
};
