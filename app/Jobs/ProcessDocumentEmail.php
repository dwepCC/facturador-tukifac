<?php

namespace App\Jobs;

use App\CoreFacturalo\Facturalo;
use App\Models\Tenant\Document;
use Hyn\Tenancy\Contracts\Website;
use Hyn\Tenancy\Environment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessDocumentEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $document_id;
    protected $website_id;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($document_id, $website_id)
    {
        $this->document_id = $document_id;
        $this->website_id = $website_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $website = \Hyn\Tenancy\Models\Website::find($this->website_id);
        $tenancy = app(Environment::class);
        $tenancy->tenant($website);

        $document = Document::find($this->document_id);

        if (!$document) {
            Log::error("Document not found: {$this->document_id}");
            return;
        }

        try {
            $facturalo = new Facturalo();
            $facturalo->setDocument($document);
            $facturalo->sendEmail();
        } catch (\Exception $e) {
            Log::error("Error sending email for document {$this->document_id}: " . $e->getMessage());
        }
    }
}
