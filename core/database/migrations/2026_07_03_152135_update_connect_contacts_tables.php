<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // 1. Add missing columns to connect_contacts
        Schema::table('connect_contacts', function (Blueprint $table) {
            $table->string('whatsapp')->nullable()->after('phone');
            $table->string('country')->nullable()->after('whatsapp');
            $table->string('language')->nullable()->after('country');
            $table->string('timezone')->nullable()->after('language');
            $table->string('source')->nullable()->after('timezone');
            $table->text('notes')->nullable()->after('status');
            
            // Unique constraints (email & phone already have unique from Epic 1)
            $table->unique(['merchant_id', 'whatsapp']);
            $table->index('whatsapp');
            $table->index('source');
        });

        // 2. Create connect_tags
        Schema::create('connect_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('color')->nullable();
            $table->timestamps();
            
            $table->unique(['merchant_id', 'name']);
        });

        // 3. Migrate connect_contact_tags
        // Add tag_id
        Schema::table('connect_contact_tags', function (Blueprint $table) {
            $table->unsignedBigInteger('tag_id')->nullable()->after('contact_id');
        });

        // Data migration query (creates tags in connect_tags if missing, and assigns tag_id)
        // using Laravel DB query builder for safe idempotent execution
        $tags = DB::table('connect_contact_tags')->select('merchant_id', 'tag_name')->distinct()->get();
        foreach ($tags as $tag) {
            if ($tag->tag_name) {
                $insertedId = DB::table('connect_tags')->insertGetId([
                    'merchant_id' => $tag->merchant_id,
                    'name' => $tag->tag_name,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                DB::table('connect_contact_tags')
                    ->where('merchant_id', $tag->merchant_id)
                    ->where('tag_name', $tag->tag_name)
                    ->update(['tag_id' => $insertedId]);
            }
        }

        // Cleanup and strict constraints on connect_contact_tags
        Schema::table('connect_contact_tags', function (Blueprint $table) {
            // Drop old index/unique containing tag_name
            $table->dropUnique(['merchant_id', 'contact_id', 'tag_name']);
            // Drop old column
            $table->dropColumn('tag_name');
            
            // New strict constraint
            $table->unique(['contact_id', 'tag_id']);
            
            $table->foreign('tag_id')->references('id')->on('connect_tags')->cascadeOnDelete();
            // Assuming contact_id is already a FK or indexed. Let's add FK if missing.
            $table->foreign('contact_id')->references('id')->on('connect_contacts')->cascadeOnDelete();
        });

        // 4. Create connect_contact_custom_fields
        Schema::create('connect_contact_custom_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained('connect_contacts')->cascadeOnDelete();
            $table->string('field_name');
            $table->text('field_value')->nullable();
            $table->timestamps();

            $table->unique(['contact_id', 'field_name']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('connect_contact_custom_fields');
        
        Schema::table('connect_contact_tags', function (Blueprint $table) {
            $table->dropForeign(['contact_id']);
            $table->dropForeign(['tag_id']);
            $table->dropUnique(['contact_id', 'tag_id']);
            $table->string('tag_name')->nullable();
        });
        
        Schema::dropIfExists('connect_tags');
        
        Schema::table('connect_contacts', function (Blueprint $table) {
            $table->dropUnique(['merchant_id', 'whatsapp']);
            $table->dropIndex(['whatsapp']);
            $table->dropIndex(['source']);
            $table->dropColumn(['whatsapp', 'country', 'language', 'timezone', 'source', 'notes']);
        });
    }
};
