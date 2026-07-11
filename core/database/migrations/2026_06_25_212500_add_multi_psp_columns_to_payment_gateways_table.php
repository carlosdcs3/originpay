<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('payment_gateways', function (Blueprint $table) {
            $table->integer('priority')->default(999)->after('status')->comment('Lower is higher priority');
            $table->boolean('is_sandbox')->default(true)->after('priority');
            $table->boolean('supports_pix')->default(false)->after('is_sandbox');
            $table->boolean('supports_card')->default(false)->after('supports_pix');
            $table->boolean('supports_refund')->default(false)->after('supports_card');
            // The table already had some withdraw_field, we will add explicit boolean flag
            $table->boolean('supports_withdrawal')->default(false)->after('supports_refund');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('payment_gateways', function (Blueprint $table) {
            $table->dropColumn([
                'priority',
                'is_sandbox',
                'supports_pix',
                'supports_card',
                'supports_refund',
                'supports_withdrawal'
            ]);
        });
    }
};
