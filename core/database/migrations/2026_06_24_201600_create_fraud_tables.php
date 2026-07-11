<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fraud_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade');
            $table->integer('fraud_score')->default(0);
            $table->string('risk_level', 20)->default('LOW'); // LOW, MEDIUM, HIGH, CRITICAL
            $table->timestamp('last_evaluation_at')->nullable();
            $table->timestamps();
        });

        Schema::create('device_fingerprints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('fingerprint_hash'); // HMAC SHA256 (no IP)
            $table->string('ip_hash')->nullable(); // Separate IP hash
            $table->string('user_agent_hash')->nullable(); 
            $table->boolean('trusted')->default(false);
            $table->json('reduced_metadata')->nullable(); // safe, non-raw info
            $table->timestamp('first_seen_at')->useCurrent();
            $table->timestamp('last_seen_at')->useCurrent();
            $table->timestamps();

            $table->index('fingerprint_hash');
            $table->index('ip_hash');
        });

        Schema::create('fraud_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('type'); 
            $table->string('severity', 20);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('identity_fingerprints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('cpf_hash')->nullable();
            $table->string('document_hash')->nullable();
            $table->string('selfie_hash')->nullable();
            $table->timestamps();

            $table->index('cpf_hash');
            $table->index('document_hash');
            $table->index('selfie_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('identity_fingerprints');
        Schema::dropIfExists('fraud_events');
        Schema::dropIfExists('device_fingerprints');
        Schema::dropIfExists('fraud_profiles');
    }
};
