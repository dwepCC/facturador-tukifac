<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('client_document_packages')) {
            return;
        }

        Schema::table('client_document_packages', function (Blueprint $table) {
            if (!Schema::hasColumn('client_document_packages', 'price')) {
                $table->decimal('price', 10, 2)->nullable()->after('units_total');
            }
        });

        if (Schema::hasColumn('client_document_packages', 'price')) {
            DB::table('client_document_packages')
                ->whereNull('price')
                ->update([
                    'price' => DB::raw("CASE
                        WHEN units_total = 100 THEN 10
                        WHEN units_total = 200 THEN 18
                        WHEN units_total = 1500 THEN 120
                        ELSE 0
                    END"),
                ]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('client_document_packages')) {
            return;
        }

        Schema::table('client_document_packages', function (Blueprint $table) {
            if (Schema::hasColumn('client_document_packages', 'price')) {
                $table->dropColumn('price');
            }
        });
    }
};
