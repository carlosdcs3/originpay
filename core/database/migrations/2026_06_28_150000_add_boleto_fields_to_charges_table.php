<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('charges')) {
            return;
        }

        Schema::table('charges', function (Blueprint $table) {
            if (! Schema::hasColumn('charges', 'gateway_reference')) {
                $table->string('gateway_reference')->nullable()->after('gateway_charge_id')->index();
            }

            if (! Schema::hasColumn('charges', 'boleto_url')) {
                $table->string('boleto_url')->nullable()->after('payment_link');
            }

            if (! Schema::hasColumn('charges', 'boleto_pdf_url')) {
                $table->string('boleto_pdf_url')->nullable()->after('boleto_url');
            }

            if (! Schema::hasColumn('charges', 'barcode')) {
                $table->string('barcode')->nullable()->after('boleto_pdf_url');
            }

            if (! Schema::hasColumn('charges', 'digitable_line')) {
                $table->string('digitable_line')->nullable()->after('barcode');
            }

            if (! Schema::hasColumn('charges', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('expires_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('charges')) {
            return;
        }

        Schema::table('charges', function (Blueprint $table) {
            foreach (['gateway_reference', 'boleto_url', 'boleto_pdf_url', 'barcode', 'digitable_line', 'paid_at'] as $column) {
                if (Schema::hasColumn('charges', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
