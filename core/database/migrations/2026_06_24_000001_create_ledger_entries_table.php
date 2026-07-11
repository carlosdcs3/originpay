<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();
            // nullable because manual adjustments might not have a transaction
            $table->foreignId('transaction_id')->nullable()->constrained()->nullOnDelete();
            
            // The wallet affected
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            
            $table->enum('direction', ['credit', 'debit']);
            $table->decimal('amount', 15, 2)->unsigned();
            $table->string('currency', 3)->default('USD');
            
            // The balance of the wallet AFTER this operation
            $table->decimal('balance_after', 15, 2);
            
            $table->string('description')->nullable();
            $table->json('metadata')->nullable();
            
            // Ledger entries are immutable, they only have created_at
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
    }
};
