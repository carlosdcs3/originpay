<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up()
    {
        Schema::table('connect_templates', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
            $table->string('content_format')->default('ast')->after('content');
            $table->json('metadata')->nullable()->after('content_format');
            $table->json('dimensions')->nullable()->after('metadata');
            $table->json('variables')->nullable()->after('dimensions');
            $table->integer('version')->default(1)->after('variables');
            $table->boolean('is_current')->default(true)->after('version');
            $table->unsignedBigInteger('parent_template_id')->nullable()->after('is_current');
            $table->timestamp('published_at')->nullable()->after('parent_template_id');
            $table->unsignedBigInteger('created_by')->nullable()->after('published_at');
            $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');

            $table->foreign('parent_template_id')->references('id')->on('connect_templates')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });

        // Initialize UUIDs safely
        $templates = DB::table('connect_templates')->get();
        foreach ($templates as $t) {
            DB::table('connect_templates')->where('id', $t->id)->update([
                'uuid' => Str::uuid()->toString()
            ]);
        }

        Schema::table('connect_templates', function (Blueprint $table) {
            $table->string('uuid')->nullable(false)->unique()->change();
        });
    }

    public function down()
    {
        Schema::table('connect_templates', function (Blueprint $table) {
            $table->dropForeign(['parent_template_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            $table->dropColumn([
                'uuid', 'content_format', 'metadata', 'dimensions', 'variables',
                'version', 'is_current', 'parent_template_id', 'published_at',
                'created_by', 'updated_by'
            ]);
        });
    }
};
