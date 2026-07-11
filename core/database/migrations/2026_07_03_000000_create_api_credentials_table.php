<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('api_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained('merchants')->onDelete('cascade');
            $table->string('public_key')->unique();
            $table->string('secret_key_hash');
            $table->string('key_prefix')->index();
            $table->string('environment')->index(); // sandbox or production
            $table->string('status')->default('active')->index();
            $table->json('permissions')->nullable();
            $table->string('api_version')->default('v1');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('grace_period_expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('merchant_id'); // Just explicitly showing, though foreignId creates an index
        });
    }

    public function down()
    {
        Schema::dropIfExists('api_credentials');
    }
};
