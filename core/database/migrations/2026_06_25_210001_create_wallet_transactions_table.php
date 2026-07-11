<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('wallet_id')->constrained('wallets')->onDelete('cascade');
            
            $table->string('type'); // charge, fee, withdrawal, adjustment, refund
            
            $table->decimal('amount', 28, 8); // positive or negative
            $table->decimal('balance_after', 28, 8);
            
            $table->string('description')->nullable();
            
            $table->string('reference_type')->nullable(); // e.g. App\Models\Charge
            $table->unsignedBigInteger('reference_id')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
