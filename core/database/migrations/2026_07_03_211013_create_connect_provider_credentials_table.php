<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('connect_provider_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained('users')->cascadeOnDelete();
            $table->string('channel'); // email, whatsapp, sms
            $table->string('provider'); // aws_ses, resend, meta_cloud, twilio
            $table->text('credentials'); // Encrypted
            $table->json('configuration')->nullable(); // region, sandbox, etc
            
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('priority')->default(1);
            
            $table->timestamp('last_success_at')->nullable();
            $table->timestamp('last_failure_at')->nullable();
            $table->unsignedInteger('success_count')->default(0);
            $table->unsignedInteger('failure_count')->default(0);
            $table->decimal('health_score', 5, 2)->default(100.00);
            $table->text('last_error')->nullable();

            $table->timestamps();
            
            $table->index(['merchant_id', 'channel', 'is_active']);
        });

        // Add extensive audit fields to Delivery Attempts
        Schema::table('connect_campaign_delivery_attempts', function (Blueprint $table) {
            $table->unsignedInteger('priority')->default(1)->after('provider');
            $table->unsignedInteger('attempt_number')->default(1)->after('priority');
            $table->string('driver_class')->nullable()->after('attempt_number');
            $table->string('adapter_class')->nullable()->after('driver_class');
        });
    }

    public function down()
    {
        Schema::table('connect_campaign_delivery_attempts', function (Blueprint $table) {
            $table->dropColumn(['priority', 'attempt_number', 'driver_class', 'adapter_class']);
        });
        Schema::dropIfExists('connect_provider_credentials');
    }
};
