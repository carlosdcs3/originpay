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
        Schema::table('connect_segments', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
            $table->text('description')->nullable()->after('name');
            $table->json('rules')->nullable()->after('description');
            $table->boolean('is_dynamic')->default(true)->after('rules');
        });

        // Migrate UUIDs and JSON rules if any existing rules
        $segments = DB::table('connect_segments')->get();
        foreach ($segments as $segment) {
            // Find old rules
            $oldRules = DB::table('connect_segment_rules')->where('segment_id', $segment->id)->get();
            $newRules = [];
            foreach ($oldRules as $r) {
                $newRules[] = [
                    'field' => $r->rule_type,
                    'operator' => $r->operator,
                    'value' => $r->rule_value
                ];
            }
            $payload = [
                'version' => 1,
                'condition' => 'and',
                'rules' => $newRules
            ];

            DB::table('connect_segments')
                ->where('id', $segment->id)
                ->update([
                    'uuid' => Str::uuid()->toString(),
                    'rules' => json_encode($payload)
                ]);
        }

        // Strict constraints
        Schema::table('connect_segments', function (Blueprint $table) {
            $table->string('uuid')->nullable(false)->unique()->change();
        });

        Schema::dropIfExists('connect_segment_rules');
    }

    public function down()
    {
        Schema::create('connect_segment_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('segment_id')->constrained('connect_segments')->nullOnDelete();
            $table->string('rule_type');
            $table->string('rule_value');
            $table->string('operator')->default('equals');
            $table->timestamps();
        });

        Schema::table('connect_segments', function (Blueprint $table) {
            $table->dropColumn(['uuid', 'description', 'rules', 'is_dynamic']);
        });
    }
};
