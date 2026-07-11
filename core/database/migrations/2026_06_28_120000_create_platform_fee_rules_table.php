<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('platform_fee_rules')) {
            Schema::create('platform_fee_rules', function (Blueprint $table) {
                $table->id();
                $table->string('scope', 20)->default('global');
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('payment_method', 30);
                $table->string('currency', 3)->default('BRL');
                $table->decimal('fixed_fee', 28, 8)->default(0);
                $table->decimal('percentage_fee', 8, 4)->default(0);
                $table->decimal('minimum_fee', 28, 8)->nullable();
                $table->decimal('maximum_fee', 28, 8)->nullable();
                $table->unsignedInteger('settlement_delay_days')->default(0);
                $table->decimal('reserve_percentage', 8, 4)->default(0);
                $table->string('status', 20)->default('active');
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->json('metadata')->nullable();
                $table->foreignId('created_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
                $table->foreignId('updated_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
                $table->timestamps();

                $table->index(['scope', 'payment_method', 'currency', 'status'], 'pfr_scope_method_currency_status_idx');
                $table->index(['user_id', 'payment_method', 'currency', 'status'], 'pfr_user_method_currency_status_idx');
                $table->index(['starts_at', 'ends_at'], 'pfr_active_window_idx');
            });
        }

        if (Schema::hasTable('charges')) {
            Schema::table('charges', function (Blueprint $table) {
                if (! Schema::hasColumn('charges', 'fee_rule_id')) {
                    $table->foreignId('fee_rule_id')->nullable()->after('gateway_fee')->constrained('platform_fee_rules')->nullOnDelete();
                }

                if (! Schema::hasColumn('charges', 'fee_snapshot')) {
                    $table->json('fee_snapshot')->nullable()->after('fee_rule_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('charges')) {
            Schema::table('charges', function (Blueprint $table) {
                if (Schema::hasColumn('charges', 'fee_rule_id')) {
                    $table->dropConstrainedForeignId('fee_rule_id');
                }

                if (Schema::hasColumn('charges', 'fee_snapshot')) {
                    $table->dropColumn('fee_snapshot');
                }
            });
        }

        Schema::dropIfExists('platform_fee_rules');
    }
};
