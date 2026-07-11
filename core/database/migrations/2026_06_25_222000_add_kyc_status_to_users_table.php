<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE users ADD COLUMN IF NOT EXISTS kyc_status integer NOT NULL DEFAULT 3");
            DB::statement("ALTER TABLE users ADD COLUMN IF NOT EXISTS kyc_type varchar(16) NULL");
            DB::statement("ALTER TABLE users ADD COLUMN IF NOT EXISTS kyc_reason text NULL");
            DB::statement("COMMENT ON COLUMN users.kyc_status IS '0=Pending, 1=Approved, 2=Rejected, 3=Not Started, 4=Draft'");
            DB::statement("COMMENT ON COLUMN users.kyc_type IS 'pf or pj'");
            DB::statement("COMMENT ON COLUMN users.kyc_reason IS 'Reason if rejected'");

            return;
        }

        if (!Schema::hasColumn('users', 'kyc_status')) {
            Schema::table('users', function (Blueprint $table) {
                $table->integer('kyc_status')->default(3)->after('status')->comment('0=Pending, 1=Approved, 2=Rejected, 3=Not Started, 4=Draft');
            });
        }

        if (!Schema::hasColumn('users', 'kyc_type')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('kyc_type', 16)->nullable()->after('kyc_status')->comment('pf or pj');
            });
        }

        if (!Schema::hasColumn('users', 'kyc_reason')) {
            Schema::table('users', function (Blueprint $table) {
                $table->text('kyc_reason')->nullable()->after('kyc_type')->comment('Reason if rejected');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $columns = array_values(array_filter(
            ['kyc_status', 'kyc_type', 'kyc_reason'],
            fn (string $column) => Schema::hasColumn('users', $column)
        ));

        if ($columns !== []) {
            Schema::table('users', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
};
