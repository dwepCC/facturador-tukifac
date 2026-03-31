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
            Schema::create('client_document_packages', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('client_id');
                $table->unsignedInteger('units_total');
                $table->unsignedInteger('units_consumed')->default(0);
                $table->boolean('include_sale_notes')->nullable();
                $table->date('cycle_start_at');
                $table->date('cycle_end_at');
                $table->string('status', 20)->default('active');
                $table->timestamps();

                $table->index(['client_id', 'status'], 'cdp_client_status_idx');
                $table->index(['client_id', 'cycle_start_at', 'cycle_end_at'], 'cdp_client_cycle_idx');
                $table->index(['client_id', 'status', 'cycle_end_at'], 'cdp_client_status_end_idx');
            });
            return;
        }

        $rawIndexes = DB::select("SHOW INDEX FROM `client_document_packages`");
        $indexColumnsByName = [];

        foreach ($rawIndexes as $row) {
            $keyName = $row->Key_name ?? null;
            $colName = $row->Column_name ?? null;
            $seq = (int) ($row->Seq_in_index ?? 0);

            if (!$keyName || !$colName) {
                continue;
            }

            if (!array_key_exists($keyName, $indexColumnsByName)) {
                $indexColumnsByName[$keyName] = [];
            }

            $indexColumnsByName[$keyName][$seq] = $colName;
        }

        foreach ($indexColumnsByName as $keyName => $colsBySeq) {
            ksort($colsBySeq);
            $indexColumnsByName[$keyName] = array_values($colsBySeq);
        }

        $hasIndexWithColumns = function (array $expected) use ($indexColumnsByName): bool {
            foreach ($indexColumnsByName as $keyName => $cols) {
                if ($keyName === 'PRIMARY') {
                    continue;
                }
                if ($cols === $expected) {
                    return true;
                }
            }
            return false;
        };

        Schema::table('client_document_packages', function (Blueprint $table) use ($hasIndexWithColumns) {
            if (!$hasIndexWithColumns(['client_id', 'status'])) {
                $table->index(['client_id', 'status'], 'cdp_client_status_idx');
            }
            if (!$hasIndexWithColumns(['client_id', 'cycle_start_at', 'cycle_end_at'])) {
                $table->index(['client_id', 'cycle_start_at', 'cycle_end_at'], 'cdp_client_cycle_idx');
            }
            if (!$hasIndexWithColumns(['client_id', 'status', 'cycle_end_at'])) {
                $table->index(['client_id', 'status', 'cycle_end_at'], 'cdp_client_status_end_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_document_packages');
    }
};

