<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->decimal('pending_balance', 28, 8)->default(0)->after('available_balance');
            $table->decimal('withdrawn_balance', 28, 8)->default(0)->after('reserved_balance');
        });
    }

    public function down(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->dropColumn(['pending_balance', 'withdrawn_balance']);
        });
    }
};
