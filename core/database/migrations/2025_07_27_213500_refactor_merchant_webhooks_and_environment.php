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
        Schema::table('merchants', function (Blueprint $table) {
            // Drop the test_webhook_urls field since we'll use single webhook_url
            if (Schema::hasColumn('merchants', 'test_webhook_urls')) {
                $table->dropColumn('test_webhook_urls');
            }
            
            // Add single webhook_url field for all environments
            if (!Schema::hasColumn('merchants', 'webhook_url')) {
                $table->string('webhook_url')->nullable()->after('sandbox_enabled');
            }
        });

        if ($this->indexExists('merchants', 'merchants_current_mode_index')) {
            Schema::table('merchants', function (Blueprint $table) {
                $table->dropIndex('merchants_current_mode_index');
            });
        }

        Schema::table('merchants', function (Blueprint $table) {
            if (Schema::hasColumn('merchants', 'current_mode')) {
                $table->dropColumn('current_mode');
            }
        });

        Schema::table('merchants', function (Blueprint $table) {
            if (!Schema::hasColumn('merchants', 'current_mode')) {
                $table->enum('current_mode', ['sandbox', 'production'])->default('sandbox')->after('webhook_url');
            }

            if (!$this->indexExists('merchants', 'merchants_webhook_url_index')) {
                $table->index('webhook_url');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            // Restore test_webhook_urls field
            if (!Schema::hasColumn('merchants', 'test_webhook_urls')) {
                $table->text('test_webhook_urls')->nullable()->after('sandbox_enabled');
            }
            
            // Remove webhook_url field
            if ($this->indexExists('merchants', 'merchants_webhook_url_index')) {
                $table->dropIndex('merchants_webhook_url_index');
            }
            if (Schema::hasColumn('merchants', 'webhook_url')) {
                $table->dropColumn('webhook_url');
            }
        });

        if ($this->indexExists('merchants', 'merchants_current_mode_index')) {
            Schema::table('merchants', function (Blueprint $table) {
                $table->dropIndex('merchants_current_mode_index');
            });
        }

        Schema::table('merchants', function (Blueprint $table) {
            if (Schema::hasColumn('merchants', 'current_mode')) {
                $table->dropColumn('current_mode');
            }
        });

        Schema::table('merchants', function (Blueprint $table) {
            if (!Schema::hasColumn('merchants', 'current_mode')) {
                $table->enum('current_mode', ['production', 'sandbox'])->default('sandbox');
            }

            if (!$this->indexExists('merchants', 'merchants_current_mode_index')) {
                $table->index('current_mode');
            }
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        return collect(Schema::getIndexes($table))->contains(
            fn (array $index) => ($index['name'] ?? null) === $indexName
        );
    }
};
