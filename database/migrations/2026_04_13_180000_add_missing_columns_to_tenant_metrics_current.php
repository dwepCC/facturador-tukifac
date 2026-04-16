<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $c = 'system';

        if (!Schema::connection($c)->hasTable('tenant_metrics_current')) {
            return;
        }

        Schema::connection($c)->table('tenant_metrics_current', function (Blueprint $table) use ($c) {
            if (!Schema::connection($c)->hasColumn('tenant_metrics_current', 'monthly_sales_total_cached')) {
                $table->decimal('monthly_sales_total_cached', 18, 2)->default(0);
            }
            if (!Schema::connection($c)->hasColumn('tenant_metrics_current', 'metrics_last_synced_at')) {
                $table->timestamp('metrics_last_synced_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        $c = 'system';

        if (!Schema::connection($c)->hasTable('tenant_metrics_current')) {
            return;
        }

        Schema::connection($c)->table('tenant_metrics_current', function (Blueprint $table) use ($c) {
            if (Schema::connection($c)->hasColumn('tenant_metrics_current', 'metrics_last_synced_at')) {
                $table->dropColumn('metrics_last_synced_at');
            }
            if (Schema::connection($c)->hasColumn('tenant_metrics_current', 'monthly_sales_total_cached')) {
                $table->dropColumn('monthly_sales_total_cached');
            }
        });
    }
};
