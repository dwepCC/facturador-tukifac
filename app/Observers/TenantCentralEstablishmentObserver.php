<?php

namespace App\Observers;

use App\Models\Tenant\Establishment;
use App\Services\System\TenantCentralMetricsSyncService;
use Illuminate\Support\Facades\DB;

class TenantCentralEstablishmentObserver
{
    public function created(Establishment $establishment): void
    {
        DB::connection($establishment->getConnectionName())->afterCommit(function () use ($establishment) {
            $svc = app(TenantCentralMetricsSyncService::class);
            $clientId = $svc->resolveClientId();
            if ($clientId) {
                $svc->appendTenantMetricHistoryEvent(
                    $clientId,
                    'establishments',
                    'created',
                    'tenant_establishment',
                    (int) $establishment->getKey(),
                    array_filter([
                        'description' => $establishment->description ?? null,
                        'code' => $establishment->code ?? null,
                    ])
                );
            }
            $svc->refreshUserAndEstablishmentCounts();
        });
    }

    public function deleted(Establishment $establishment): void
    {
        DB::connection($establishment->getConnectionName())->afterCommit(function () use ($establishment) {
            $svc = app(TenantCentralMetricsSyncService::class);
            $clientId = $svc->resolveClientId();
            if ($clientId) {
                $svc->appendTenantMetricHistoryEvent(
                    $clientId,
                    'establishments',
                    'deleted',
                    'tenant_establishment',
                    (int) $establishment->getKey(),
                    array_filter([
                        'description' => $establishment->description ?? null,
                        'code' => $establishment->code ?? null,
                    ])
                );
            }
            $svc->refreshUserAndEstablishmentCounts();
        });
    }
}
