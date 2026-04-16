<?php

namespace App\Services\System;

use App\Models\System\Client;
use App\Models\System\ClientCentralDocument;
use App\Models\System\ClientCentralSaleNote;
use App\Models\System\TenantMetricsCurrent;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Document\Helpers\DocumentHelper;

class CentralClientMetricsQueryService
{
    public function paginateClients(Request $request): LengthAwarePaginator
    {
        $q = Client::query()->with(['hostname', 'plan']);

        if ($search = $request->input('search')) {
            $q->where(function ($sub) use ($search) {
                $sub->where('name', 'like', '%' . $search . '%')
                    ->orWhere('number', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhereHas('hostname', function ($h) use ($search) {
                        $h->where('fqdn', 'like', '%' . $search . '%');
                    });
            });
        }

        if ($request->filled('plan_id')) {
            $q->where('plan_id', (int) $request->input('plan_id'));
        }

        if ($request->has('locked_tenant') && $request->input('locked_tenant') !== '' && $request->input('locked_tenant') !== null) {
            $q->where('locked_tenant', (int) $request->input('locked_tenant') === 1);
        }

        if ($request->filled('soap_type')) {
            $soap = $request->input('soap_type');
            $q->whereExists(function ($sub) use ($soap) {
                $sub->select(DB::raw('1'))
                    ->from('tenant_metrics_current as tmc')
                    ->whereColumn('tmc.client_id', 'clients.id')
                    ->where('tmc.soap_type_id', $soap);
            });
        }

        $perPage = min(max((int) $request->input('per_page', 25), 5), 100);

        return $q->latest()->paginate($perPage);
    }

    public function buildPayload(LengthAwarePaginator $paginator, Request $request): array
    {
        $clients = collect($paginator->items());
        $ids = $clients->pluck('id')->all();

        $metrics = $ids ? TenantMetricsCurrent::query()->whereIn('client_id', $ids)->get()->keyBy('client_id') : collect();

        $docStart = $request->input('documents_date_start');
        $docEnd = $request->input('documents_date_end');
        $rangeActive = $this->isDocumentsDateRangeActive($docStart, $docEnd);

        $docCounts = $this->countDocumentsInRangeGrouped($ids, $docStart, $docEnd);
        $snCounts = $this->countSaleNotesInRangeGrouped($ids, $docStart, $docEnd);

        $monthStart = Carbon::now()->startOfMonth()->toDateString();
        $monthEnd = Carbon::now()->endOfMonth()->toDateString();
        $currentMonthDocs = $this->countDocumentsInRangeGrouped($ids, $monthStart, $monthEnd);

        $apiPeru = $rangeActive
            ? $this->countApiPeruGrouped($ids, $docStart, $docEnd)
            : $this->countApiPeruGrouped($ids, null, null);

        $pseInRange = [];
        $pendingInRange = [
            'document_regularize_shipping' => [],
            'document_not_sent' => [],
            'document_to_be_canceled' => [],
            'document_rejected' => [],
            'document_observed' => [],
        ];
        $salesInRange = [];
        $histUsersCreated = [];
        $histUsersDeleted = [];
        $histEstCreated = [];
        $histEstDeleted = [];
        if ($rangeActive && $ids) {
            $pseInRange = $this->countDocumentsPseInRangeGrouped($ids, $docStart, $docEnd);
            $pendingInRange = $this->countPendingDocumentsGrouped($ids, $docStart, $docEnd);
            foreach ($clients as $c) {
                $includeNv = $c->plan && $c->plan->includeSaleNotesSalesLimit();
                $salesInRange[$c->id] = $this->computeCentralSalesInRange((int) $c->id, $docStart, $docEnd, $includeNv);
            }
            $histUsersCreated = $this->countTenantMetricHistoryGrouped($ids, $docStart, $docEnd, 'users', 'created');
            $histUsersDeleted = $this->countTenantMetricHistoryGrouped($ids, $docStart, $docEnd, 'users', 'deleted');
            $histEstCreated = $this->countTenantMetricHistoryGrouped($ids, $docStart, $docEnd, 'establishments', 'created');
            $histEstDeleted = $this->countTenantMetricHistoryGrouped($ids, $docStart, $docEnd, 'establishments', 'deleted');
        }

        $data = [];
        foreach ($clients as $client) {
            /** @var Client $client */
            $m = $metrics->get($client->id);

            $cycleStart = $monthStart;
            $cycleEnd = $monthEnd;
            if ($client->start_billing_cycle) {
                $range = DocumentHelper::getStartEndDateForFilterDocument($client->start_billing_cycle);
                $cycleStart = $range['start_date'] instanceof Carbon
                    ? $range['start_date']->format('Y-m-d')
                    : (string) $range['start_date'];
                $cycleEnd = $range['end_date'] instanceof Carbon
                    ? $range['end_date']->format('Y-m-d')
                    : (string) $range['end_date'];
            }

            $countDocRange = $docCounts[$client->id] ?? 0;
            $countSnRange = $snCounts[$client->id] ?? 0;
            $countDocCycle = $docStart && $docEnd
                ? $countDocRange
                : ($this->singleClientDocCountInRange($client->id, $cycleStart, $cycleEnd));
            $countSnCycle = $docStart && $docEnd
                ? $countSnRange
                : ($this->singleClientSaleNoteCountInRange($client->id, $cycleStart, $cycleEnd));

            $countDoc = ($docStart && $docEnd) ? $countDocRange : (int) (optional($m)->total_documents ?? 0);
            $countSalesNotes = ($docStart && $docEnd) ? $countSnRange : (int) (optional($m)->total_sales_notes ?? 0);

            $data[] = [
                'id' => $client->id,
                'hostname' => optional($client->hostname)->fqdn ?? '',
                'name' => $client->name,
                'email' => $client->email,
                'token' => $client->token,
                'number' => $client->number,
                'plan' => optional($client->plan)->name ?? '',
                'locked' => (bool) $client->locked,
                'locked_emission' => (bool) $client->locked_emission,
                'locked_users' => (bool) $client->locked_users,
                'locked_tenant' => (bool) $client->locked_tenant,
                'count_doc' => (int) $countDoc,
                'count_sales_notes' => (int) $countSalesNotes,
                'max_documents' => (int) optional($client->plan)->limit_documents,
                'count_user' => (int) (optional($m)->total_users ?? 0),
                'max_users' => (int) optional($client->plan)->limit_users,
                'created_at' => optional($client->created_at)->format('Y-m-d H:i:s'),
                'updated_at' => optional($client->updated_at)->format('Y-m-d H:i:s'),
                'current_count_doc_month' => (int) ($rangeActive
                    ? ($docCounts[$client->id] ?? 0)
                    : ($currentMonthDocs[$client->id] ?? 0)),
                'count_doc_pse' => (int) ($rangeActive
                    ? ($pseInRange[$client->id] ?? 0)
                    : (optional($m)->total_documents_pse ?? 0)),
                'start_billing_cycle' => $client->start_billing_cycle
                    ? $client->start_billing_cycle->format('Y-m-d')
                    : '',
                'count_doc_month' => (int) $countDocCycle,
                'count_sales_notes_month' => (int) $countSnCycle,
                'select_date_billing' => '',
                'soap_type' => (string) (optional($m)->soap_type_id ?? ''),
                'document_regularize_shipping' => (int) ($rangeActive
                    ? ($pendingInRange['document_regularize_shipping'][$client->id] ?? 0)
                    : (optional($m)->pending_regularize_shipping ?? 0)),
                'document_not_sent' => (int) ($rangeActive
                    ? ($pendingInRange['document_not_sent'][$client->id] ?? 0)
                    : (optional($m)->pending_not_sent ?? 0)),
                'document_to_be_canceled' => (int) ($rangeActive
                    ? ($pendingInRange['document_to_be_canceled'][$client->id] ?? 0)
                    : (optional($m)->pending_to_be_canceled ?? 0)),
                'document_rejected' => (int) ($rangeActive
                    ? ($pendingInRange['document_rejected'][$client->id] ?? 0)
                    : (optional($m)->pending_rejected ?? 0)),
                'document_observed' => (int) ($rangeActive
                    ? ($pendingInRange['document_observed'][$client->id] ?? 0)
                    : (optional($m)->pending_observed ?? 0)),
                'queries_to_apiperu' => (int) ($apiPeru[$client->id] ?? 0),
                'locked_create_establishments' => (bool) $client->locked_create_establishments,
                'restrict_sales_limit' => (bool) $client->restrict_sales_limit,
                'quantity_establishments' => (int) (optional($m)->total_establishments ?? 0),
                'max_quantity_establishments' => (int) optional($client->plan)->establishments_limit,
                'establishments_unlimited' => (bool) optional($client->plan)->establishments_unlimited,
                'monthly_sales_total' => $rangeActive
                    ? number_format((float) ($salesInRange[$client->id] ?? 0), 2, '.', '')
                    : number_format((float) (optional($m)->monthly_sales_total_cached ?? 0), 2, '.', ''),
                'max_sales_limit' => number_format((float) optional($client->plan)->sales_limit, 2, '.', ''),
                'sales_unlimited' => (bool) optional($client->plan)->sales_unlimited,
                'sale_notes_quantity_if_include' => $client->plan && $client->plan->includeSaleNotesLimitDocuments()
                    ? (int) $countSnCycle
                    : 0,
                'enable_list_product' => (bool) $client->enable_list_product,
                'metrics_last_synced_at' => $m && $m->metrics_last_synced_at
                    ? $m->metrics_last_synced_at->format('Y-m-d H:i:s')
                    : null,
                'mh_users_created' => $rangeActive ? (int) ($histUsersCreated[$client->id] ?? 0) : null,
                'mh_users_deleted' => $rangeActive ? (int) ($histUsersDeleted[$client->id] ?? 0) : null,
                'mh_establishments_created' => $rangeActive ? (int) ($histEstCreated[$client->id] ?? 0) : null,
                'mh_establishments_deleted' => $rangeActive ? (int) ($histEstDeleted[$client->id] ?? 0) : null,
            ];
        }

        return [
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ];
    }

    protected function isDocumentsDateRangeActive(?string $start, ?string $end): bool
    {
        return (bool) ($start && $end);
    }

    /**
     * Cuenta eventos en tenant_metric_history por cliente (mismo rango que filtro de documentos).
     */
    protected function countTenantMetricHistoryGrouped(
        array $clientIds,
        string $start,
        string $end,
        string $metricType,
        string $eventType
    ): array {
        if (!$clientIds) {
            return [];
        }
        $startAt = Carbon::parse($start)->startOfDay();
        $endAt = Carbon::parse($end)->endOfDay();

        $rows = DB::connection('system')->table('tenant_metric_history')
            ->whereIn('client_id', $clientIds)
            ->where('metric_type', $metricType)
            ->where('event_type', $eventType)
            ->whereBetween('event_date', [$startAt->toDateTimeString(), $endAt->toDateTimeString()])
            ->groupBy('client_id')
            ->selectRaw('client_id, COUNT(*) as c')
            ->get();

        return $rows->pluck('c', 'client_id')->all();
    }

    /**
     * Comprobantes con envío a PSE en el rango (date_of_issue).
     */
    protected function countDocumentsPseInRangeGrouped(array $clientIds, string $start, string $end): array
    {
        if (!$clientIds) {
            return [];
        }
        $rows = DB::connection('system')->table('client_central_documents')
            ->whereIn('client_id', $clientIds)
            ->whereBetween('date_of_issue', [$start, $end])
            ->where('send_to_pse', 1)
            ->groupBy('client_id')
            ->selectRaw('client_id, COUNT(*) as c')
            ->get();

        return $rows->pluck('c', 'client_id')->all();
    }

    /**
     * Misma lógica que {@see ClientController::getQuantityPendingDocuments} + filtro por date_of_issue.
     *
     * @return array<string, array<int, int>> claves document_* y mapas client_id => count
     */
    protected function countPendingDocumentsGrouped(array $clientIds, string $start, string $end): array
    {
        if (!$clientIds) {
            return [
                'document_regularize_shipping' => [],
                'document_not_sent' => [],
                'document_to_be_canceled' => [],
                'document_rejected' => [],
                'document_observed' => [],
            ];
        }

        $today = Carbon::now()->toDateString();

        $regularize = DB::connection('system')->table('client_central_documents')
            ->whereIn('client_id', $clientIds)
            ->whereBetween('date_of_issue', [$start, $end])
            ->where('state_type_id', '01')
            ->where('regularize_shipping', true)
            ->groupBy('client_id')
            ->selectRaw('client_id, COUNT(*) as c')
            ->get();

        $notSent = DB::connection('system')->table('client_central_documents')
            ->whereIn('client_id', $clientIds)
            ->whereBetween('date_of_issue', [$start, $end])
            ->whereIn('state_type_id', ['01', '03'])
            ->where('date_of_issue', '<=', $today)
            ->groupBy('client_id')
            ->selectRaw('client_id, COUNT(*) as c')
            ->get();

        $toCancel = DB::connection('system')->table('client_central_documents')
            ->whereIn('client_id', $clientIds)
            ->whereBetween('date_of_issue', [$start, $end])
            ->where('state_type_id', '13')
            ->groupBy('client_id')
            ->selectRaw('client_id, COUNT(*) as c')
            ->get();

        $rejected = DB::connection('system')->table('client_central_documents')
            ->whereIn('client_id', $clientIds)
            ->whereBetween('date_of_issue', [$start, $end])
            ->where('state_type_id', '09')
            ->groupBy('client_id')
            ->selectRaw('client_id, COUNT(*) as c')
            ->get();

        $observed = DB::connection('system')->table('client_central_documents')
            ->whereIn('client_id', $clientIds)
            ->whereBetween('date_of_issue', [$start, $end])
            ->where('state_type_id', '07')
            ->groupBy('client_id')
            ->selectRaw('client_id, COUNT(*) as c')
            ->get();

        return [
            'document_regularize_shipping' => $regularize->pluck('c', 'client_id')->all(),
            'document_not_sent' => $notSent->pluck('c', 'client_id')->all(),
            'document_to_be_canceled' => $toCancel->pluck('c', 'client_id')->all(),
            'document_rejected' => $rejected->pluck('c', 'client_id')->all(),
            'document_observed' => $observed->pluck('c', 'client_id')->all(),
        ];
    }

    /**
     * Total ventas aproximado en el índice central (misma regla que {@see TenantCentralMetricsSyncService::computeSalesTotalCached}).
     */
    protected function computeCentralSalesInRange(int $clientId, string $cycleStart, string $cycleEnd, bool $includeSaleNotes): float
    {
        $states = ['01', '03', '05', '07', '13'];
        $typesMain = ['01', '03', '08'];

        $rows = ClientCentralDocument::query()
            ->where('client_id', $clientId)
            ->whereBetween('date_of_issue', [$cycleStart, $cycleEnd])
            ->whereIn('state_type_id', $states)
            ->whereIn('document_type_id', array_merge($typesMain, ['07']))
            ->get(['document_type_id', 'currency_type_id', 'total', 'exchange_rate_sale']);

        $sum = 0.0;
        foreach ($rows as $r) {
            $amount = (float) $r->total;
            if ($r->currency_type_id === 'USD') {
                $amount *= (float) ($r->exchange_rate_sale ?: 1);
            }
            if ($r->document_type_id === '07') {
                $sum -= $amount;
            } elseif (in_array($r->document_type_id, $typesMain, true)) {
                $sum += $amount;
            }
        }

        if ($includeSaleNotes) {
            $nvRows = ClientCentralSaleNote::query()
                ->where('client_id', $clientId)
                ->where('changed', false)
                ->whereBetween('date_of_issue', [$cycleStart, $cycleEnd])
                ->whereIn('state_type_id', ['01', '03', '05', '07', '13'])
                ->get(['currency_type_id', 'total', 'exchange_rate_sale']);

            foreach ($nvRows as $r) {
                $amount = (float) $r->total;
                if ($r->currency_type_id === 'USD') {
                    $amount *= (float) ($r->exchange_rate_sale ?: 1);
                }
                $sum += $amount;
            }
        }

        return round($sum, 2);
    }

    protected function countDocumentsInRangeGrouped(array $clientIds, ?string $start, ?string $end): array
    {
        if (!$clientIds) {
            return [];
        }
        $q = DB::connection('system')->table('client_central_documents')->whereIn('client_id', $clientIds);
        if ($start && $end) {
            $q->whereBetween('date_of_issue', [$start, $end]);
        }
        $rows = $q->groupBy('client_id')->selectRaw('client_id, COUNT(*) as c')->get();

        return $rows->pluck('c', 'client_id')->all();
    }

    protected function countSaleNotesInRangeGrouped(array $clientIds, ?string $start, ?string $end): array
    {
        if (!$clientIds) {
            return [];
        }
        $q = DB::connection('system')->table('client_central_sale_notes')->whereIn('client_id', $clientIds);
        if ($start && $end) {
            $q->whereBetween('date_of_issue', [$start, $end]);
        }
        $rows = $q->groupBy('client_id')->selectRaw('client_id, COUNT(*) as c')->get();

        return $rows->pluck('c', 'client_id')->all();
    }

    protected function singleClientDocCountInRange(int $clientId, string $start, string $end): int
    {
        return (int) DB::connection('system')->table('client_central_documents')
            ->where('client_id', $clientId)
            ->whereBetween('date_of_issue', [$start, $end])
            ->count();
    }

    protected function singleClientSaleNoteCountInRange(int $clientId, string $start, string $end): int
    {
        return (int) DB::connection('system')->table('client_central_sale_notes')
            ->where('client_id', $clientId)
            ->whereBetween('date_of_issue', [$start, $end])
            ->count();
    }

    /**
     * @param  string|null  $start  Si null, mes calendario actual (campo date_of_issue).
     * @param  string|null  $end
     */
    protected function countApiPeruGrouped(array $clientIds, ?string $start = null, ?string $end = null): array
    {
        if (!$clientIds) {
            return [];
        }
        if (!$start || !$end) {
            $start = Carbon::now()->firstOfMonth()->format('Y-m-d');
            $end = Carbon::now()->lastOfMonth()->format('Y-m-d');
        }

        $rows = DB::connection('system')->table('track_api_peru_services')
            ->whereIn('client_id', $clientIds)
            ->whereDate('date_of_issue', '>=', $start)
            ->whereDate('date_of_issue', '<=', $end)
            ->selectRaw('client_id, COUNT(*) as c')
            ->groupBy('client_id')
            ->get();

        return $rows->pluck('c', 'client_id')->all();
    }

    /**
     * Listado paginado de comprobantes en índice central para un tipo de "evento" de notificación.
     * Criterios alineados con {@see TenantCentralMetricsSyncService} (pending_*).
     */
    public function paginateDocumentEvents(int $clientId, string $kind, int $perPage = 25, ?string $docStart = null, ?string $docEnd = null): LengthAwarePaginator
    {
        $q = ClientCentralDocument::query()->where('client_id', $clientId);
        $this->applyDocumentEventKindFilter($q, $kind);
        if ($docStart && $docEnd) {
            $q->whereBetween('date_of_issue', [$docStart, $docEnd]);
        }

        $paginator = $q->orderByDesc('date_of_issue')
            ->orderByDesc('id')
            ->paginate($perPage);

        $paginator->setCollection(
            $paginator->getCollection()->map(fn (ClientCentralDocument $doc) => $this->mapCentralDocumentForList($doc))
        );

        return $paginator;
    }

    protected function applyDocumentEventKindFilter(Builder $query, string $kind): void
    {
        $today = Carbon::now()->toDateString();

        switch ($kind) {
            case 'not_sent':
                $query->whereIn('state_type_id', ['01', '03'])
                    ->where('date_of_issue', '<=', $today);
                break;
            case 'regularize_shipping':
                $query->where('state_type_id', '01')->where('regularize_shipping', true);
                break;
            case 'to_be_canceled':
                $query->where('state_type_id', '13');
                break;
            case 'rejected':
                $query->where('state_type_id', '09');
                break;
            case 'observed':
                $query->where('state_type_id', '07');
                break;
            default:
                throw new InvalidArgumentException('Invalid document event kind: ' . $kind);
        }
    }

    protected function mapCentralDocumentForList(ClientCentralDocument $doc): array
    {
        return [
            'tenant_document_id' => (int) $doc->tenant_document_id,
            'date_of_issue' => $doc->date_of_issue ? $doc->date_of_issue->format('Y-m-d') : '',
            'document_type_id' => $doc->document_type_id,
            'document_type_description' => $this->documentTypeLabel($doc->document_type_id),
            'state_type_id' => $doc->state_type_id,
            'state_type_description' => $this->stateTypeLabel($doc->state_type_id),
            'regularize_shipping' => (bool) $doc->regularize_shipping,
            'send_to_pse' => (bool) $doc->send_to_pse,
            'currency_type_id' => $doc->currency_type_id,
            'total' => (string) $doc->total,
            'exchange_rate_sale' => (string) $doc->exchange_rate_sale,
        ];
    }

    protected function documentTypeLabel(?string $id): string
    {
        $map = [
            '01' => 'Factura',
            '03' => 'Boleta de venta',
            '07' => 'Nota de crédito',
            '08' => 'Nota de débito',
            '09' => 'Guía de remisión',
            '20' => 'Retención',
            '40' => 'Percepción',
        ];

        return $map[$id] ?? ($id ? 'Tipo ' . $id : '—');
    }

    protected function stateTypeLabel(?string $id): string
    {
        $map = [
            '01' => 'Registrado',
            '03' => 'Enviado',
            '05' => 'Aceptado',
            '07' => 'Observado',
            '09' => 'Rechazado',
            '11' => 'Contingencia',
            '13' => 'Por anular',
        ];

        return $map[$id] ?? ($id ? 'Estado ' . $id : '—');
    }
}
