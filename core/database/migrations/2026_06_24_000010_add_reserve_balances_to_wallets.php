<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->decimal('available_balance', 28, 8)->default(0)->after('balance');
            $table->decimal('reserved_balance', 28, 8)->default(0)->after('available_balance');
        });

        // Seed current balance into available_balance
        DB::statement('UPDATE wallets SET available_balance = balance');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->dropColumn(['available_balance', 'reserved_balance']);
        });
    }
};
