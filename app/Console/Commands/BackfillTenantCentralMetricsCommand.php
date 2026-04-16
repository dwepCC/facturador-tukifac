<?php

namespace App\Console\Commands;

use App\Models\System\Client;
use App\Models\System\ClientCentralDocument;
use App\Models\System\ClientCentralSaleNote;
use App\Services\System\TenantCentralMetricsSyncService;
use Hyn\Tenancy\Environment;
use Illuminate\Console\Command;

class BackfillTenantCentralMetricsCommand extends Command
{
    protected $signature = 'tenant-metrics:central-backfill
                            {--client_id= : Solo un cliente (id system)}
                            {--with-entity-history : Tras documentos/NV, reconstruye historial usuarios/sucursales (tenant_metric_history)}';

    protected $description = 'Replica documentos/NV y contadores desde cada tenant hacia las tablas centrales de métricas';

    public function handle(): int
    {
        $sync = app(TenantCentralMetricsSyncService::class);
        $tenancy = app(Environment::class);

        $q = Client::query()->with(['hostname.website', 'plan']);
        if ($this->option('client_id')) {
            $q->where('id', (int) $this->option('client_id'));
        }

        $clients = $q->get();
        foreach ($clients as $client) {
            if (!$client->hostname || !$client->hostname->website) {
                $this->warn("Cliente {$client->id} sin website, omitido.");
                continue;
            }

            $this->info("Backfill cliente {$client->id} ({$client->name})...");
            // En consola hace falta hostname + website: solo tenant() no registra CurrentHostname.
            $tenancy->hostname($client->hostname);
            $tenancy->tenant($client->hostname->website);

            $sync->backfillClientFromTenant((int) $client->id);

            $docs = ClientCentralDocument::query()->where('client_id', $client->id)->count();
            $sns = ClientCentralSaleNote::query()->where('client_id', $client->id)->count();
            $this->line("  → client_central_documents: {$docs}, client_central_sale_notes: {$sns}");

            if ($this->option('with-entity-history')) {
                $h = $sync->rebuildUsersAndEstablishmentsHistoryFromTenant((int) $client->id, false);
                $this->line("  → historial entidades: eliminadas={$h['history_deleted']} filas, created usuarios={$h['users']}, sucursales={$h['establishments']}");
            }
        }

        $this->info('Listo.');

        return self::SUCCESS;
    }
}
