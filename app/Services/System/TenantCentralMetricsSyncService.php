<?php

namespace App\Services\System;

use App\Models\System\Client;
use App\Models\System\ClientCentralDocument;
use App\Models\System\ClientCentralSaleNote;
use App\Models\System\TenantMetricHistory;
use App\Models\System\TenantMetricsCurrent;
use App\Models\Tenant\Company;
use App\Models\Tenant\Document;
use App\Models\Tenant\Establishment;
use App\Models\Tenant\SaleNote;
use App\Models\Tenant\User;
use Carbon\Carbon;
use Hyn\Tenancy\Environment;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Document\Helpers\DocumentHelper;

class TenantCentralMetricsSyncService
{
    /**
     * En consola / jobs, {@see Environment::hostname()} puede ser null aunque el tenant DB esté activo.
     * El backfill fija este id para que todas las escrituras centrales usen el cliente correcto.
     */
    private ?int $contextClientId = null;

    public function resolveClientId(): ?int
    {
        if ($this->contextClientId !== null) {
            return $this->contextClientId;
        }

        $hostname = app(Environment::class)->hostname();
        if (!$hostname) {
            return null;
        }

        return Client::query()->where('hostname_id', $hostname->id)->value('id');
    }

    /**
     * Registro append-only en base central para auditoría y agregados por rango de fechas (usuarios, sucursales, etc.).
     */
    public function appendTenantMetricHistoryEvent(
        int $clientId,
        string $metricType,
        string $eventType,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?array $metadata = null,
        ?Carbon $eventDate = null
    ): void {
        try {
            $meta = array_filter($metadata ?? []);
            $meta['source'] = 'live';

            TenantMetricHistory::query()->create([
                'client_id' => $clientId,
                'metric_type' => $metricType,
                'event_type' => $eventType,
                'value' => 1,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'event_date' => $eventDate ?? Carbon::now(),
                'metadata' => $meta,
            ]);
        } catch (\Throwable $e) {
            Log::warning('TenantCentralMetricsSyncService::appendTenantMetricHistoryEvent failed', [
                'client_id' => $clientId,
                'metric_type' => $metricType,
                'event_type' => $eventType,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function ensureMetricsRow(int $clientId): TenantMetricsCurrent
    {
        return TenantMetricsCurrent::query()->firstOrCreate(
            ['client_id' => $clientId],
            [
                'total_users' => 0,
                'total_establishments' => 0,
            ]
        );
    }

    public function syncDocument(Document $document, bool $withRefresh = true): void
    {
        $clientId = $this->resolveClientId();
        if (!$clientId) {
            return;
        }

        try {
            ClientCentralDocument::query()->updateOrCreate(
                [
                    'client_id' => $clientId,
                    'tenant_document_id' => $document->id,
                ],
                [
                    'date_of_issue' => $document->date_of_issue,
                    'document_type_id' => (string) $document->document_type_id,
                    'state_type_id' => (string) $document->state_type_id,
                    'regularize_shipping' => (bool) $document->regularize_shipping,
                    'send_to_pse' => (bool) $document->send_to_pse,
                    'currency_type_id' => (string) ($document->currency_type_id ?? 'PEN'),
                    'exchange_rate_sale' => (float) ($document->exchange_rate_sale ?? 1),
                    'total' => (float) ($document->total ?? 0),
                ]
            );

            if ($withRefresh) {
                $this->refreshAggregatesForClient($clientId);
            }
        } catch (\Throwable $e) {
            Log::warning('TenantCentralMetricsSyncService::syncDocument failed', [
                'client_id' => $clientId,
                'document_id' => $document->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function removeDocument(Document $document): void
    {
        $clientId = $this->resolveClientId();
        if (!$clientId) {
            return;
        }

        try {
            ClientCentralDocument::query()
                ->where('client_id', $clientId)
                ->where('tenant_document_id', $document->id)
                ->delete();
            $this->refreshAggregatesForClient($clientId);
        } catch (\Throwable $e) {
            Log::warning('TenantCentralMetricsSyncService::removeDocument failed', [
                'client_id' => $clientId,
                'document_id' => $document->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function syncSaleNote(SaleNote $saleNote, bool $withRefresh = true): void
    {
        $clientId = $this->resolveClientId();
        if (!$clientId) {
            return;
        }

        try {
            ClientCentralSaleNote::query()->updateOrCreate(
                [
                    'client_id' => $clientId,
                    'tenant_sale_note_id' => $saleNote->id,
                ],
                [
                    'date_of_issue' => $saleNote->date_of_issue,
                    'state_type_id' => (string) $saleNote->state_type_id,
                    'changed' => (bool) $saleNote->changed,
                    'currency_type_id' => (string) ($saleNote->currency_type_id ?? 'PEN'),
                    'exchange_rate_sale' => (float) ($saleNote->exchange_rate_sale ?? 1),
                    'total' => (float) ($saleNote->total ?? 0),
                ]
            );

            if ($withRefresh) {
                $this->refreshAggregatesForClient($clientId);
            }
        } catch (\Throwable $e) {
            Log::warning('TenantCentralMetricsSyncService::syncSaleNote failed', [
                'client_id' => $clientId,
                'sale_note_id' => $saleNote->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function removeSaleNote(SaleNote $saleNote): void
    {
        $clientId = $this->resolveClientId();
        if (!$clientId) {
            return;
        }

        try {
            ClientCentralSaleNote::query()
                ->where('client_id', $clientId)
                ->where('tenant_sale_note_id', $saleNote->id)
                ->delete();
            $this->refreshAggregatesForClient($clientId);
        } catch (\Throwable $e) {
            Log::warning('TenantCentralMetricsSyncService::removeSaleNote failed', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function refreshUserAndEstablishmentCounts(): void
    {
        $clientId = $this->resolveClientId();
        if (!$clientId) {
            return;
        }

        try {
            $users = User::query()->count();
            $establishments = Establishment::query()->count();
            $soapTypeId = Company::query()->value('soap_type_id');

            $row = $this->ensureMetricsRow($clientId);
            $row->total_users = $users;
            $row->total_establishments = $establishments;
            $row->soap_type_id = $soapTypeId ? (string) $soapTypeId : null;
            $row->save();

            $this->refreshAggregatesForClient($clientId);
        } catch (\Throwable $e) {
            Log::warning('TenantCentralMetricsSyncService::refreshUserAndEstablishmentCounts failed', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function syncCompanySoapType(Company $company): void
    {
        if (!$company->wasChanged('soap_type_id')) {
            return;
        }

        $clientId = $this->resolveClientId();
        if (!$clientId) {
            return;
        }

        try {
            $row = $this->ensureMetricsRow($clientId);
            $row->soap_type_id = $company->soap_type_id ? (string) $company->soap_type_id : null;
            $row->save();
        } catch (\Throwable $e) {
            Log::warning('TenantCentralMetricsSyncService::syncCompanySoapType failed', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Recalcula agregados en tenant_metrics_current a partir de los índices centrales.
     *
     * Optimización: una sola pasada SQL para contadores sobre client_central_documents,
     * sumatoria de ventas del ciclo con agregados SQL (equivalente a computeSalesTotalCached en PHP),
     * y lectura única de tenant_metrics_current para usuarios/sucursales/soap.
     */
    public function refreshAggregatesForClient(int $clientId): void
    {
        $now = Carbon::now();
        $monthStart = $now->copy()->startOfMonth()->toDateString();
        $monthEnd = $now->copy()->endOfMonth()->toDateString();
        $today = $now->toDateString();

        $client = Client::query()->with('plan')->find($clientId);
        $includeNvSales = $client && $client->plan
            ? $client->plan->includeSaleNotesSalesLimit()
            : false;

        $cycleStart = $monthStart;
        $cycleEnd = $monthEnd;
        if ($client && $client->start_billing_cycle) {
            $range = DocumentHelper::getStartEndDateForFilterDocument($client->start_billing_cycle);
            $cycleStart = $range['start_date'] instanceof Carbon
                ? $range['start_date']->format('Y-m-d')
                : (string) $range['start_date'];
            $cycleEnd = $range['end_date'] instanceof Carbon
                ? $range['end_date']->format('Y-m-d')
                : (string) $range['end_date'];
        }

        $sys = DB::connection('system');

        $agg = $sys->selectOne(
            'SELECT COUNT(*) AS total_documents, '
            . 'COALESCE(SUM(CASE WHEN send_to_pse = 1 THEN 1 ELSE 0 END), 0) AS total_documents_pse, '
            . 'COALESCE(SUM(CASE WHEN date_of_issue BETWEEN ? AND ? THEN 1 ELSE 0 END), 0) AS current_month_documents, '
            . 'COALESCE(SUM(CASE WHEN state_type_id = ? AND regularize_shipping = 1 THEN 1 ELSE 0 END), 0) AS pending_regularize_shipping, '
            . 'COALESCE(SUM(CASE WHEN state_type_id IN (?, ?) AND date_of_issue <= ? THEN 1 ELSE 0 END), 0) AS pending_not_sent, '
            . 'COALESCE(SUM(CASE WHEN state_type_id = ? THEN 1 ELSE 0 END), 0) AS pending_to_be_canceled, '
            . 'COALESCE(SUM(CASE WHEN state_type_id = ? THEN 1 ELSE 0 END), 0) AS pending_rejected, '
            . 'COALESCE(SUM(CASE WHEN state_type_id = ? THEN 1 ELSE 0 END), 0) AS pending_observed '
            . 'FROM client_central_documents WHERE client_id = ?',
            [
                $monthStart,
                $monthEnd,
                '01',
                '01',
                '03',
                $today,
                '13',
                '09',
                '07',
                $clientId,
            ]
        );

        $totalDocuments = (int) ($agg->total_documents ?? 0);
        $totalPse = (int) ($agg->total_documents_pse ?? 0);
        $currentMonthDocuments = (int) ($agg->current_month_documents ?? 0);
        $pendingRegularize = (int) ($agg->pending_regularize_shipping ?? 0);
        $pendingNotSent = (int) ($agg->pending_not_sent ?? 0);
        $pendingCanceled = (int) ($agg->pending_to_be_canceled ?? 0);
        $pendingRejected = (int) ($agg->pending_rejected ?? 0);
        $pendingObserved = (int) ($agg->pending_observed ?? 0);

        $totalSaleNotes = (int) $sys->table('client_central_sale_notes')
            ->where('client_id', $clientId)
            ->count();

        $salesCached = $this->computeSalesTotalCachedUsingSql($sys, $clientId, $cycleStart, $cycleEnd, $includeNvSales);

        $existingMetrics = $sys->table('tenant_metrics_current')
            ->where('client_id', $clientId)
            ->select(['total_users', 'total_establishments', 'soap_type_id'])
            ->first();

        $users = (int) ($existingMetrics?->total_users ?? 0);
        $establishments = (int) ($existingMetrics?->total_establishments ?? 0);
        $soap = $existingMetrics?->soap_type_id;

        TenantMetricsCurrent::query()->updateOrInsert(
            ['client_id' => $clientId],
            [
                'total_documents' => $totalDocuments,
                'total_documents_pse' => $totalPse,
                'current_month_documents' => $currentMonthDocuments,
                'total_sales_notes' => $totalSaleNotes,
                'pending_regularize_shipping' => $pendingRegularize,
                'pending_not_sent' => $pendingNotSent,
                'pending_to_be_canceled' => $pendingCanceled,
                'pending_rejected' => $pendingRejected,
                'pending_observed' => $pendingObserved,
                'monthly_sales_total_cached' => $salesCached,
                'metrics_last_synced_at' => $now,
                'total_users' => $users,
                'total_establishments' => $establishments,
                'soap_type_id' => $soap,
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );
    }

    /**
     * Misma regla que el histórico computeSalesTotalCached en PHP: documentos 01/03/08 suman,
     * 07 resta (NC), conversión USD con exchange_rate_sale (PHP trataba 0/null como 1); NV opcional.
     */
    private function computeSalesTotalCachedUsingSql(
        Connection $sys,
        int $clientId,
        string $cycleStart,
        string $cycleEnd,
        bool $includeSaleNotes
    ): float {
        $docRow = $sys->selectOne(
            'SELECT COALESCE(SUM('
            . 'CASE '
            . "WHEN document_type_id = '07' THEN "
            . '-(CASE WHEN currency_type_id = ? THEN total * IFNULL(NULLIF(exchange_rate_sale, 0), 1) ELSE total END) '
            . "WHEN document_type_id IN ('01', '03', '08') THEN "
            . '(CASE WHEN currency_type_id = ? THEN total * IFNULL(NULLIF(exchange_rate_sale, 0), 1) ELSE total END) '
            . 'ELSE 0 END'
            . '), 0) AS s '
            . 'FROM client_central_documents '
            . 'WHERE client_id = ? '
            . 'AND date_of_issue BETWEEN ? AND ? '
            . "AND state_type_id IN ('01','03','05','07','13') "
            . "AND document_type_id IN ('01','03','08','07')",
            ['USD', 'USD', $clientId, $cycleStart, $cycleEnd]
        );

        $sum = (float) ($docRow->s ?? 0);

        if ($includeSaleNotes) {
            $nvRow = $sys->selectOne(
                'SELECT COALESCE(SUM('
                . '(CASE WHEN currency_type_id = ? THEN total * IFNULL(NULLIF(exchange_rate_sale, 0), 1) ELSE total END)'
                . '), 0) AS s '
                . 'FROM client_central_sale_notes '
                . 'WHERE client_id = ? '
                . 'AND changed = 0 '
                . 'AND date_of_issue BETWEEN ? AND ? '
                . "AND state_type_id IN ('01','03','05','07','13')",
                ['USD', $clientId, $cycleStart, $cycleEnd]
            );
            $sum += (float) ($nvRow->s ?? 0);
        }

        return round($sum, 2);
    }

    /**
     * Reconstruye tenant_metric_history (usuarios y sucursales) desde el tenant actual.
     * Elimina eventos previos de esas métricas para el cliente y vuelve a insertar un "created"
     * por cada fila existente usando created_at (producción / tenants ya operativos).
     *
     * @return array{users: int, establishments: int, history_deleted: int}
     */
    public function rebuildUsersAndEstablishmentsHistoryFromTenant(int $clientId, bool $dryRun = false): array
    {
        $previousContext = $this->contextClientId;
        $this->contextClientId = $clientId;

        $stats = ['users' => 0, 'establishments' => 0, 'history_deleted' => 0];

        try {
            $users = User::query()
                ->orderBy('id')
                ->get(['id', 'name', 'email', 'created_at']);

            $establishments = Establishment::query()
                ->orderBy('id')
                ->get(['id', 'description', 'code', 'created_at']);

            $stats['users'] = $users->count();
            $stats['establishments'] = $establishments->count();

            if ($dryRun) {
                return $stats;
            }

            $now = Carbon::now();

            DB::connection('system')->transaction(function () use ($clientId, $users, $establishments, $now, &$stats) {
                $stats['history_deleted'] = TenantMetricHistory::query()
                    ->where('client_id', $clientId)
                    ->whereIn('metric_type', ['users', 'establishments'])
                    ->delete();

                $rows = [];

                foreach ($users as $u) {
                    $eventAt = $u->created_at ? Carbon::parse($u->created_at) : $now;
                    $rows[] = [
                        'client_id' => $clientId,
                        'metric_type' => 'users',
                        'event_type' => 'created',
                        'value' => 1,
                        'reference_type' => 'tenant_user',
                        'reference_id' => (int) $u->id,
                        'event_date' => $eventAt->format('Y-m-d H:i:s'),
                        'metadata' => json_encode(array_merge(
                            array_filter([
                                'email' => $u->email,
                                'name' => $u->name,
                            ]),
                            ['source' => 'rebuild']
                        ), JSON_UNESCAPED_UNICODE),
                        'created_at' => $now->format('Y-m-d H:i:s'),
                        'updated_at' => $now->format('Y-m-d H:i:s'),
                    ];
                }

                foreach ($establishments as $e) {
                    $eventAt = $e->created_at ? Carbon::parse($e->created_at) : $now;
                    $rows[] = [
                        'client_id' => $clientId,
                        'metric_type' => 'establishments',
                        'event_type' => 'created',
                        'value' => 1,
                        'reference_type' => 'tenant_establishment',
                        'reference_id' => (int) $e->id,
                        'event_date' => $eventAt->format('Y-m-d H:i:s'),
                        'metadata' => json_encode(array_merge(
                            array_filter([
                                'description' => $e->description,
                                'code' => $e->code,
                            ]),
                            ['source' => 'rebuild']
                        ), JSON_UNESCAPED_UNICODE),
                        'created_at' => $now->format('Y-m-d H:i:s'),
                        'updated_at' => $now->format('Y-m-d H:i:s'),
                    ];
                }

                foreach (array_chunk($rows, 300) as $chunk) {
                    DB::connection('system')->table('tenant_metric_history')->insert($chunk);
                }
            });

            $this->refreshUserAndEstablishmentCounts();
        } finally {
            $this->contextClientId = $previousContext;
        }

        return $stats;
    }

    public function backfillClientFromTenant(int $clientId): void
    {
        $previousContext = $this->contextClientId;
        $this->contextClientId = $clientId;

        try {
            $this->refreshUserAndEstablishmentCounts();

            $documents = Document::query()
                ->select([
                    'id',
                    'date_of_issue',
                    'document_type_id',
                    'state_type_id',
                    'regularize_shipping',
                    'send_to_pse',
                    'currency_type_id',
                    'exchange_rate_sale',
                    'total',
                ])
                ->orderBy('id')
                ->cursor();

            foreach ($documents as $document) {
                $this->syncDocument($document, false);
            }

            $saleNotes = SaleNote::query()
                ->select([
                    'id',
                    'date_of_issue',
                    'state_type_id',
                    'changed',
                    'currency_type_id',
                    'exchange_rate_sale',
                    'total',
                ])
                ->orderBy('id')
                ->cursor();

            foreach ($saleNotes as $saleNote) {
                $this->syncSaleNote($saleNote, false);
            }

            $this->refreshAggregatesForClient($clientId);
        } finally {
            $this->contextClientId = $previousContext;
        }
    }
}

