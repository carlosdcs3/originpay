<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('financial_compliance_audits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action');
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->text('reason')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            // We do not define foreign keys for audits strictly so we don't block deletions 
            // if we really wanted to, but actually keeping audits robust is key. 
            // We'll leave it without FK constraints to avoid cascade issues if an admin is deleted.
        });
    }

    public function down()
    {
        Schema::dropIfExists('financial_compliance_audits');
    }
};
