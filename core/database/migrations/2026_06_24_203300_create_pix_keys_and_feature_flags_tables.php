<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pix_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('key_type'); // cpf, email, phone, random
            $table->string('pix_key');
            $table->boolean('verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->string('risk_level', 20)->default('LOW'); // LOW, MEDIUM, HIGH, CRITICAL
            $table->timestamp('last_used_at')->nullable();
            $table->string('status', 40)->default('ACTIVE'); // ACTIVE, BLOCKED, PENDING_OWNERSHIP_VERIFICATION
            $table->timestamps();

            $table->index('pix_key');
        });

        Schema::create('feature_flags', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->boolean('is_active')->default(false);
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Insert Default Flags
        \Illuminate\Support\Facades\DB::table('feature_flags')->insert([
            ['key' => 'deposits_enabled', 'is_active' => true, 'description' => 'System-wide deposit kill switch'],
            ['key' => 'withdrawals_enabled', 'is_active' => true, 'description' => 'System-wide withdrawal kill switch'],
            ['key' => 'refunds_enabled', 'is_active' => true, 'description' => 'System-wide refund kill switch'],
            ['key' => 'new_registrations_enabled', 'is_active' => true, 'description' => 'System-wide new registrations switch'],
            ['key' => 'kyc_required', 'is_active' => true, 'description' => 'Is KYC required for operations?'],
            ['key' => 'pix_ownership_required', 'is_active' => true, 'description' => 'Is PIX ownership validation required?'],
            ['key' => 'beta_mode', 'is_active' => true, 'description' => 'System is operating in Beta mode'],
            ['key' => 'maintenance_financial_mode', 'is_active' => false, 'description' => 'Strict maintenance mode'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_flags');
        Schema::dropIfExists('pix_keys');
    }
};
