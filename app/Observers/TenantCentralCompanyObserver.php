<?php

namespace App\Observers;

use App\Models\Tenant\Company;
use App\Services\System\TenantCentralMetricsSyncService;
use Illuminate\Support\Facades\DB;

class TenantCentralCompanyObserver
{
    public function updated(Company $company): void
    {
        if (!$company->wasChanged('soap_type_id')) {
            return;
        }

        DB::connection($company->getConnectionName())->afterCommit(function () use ($company) {
            app(TenantCentralMetricsSyncService::class)->syncCompanySoapType($company);
        });
    }
}
