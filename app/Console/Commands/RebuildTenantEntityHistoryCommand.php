<?php

namespace App\Console\Commands;

use App\Models\System\Client;
use App\Services\System\TenantCentralMetricsSyncService;
use Hyn\Tenancy\Environment;
use Illuminate\Console\Command;
use Throwable;

class RebuildTenantEntityHistoryCommand extends Command
{
    protected $signature = 'tenant-metrics:rebuild-entity-history
                            {--client_id= : Solo un cliente (id en base system)}
                            {--dry-run : Solo mostrar conteos sin escribir}
                            {--continue-on-error : Seguir con el siguiente tenant si uno falla}';

    protected $description = 'Reconstruye tenant_metric_history (altas de usuarios y sucursales) desde cada tenant; elimina el historial previo de esas métricas por cliente y refresca contadores centrales.';

    public function handle(): int
    {
        $sync = app(TenantCentralMetricsSyncService::class);
        $tenancy = app(Environment::class);

        $q = Client::query()->with(['hostname.website']);
        if ($this->option('client_id')) {
            $q->where('id', (int) $this->option('client_id'));
        }

        $clients = $q->orderBy('id')->get();
        $dryRun = (bool) $this->option('dry-run');
        $continue = (bool) $this->option('continue-on-error');

        if ($dryRun) {
            $this->warn('Modo dry-run: no se escribe en tenant_metric_history ni se borra historial.');
        } else {
            $this->warn('Se eliminarán filas de historial (users/establishments) por cliente y se insertarán eventos "created" desde created_at del tenant.');
        }

        foreach ($clients as $client) {
            if (!$client->hostname || !$client->hostname->website) {
                $this->warn("Cliente {$client->id} sin website, omitido.");
                continue;
            }

            $this->info("Cliente {$client->id} ({$client->name})...");

            try {
                $tenancy->hostname($client->hostname);
                $tenancy->tenant($client->hostname->website);

                $stats = $sync->rebuildUsersAndEstablishmentsHistoryFromTenant((int) $client->id, $dryRun);

                if ($dryRun) {
                    $this->line("  → dry-run: usuarios={$stats['users']}, sucursales={$stats['establishments']}");
                    continue;
                }

                $this->line("  → historial eliminado: {$stats['history_deleted']} filas; insertados created: usuarios={$stats['users']}, sucursales={$stats['establishments']}");
            } catch (Throwable $e) {
                $this->error("  → Error cliente {$client->id}: {$e->getMessage()}");
                if (!$continue) {
                    return self::FAILURE;
                }
            }
        }

        $this->info('Listo.');

        return self::SUCCESS;
    }
}
