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
        Schema::create('disputes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('type'); // MED, Chargeback, Extorno, etc
            $table->string('status'); 
            $table->unsignedBigInteger('merchant_id'); // Lojista
            $table->unsignedBigInteger('customer_id')->nullable(); // Cliente (se houver)
            $table->unsignedBigInteger('transaction_id')->nullable(); // Transação/Pagamento
            $table->string('gateway')->nullable();
            
            // Valores financeiros em centavos
            $table->unsignedBigInteger('amount_cents')->default(0);
            $table->unsignedBigInteger('retained_amount_cents')->default(0);
            $table->unsignedBigInteger('disputed_amount_cents')->default(0);
            $table->unsignedBigInteger('lost_amount_cents')->default(0);
            $table->unsignedBigInteger('recovered_amount_cents')->default(0);
            
            $table->string('reason')->nullable();
            $table->string('source')->nullable(); // Origem da disputa (banco, cliente)
            
            $table->timestamp('due_at')->nullable(); // Prazo final
            $table->timestamp('resolved_at')->nullable(); // Data de resolução
            $table->timestamps();
            
            // Índices básicos
            $table->index('merchant_id');
            $table->index('transaction_id');
            $table->index('status');
        });

        Schema::create('dispute_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispute_id')->constrained()->cascadeOnDelete();
            $table->string('sender_type'); // admin, merchant, system
            $table->unsignedBigInteger('sender_id')->nullable(); 
            $table->text('message');
            $table->json('attachments')->nullable();
            $table->timestamps();
        });

        Schema::create('dispute_evidence_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispute_id')->constrained()->cascadeOnDelete();
            $table->string('type')->nullable(); // invoice, tracking, chat
            $table->string('status')->default('pending'); // pending, received, validated, rejected
            $table->string('label');
            $table->boolean('required')->default(false);
            $table->string('file_path')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('dispute_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispute_id')->constrained()->cascadeOnDelete();
            $table->string('event_type');
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dispute_events');
        Schema::dropIfExists('dispute_evidence_items');
        Schema::dropIfExists('dispute_messages');
        Schema::dropIfExists('disputes');
    }
};
