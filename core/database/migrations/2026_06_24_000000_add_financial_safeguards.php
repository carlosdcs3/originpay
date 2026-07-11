<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // MySQL supports unsigned decimal. PostgreSQL does not, and fresh
        // PostgreSQL installs already use a compatible decimal definition.
        if (DB::getDriverName() === 'mysql') {
            Schema::table('wallets', function (Blueprint $table) {
                $table->decimal('balance', 15, 2)->unsigned()->change();
            });
        }

        // 2. Adicionar UNIQUE a (provider, trx_reference) em transactions
        Schema::table('transactions', function (Blueprint $table) {
            // Nota: Se provider ou trx_reference puderem ser nulos,
            // o índice UNIQUE permitirá múltiplos nulos. Mas para webhooks, eles devem ser populados.
            $table->unique(['provider', 'trx_reference'], 'transactions_provider_trx_ref_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->decimal('balance', 15, 2)->change();
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropUnique('transactions_provider_trx_ref_unique');
        });
    }
};
