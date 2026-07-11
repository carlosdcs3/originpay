<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('webhook_endpoints')) {
            Schema::create('webhook_endpoints', function (Blueprint $table) {
                $table->id();
                $table->foreignId('merchant_id')->constrained('merchants')->onDelete('cascade');
                $table->string('url');
                $table->text('secret_encrypted');
                $table->string('secret_preview', 32);
                $table->string('environment')->index();
                $table->string('status')->default('active')->index();
                $table->json('events');
                $table->string('description')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index('merchant_id');
            });

            return;
        }

        Schema::table('webhook_endpoints', function (Blueprint $table) {
            if (! Schema::hasColumn('webhook_endpoints', 'merchant_id')) {
                $table->unsignedBigInteger('merchant_id')->nullable()->after('id');
            }

            if (! Schema::hasColumn('webhook_endpoints', 'secret_encrypted')) {
                $table->text('secret_encrypted')->nullable()->after('url');
            }

            if (! Schema::hasColumn('webhook_endpoints', 'secret_preview')) {
                $table->string('secret_preview', 32)->nullable()->after('secret_encrypted');
            }

            if (! Schema::hasColumn('webhook_endpoints', 'description')) {
                $table->string('description')->nullable()->after('events');
            }

            if (! Schema::hasColumn('webhook_endpoints', 'last_used_at')) {
                $table->timestamp('last_used_at')->nullable()->after('description');
            }

            if (! Schema::hasColumn('webhook_endpoints', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down()
    {
        Schema::dropIfExists('webhook_endpoints');
    }
};
