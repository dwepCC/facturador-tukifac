<?php

namespace App\Observers;

use App\Models\Tenant\User;
use App\Services\System\TenantCentralMetricsSyncService;
use Illuminate\Support\Facades\DB;

class TenantCentralUserObserver
{
    public function created(User $user): void
    {
        DB::connection($user->getConnectionName())->afterCommit(function () use ($user) {
            $svc = app(TenantCentralMetricsSyncService::class);
            $clientId = $svc->resolveClientId();
            if ($clientId) {
                $svc->appendTenantMetricHistoryEvent(
                    $clientId,
                    'users',
                    'created',
                    'tenant_user',
                    (int) $user->getKey(),
                    array_filter([
                        'email' => $user->email ?? null,
                        'name' => $user->name ?? null,
                    ])
                );
            }
            $svc->refreshUserAndEstablishmentCounts();
        });
    }

    public function deleted(User $user): void
    {
        DB::connection($user->getConnectionName())->afterCommit(function () use ($user) {
            $svc = app(TenantCentralMetricsSyncService::class);
            $clientId = $svc->resolveClientId();
            if ($clientId) {
                $svc->appendTenantMetricHistoryEvent(
                    $clientId,
                    'users',
                    'deleted',
                    'tenant_user',
                    (int) $user->getKey(),
                    array_filter([
                        'email' => $user->email ?? null,
                        'name' => $user->name ?? null,
                    ])
                );
            }
            $svc->refreshUserAndEstablishmentCounts();
        });
    }
}
