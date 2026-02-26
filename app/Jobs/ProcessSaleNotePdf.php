<?php

namespace App\Jobs;

use App\Http\Controllers\Tenant\SaleNoteController;
use App\Models\Tenant\SaleNote;
use Hyn\Tenancy\Contracts\Website;
use Hyn\Tenancy\Environment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessSaleNotePdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $sale_note_id;
    protected $website_id;
    protected $format;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($sale_note_id, $website_id, $format = 'a4')
    {
        $this->sale_note_id = $sale_note_id;
        $this->website_id = $website_id;
        $this->format = $format;
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

        $sale_note = SaleNote::find($this->sale_note_id);

        if (!$sale_note) {
            Log::error("SaleNote not found: {$this->sale_note_id}");
            return;
        }

        try {
            $controller = new SaleNoteController();
            $controller->createPdf($sale_note, $this->format, $sale_note->filename);
        } catch (\Exception $e) {
            Log::error("Error generating PDF for SaleNote {$this->sale_note_id}: " . $e->getMessage());
        }
    }
}
