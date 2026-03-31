<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('client_document_packages') || !Schema::hasColumn('client_document_packages', 'price')) {
            return;
        }

        DB::table('client_document_packages')
            ->whereIn('units_total', [50, 100, 200])
            ->update([
                'price' => DB::raw("CASE
                    WHEN units_total = 50 THEN 10
                    WHEN units_total = 100 THEN 15
                    WHEN units_total = 200 THEN 30
                    ELSE price
                END"),
            ]);
    }

    public function down(): void
    {
    }
};
