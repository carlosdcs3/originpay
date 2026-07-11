<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->decimal('rolling_reserve_balance', 18, 8)->default(0.00)->after('reserved_balance');
        });

        Schema::create('kyc_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->unsignedTinyInteger('level')->default(0); // 0, 1, 2, 3
            $table->string('status', 40)->default('PENDING'); // PENDING, APPROVED, REJECTED, UNDER_REVIEW
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('kyc_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('document_type'); // cpf, rg, selfie
            $table->string('storage_path');
            $table->string('checksum')->nullable();
            $table->timestamp('uploaded_at')->useCurrent();
            $table->timestamps();
        });

        Schema::create('kyc_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reviewer_id')->constrained('users');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('action'); // UPGRADE, REJECT, REQUEST_DOCS
            $table->text('notes')->nullable();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->timestamps();
        });

        Schema::create('rolling_reserves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('wallet_id')->constrained('wallets')->onDelete('cascade');
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->decimal('amount', 18, 8);
            $table->string('status', 40)->default('HELD'); // HELD, RELEASED
            $table->timestamp('release_at');
            $table->timestamp('released_at')->nullable();
            $table->timestamps();
        });

        Schema::create('user_financial_limits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade');
            $table->decimal('max_balance', 18, 8)->nullable();
            $table->decimal('max_daily_withdraw', 18, 8)->nullable();
            $table->decimal('max_monthly_volume', 18, 8)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_financial_limits');
        Schema::dropIfExists('rolling_reserves');
        Schema::dropIfExists('kyc_reviews');
        Schema::dropIfExists('kyc_documents');
        Schema::dropIfExists('kyc_profiles');
        Schema::table('wallets', function (Blueprint $table) {
            $table->dropColumn('rolling_reserve_balance');
        });
    }
};
