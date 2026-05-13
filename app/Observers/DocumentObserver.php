<?php

namespace App\Observers;

use App\CoreFacturalo\Requests\Inputs\Functions;
use App\Models\Tenant\Company;
use App\Models\Tenant\Document;
use App\Services\System\TenantCentralMetricsSyncService;
use Illuminate\Support\Facades\DB;

/**
 * Observer de documentos del tenant.
 *
 * Cache de {@see Company::active()} en {@see DocumentObserver::resolveActiveCompany}:
 * `Company` declara `$with = ['identity_document_type']`, por lo que cada `Company::active()` dispara
 * carga de relaciones. En una misma petición HTTP pueden crearse varios modelos; cachear evita
 * consultas repetidas sin cambiar la lógica de numeración ni de negocio.
 *
 * Scope esperado: **una petición HTTP** (PHP-FPM + Docker habituales: contenedor IoC nuevo por request).
 * En runtimes **persistentes** (Laravel Octane, RoadRunner, etc.) el contenedor puede sobrevivir entre
 * peticiones; por eso se registra un `terminating` que hace `forgetInstance` del binding (defensivo,
 * inocuo en FPM). Si migráis a Octane/RR, revisad también la guía oficial de reset entre requests.
 */
class DocumentObserver
{
    /**
     * Clave del contenedor para la empresa activa cacheada durante el ciclo actual de la aplicación.
     */
    private const ACTIVE_COMPANY_BINDING = 'document_observer.tenant_active_company';

    /**
     * Handle the document "creating" event.
     *
     * @param  \App\Models\Tenant\Document  $document
     * @return void
     */
    public function creating(Document $document)
    {
        $company = $this->resolveActiveCompany();
        $number = Functions::newNumber($document->soap_type_id,
                                       $document->document_type_id,
                                       $document->series,
                                       $document->number, Document::class);
        $document->number = $number;

        $document->filename = Functions::filename($company, $document->document_type_id, $document->series, $number);
        $document->unique_filename = $document->filename; //campo único para evitar duplicados

    }

    /**
     * Handle the document "updated" event.
     *
     * @param  \App\Models\Tenant\Document  $document
     * @return void
     */
    public function updated(Document $document)
    {
        DB::connection($document->getConnectionName())->afterCommit(function () use ($document) {
            app(TenantCentralMetricsSyncService::class)->syncDocument($document);
        });
    }

    /**
     * Handle the document "deleted" event.
     *
     * @param  \App\Models\Tenant\Document  $document
     * @return void
     */
    public function deleted(Document $document)
    {
        DB::connection($document->getConnectionName())->afterCommit(function () use ($document) {
            app(TenantCentralMetricsSyncService::class)->removeDocument($document);
        });
    }

    public function created(Document $document)
    {
        DB::connection($document->getConnectionName())->afterCommit(function () use ($document) {
            app(TenantCentralMetricsSyncService::class)->syncDocument($document);
        });
    }

    /**
     * Handle the document "restored" event.
     *
     * @param  \App\Models\Tenant\Document  $document
     * @return void
     */
    public function restored(Document $document)
    {
        //
    }

    /**
     * Handle the document "force deleted" event.
     *
     * @param  \App\Models\Tenant\Document  $document
     * @return void
     */
    public function forceDeleted(Document $document)
    {
        DB::connection($document->getConnectionName())->afterCommit(function () use ($document) {
            app(TenantCentralMetricsSyncService::class)->removeDocument($document);
        });
    }

    /**
     * Resuelve la empresa activa del tenant cacheándola en el contenedor durante el ciclo actual.
     *
     * Motivo: `Company::active()` es `Company::first()` con eager load por `$with`; repetirlo en la
     * misma petición es trabajo innecesario hacia la BD del tenant.
     *
     * Alcance: request-scoped en PHP-FPM estándar (nueva instancia de aplicación por request).
     * Limpieza: al terminar el ciclo se elimina el binding para no arrastrarlo en procesos persistentes.
     */
    private function resolveActiveCompany(): Company
    {
        if (!app()->bound(self::ACTIVE_COMPANY_BINDING)) {
            app()->instance(self::ACTIVE_COMPANY_BINDING, Company::active());

            app()->terminating(function () {
                if (app()->bound(self::ACTIVE_COMPANY_BINDING)) {
                    app()->forgetInstance(self::ACTIVE_COMPANY_BINDING);
                }
            });
        }

        return app(self::ACTIVE_COMPANY_BINDING);
    }
}
