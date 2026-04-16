<?php

namespace App\Console\Commands;

use App\Models\System\Client;
use App\Services\System\TenantStoragePermissionService;
use Illuminate\Console\Command;

/**
 * Reparación puntual (p. ej. una vez en producción) de permisos en
 * storage/app/tenancy/tenants/{uuid} para clientes ya existentes.
 */
class FixTenantStoragePermissionsCommand extends Command
{
    protected $signature = 'tenants:fix-storage-permissions
                            {--client_id= : Solo el cliente (id en base system)}
                            {--dry-run : Solo listar UUID/carpetas, sin cambiar permisos}';

    protected $description = 'Aplica permisos 0755/0644 (Unix) a las carpetas de almacenamiento de cada tenant existente';

    public function handle(): int
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->warn('En Windows chmod no aplica igual; el comando solo verificará rutas. En Linux producción es donde corrige permisos.');
        }

        $root = TenantStoragePermissionService::tenantStorageRoot();
        $this->line("Raíz tenancy: {$root}");

        $q = Client::query()->with(['hostname.website']);
        if ($this->option('client_id')) {
            $q->where('id', (int) $this->option('client_id'));
        }

        $clients = $q->get();
        if ($clients->isEmpty()) {
            $this->warn('No hay clientes que coincidan con el filtro.');

            return self::SUCCESS;
        }

        $dry = (bool) $this->option('dry-run');
        $processed = 0;
        $skipped = 0;
        $missing = 0;

        foreach ($clients as $client) {
            if (!$client->hostname || !$client->hostname->website) {
                $this->warn("Cliente {$client->id} ({$client->name}): sin hostname/website, omitido.");
                $skipped++;

                continue;
            }

            $uuid = (string) $client->hostname->website->uuid;
            $path = $root . DIRECTORY_SEPARATOR . $uuid;

            if (!is_dir($path)) {
                $this->warn("Cliente {$client->id}: no existe carpeta [{$uuid}]");
                $missing++;

                continue;
            }

            if ($dry) {
                $this->line("DRY-RUN cliente {$client->id} → {$path}");
                $processed++;

                continue;
            }

            TenantStoragePermissionService::applyToTenantUuid($uuid);
            $this->info("Permisos aplicados cliente {$client->id} ({$client->name}) → {$uuid}");
            $processed++;
        }

        $this->newLine();
        $labelOk = $dry ? 'Carpetas encontradas (sin cambiar permisos)' : 'Carpetas actualizadas';
        $this->table(
            ['Métrica', 'Cantidad'],
            [
                [$labelOk, (string) $processed],
                ['Omitidos (sin website)', (string) $skipped],
                ['Sin carpeta en storage', (string) $missing],
            ]
        );

        if ($dry) {
            $this->comment('Modo dry-run: no se modificaron permisos. Quite --dry-run para aplicar chmod en Unix.');
        }

        return self::SUCCESS;
    }
}
