<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withdrawal_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('withdrawal_id')->constrained('withdrawal_requests');
            $table->foreignId('user_id')->nullable()->constrained();
            $table->unsignedBigInteger('admin_id')->nullable();
            
            $table->string('action'); // request, approve, reject, cancel, process, fail, complete
            $table->text('reason')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->json('metadata')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawal_audits');
    }
};
