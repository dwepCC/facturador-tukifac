<?php

namespace App\Observers;

use App\Models\Tenant\SaleNote;
use App\Services\System\TenantCentralMetricsSyncService;
use Illuminate\Support\Facades\DB;

class TenantCentralSaleNoteObserver
{
    public function created(SaleNote $saleNote): void
    {
        DB::connection($saleNote->getConnectionName())->afterCommit(function () use ($saleNote) {
            app(TenantCentralMetricsSyncService::class)->syncSaleNote($saleNote);
        });
    }

    public function updated(SaleNote $saleNote): void
    {
        DB::connection($saleNote->getConnectionName())->afterCommit(function () use ($saleNote) {
            app(TenantCentralMetricsSyncService::class)->syncSaleNote($saleNote);
        });
    }

    public function deleted(SaleNote $saleNote): void
    {
        DB::connection($saleNote->getConnectionName())->afterCommit(function () use ($saleNote) {
            app(TenantCentralMetricsSyncService::class)->removeSaleNote($saleNote);
        });
    }
}
