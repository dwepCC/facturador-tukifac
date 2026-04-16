<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $c = 'system';
        if (!Schema::connection($c)->hasTable('tenant_metric_history')) {
            return;
        }
        Schema::connection($c)->table('tenant_metric_history', function (Blueprint $table) {
            $table->index(['client_id', 'metric_type', 'event_type', 'event_date'], 'tmh_client_metric_event_date_idx');
        });
    }

    public function down(): void
    {
        $c = 'system';
        if (!Schema::connection($c)->hasTable('tenant_metric_history')) {
            return;
        }
        Schema::connection($c)->table('tenant_metric_history', function (Blueprint $table) {
            $table->dropIndex('tmh_client_metric_event_date_idx');
        });
    }
};
