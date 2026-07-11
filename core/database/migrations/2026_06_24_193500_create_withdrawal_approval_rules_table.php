<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('withdrawal_approval_rules', function (Blueprint $table) {
            $table->id();
            $table->decimal('min_amount', 28, 8)->default(0);
            $table->decimal('max_amount', 28, 8)->nullable();
            $table->string('approval_mode')->default('AUTO'); // AUTO, ADMIN, DUAL_APPROVAL
            $table->string('required_role')->nullable(); // FINANCE_ANALYST, FINANCE_ADMIN, SUPER_ADMIN
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('withdrawal_approval_rules');
    }
};
