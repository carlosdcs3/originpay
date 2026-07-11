<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('transaction_passwords') || Schema::hasColumn('transaction_passwords', 'last_changed_at')) {
            return;
        }

        Schema::table('transaction_passwords', function (Blueprint $table) {
            $table->timestamp('last_changed_at')->nullable()->after('locked_until');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('transaction_passwords') || ! Schema::hasColumn('transaction_passwords', 'last_changed_at')) {
            return;
        }

        Schema::table('transaction_passwords', function (Blueprint $table) {
            $table->dropColumn('last_changed_at');
        });
    }
};
