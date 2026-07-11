<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withdrawal_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('wallet_id')->constrained();
            $table->string('transaction_id')->nullable()->index();
            $table->string('provider')->default('EFI');
            
            $table->string('pix_key_snapshot');
            $table->string('pix_key_type');
            $table->string('pix_owner_name')->nullable();
            $table->string('pix_owner_document')->nullable();
            
            $table->decimal('amount', 28, 8);
            $table->decimal('fee_amount', 28, 8)->default(0);
            $table->decimal('net_amount', 28, 8);
            
            $table->string('status')->default('PENDING')->index();
            
            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            
            $table->unsignedBigInteger('processed_by')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawal_requests');
    }
};
