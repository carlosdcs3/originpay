<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->string('correlation_id')->nullable()->after('id')->index();
            $table->string('idempotency_key')->nullable()->after('correlation_id')->index();
            $table->decimal('balance_before', 28, 8)->default(0)->after('amount');
            // balance_after already exists in WalletTransaction model
            $table->string('previous_integrity_hash')->nullable()->after('reference_id');
            $table->string('integrity_hash')->nullable()->after('previous_integrity_hash');
            $table->json('metadata')->nullable()->after('integrity_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->dropColumn([
                'correlation_id',
                'idempotency_key',
                'balance_before',
                'previous_integrity_hash',
                'integrity_hash',
                'metadata'
            ]);
        });
    }
};
