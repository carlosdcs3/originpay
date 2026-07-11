<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('platform_fee_rule_audits')) {
            Schema::create('platform_fee_rule_audits', function (Blueprint $table) {
                $table->id();
                $table->foreignId('platform_fee_rule_id')->nullable()->constrained('platform_fee_rules')->nullOnDelete();
                $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
                $table->string('action', 30);
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->string('reason')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamps();

                $table->index(['platform_fee_rule_id', 'action'], 'pfra_rule_action_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_fee_rule_audits');
    }
};
