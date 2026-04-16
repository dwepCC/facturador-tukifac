<?php

namespace App\Listeners\Tenancy;

use App\Services\System\TenantStoragePermissionService;
use Hyn\Tenancy\Events\Filesystem\DirectoryCreated;

class EnsureTenantStoragePermissions
{
    /**
     * Tras crear el directorio del website en storage (tenancy), normaliza permisos.
     */
    public function handle(DirectoryCreated $event): void
    {
        $uuid = $event->website->uuid ?? null;
        if (!is_string($uuid) || $uuid === '') {
            return;
        }

        TenantStoragePermissionService::applyToTenantUuid($uuid);
    }
}
