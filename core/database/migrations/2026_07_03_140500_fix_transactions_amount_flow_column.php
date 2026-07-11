<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('transactions') || ! Schema::hasColumn('transactions', 'amount_flow')) {
            return;
        }

        Schema::table('transactions', function (Blueprint $table) {
            $table->string('amount_flow', 20)->nullable()->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('transactions') || ! Schema::hasColumn('transactions', 'amount_flow')) {
            return;
        }

        Schema::table('transactions', function (Blueprint $table) {
            $table->decimal('amount_flow', 8, 2)->nullable()->change();
        });
    }
};
