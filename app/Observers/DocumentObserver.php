<?php

namespace App\Observers;

use App\CoreFacturalo\Requests\Inputs\Functions;
use App\Models\Tenant\Company;
use App\Models\Tenant\Document;
use App\Services\System\TenantCentralMetricsSyncService;
use Illuminate\Support\Facades\DB;

class DocumentObserver
{
    /**
     * Handle the document "creating" event.
     *
     * @param  \App\Models\Tenant\Document  $document
     * @return void
     */
    public function creating(Document $document)
    {
        $company = Company::active();
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
}
