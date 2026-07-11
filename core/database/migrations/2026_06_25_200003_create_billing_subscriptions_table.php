<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBillingSubscriptionsTable extends Migration
{
    public function up()
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('plan_version_id');
            $table->unsignedBigInteger('price_id')->nullable();
            
            // Status: trialing, pending_payment, active, past_due, canceled, expired, suspended
            $table->string('status')->default('pending_payment');
            
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            
            $table->boolean('cancel_at_period_end')->default(false);
            $table->timestamp('canceled_at')->nullable();
            
            $table->timestamps();

            // $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            // $table->foreign('plan_version_id')->references('id')->on('plan_versions')->onDelete('cascade');
            // $table->foreign('price_id')->references('id')->on('prices')->onDelete('set null');
        });

        Schema::create('subscription_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('subscription_id');
            $table->unsignedBigInteger('user_id');
            
            $table->unsignedBigInteger('old_plan_version_id')->nullable();
            $table->unsignedBigInteger('new_plan_version_id')->nullable();
            $table->unsignedBigInteger('old_price_id')->nullable();
            $table->unsignedBigInteger('new_price_id')->nullable();
            
            $table->string('old_status')->nullable();
            $table->string('new_status')->nullable();
            
            $table->string('action'); // created, upgraded, downgraded, canceled, renewed, failed
            $table->text('reason')->nullable();
            
            $table->timestamps();

            // $table->foreign('subscription_id')->references('id')->on('subscriptions')->onDelete('cascade');
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            // $table->foreign('old_plan_version_id')->references('id')->on('plan_versions')->onDelete('set null');
            // $table->foreign('new_plan_version_id')->references('id')->on('plan_versions')->onDelete('set null');
            // $table->foreign('old_price_id')->references('id')->on('prices')->onDelete('set null');
            // $table->foreign('new_price_id')->references('id')->on('prices')->onDelete('set null');
        });

        Schema::create('usage_metrics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('subscription_id');
            $table->unsignedBigInteger('user_id');
            
            $table->string('metric_type'); // api_requests, webhooks, storage_mb, users
            $table->bigInteger('used')->default(0);
            
            $table->timestamp('cycle_start')->nullable();
            $table->timestamp('cycle_end')->nullable();
            
            $table->timestamps();

            // $table->foreign('subscription_id')->references('id')->on('subscriptions')->onDelete('cascade');
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            $table->index(['user_id', 'metric_type', 'cycle_end']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('usage_metrics');
        Schema::dropIfExists('subscription_history');
        Schema::dropIfExists('subscriptions');
    }
}
