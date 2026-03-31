<?php

namespace Modules\Document\Helpers;

use Exception;
use Carbon\Carbon;
use App\Models\Tenant\{
    Configuration,
    Document,
    SaleNote,
};
use Hyn\Tenancy\Environment;
use App\Models\System\Client;
use App\Models\System\ClientDocumentPackage;
use App\Traits\LockedEmissionTrait;
use Illuminate\Support\Facades\DB;


class DocumentHelper
{

    use LockedEmissionTrait;
    
    /**
     * Obtener fecha de ciclo de facturacion desde client (system), relacionado al tenant
     */
    public static function getStartBillingCycleFromSystem()
    {
        $tenancy = app(Environment::class);
        $hostname = $tenancy->hostname();
        $client = Client::select('start_billing_cycle')->where('hostname_id', $hostname->id)->first();

        return $client->start_billing_cycle;
    }

            
    /**
     * 
     * Validar si los documentos emitidos superan el limite permitido por el plan (ciclo facturacion)
     *
     * Usado en: 
     * App\Providers\LockedEmissionProvider
     * App\Http\Controllers\Tenant\DocumentController
     * 
     * @param  string $type
     * @return array
     */     
    public function exceedLimitDocuments($type = 'document')
    {
        /*
        $configuration = $configuration ?? Configuration::firstOrFail();

        //cantidad limite de documentos permitidos para emitir (0 = ilimitado)
        $limit_documents = $configuration->limit_documents;
        */
        /*
        //obtener limite desde el plan en bd admin
        $plan = $this->getClientPlan(['id', 'name', 'limit_documents', 'include_sale_notes_limit_documents']);

        //cantidad limite de documentos permitidos para emitir (0 = ilimitado)
        $limit_documents = $plan->limit_documents;

        if($limit_documents !== 0)
        {
            if($type === 'document' || ($type === 'sale-note' && $plan->includeSaleNotesLimitDocuments()))
            {
                
            //fecha de inicio del ciclo de facturacion
            $start_billing_cycle = self::getStartBillingCycleFromSystem();
            
            if($start_billing_cycle){

                //obtener fecha inicio y fin
                $start_end_date = self::getStartEndDateForFilterDocument($start_billing_cycle);
    
                //cantidad de documentos emitidos en el rango de fechas obtenido desde el ciclo de facturacion
                $quantity_documents = Document::whereBetween('date_of_issue', [ $start_end_date['start_date'], $start_end_date['end_date'] ])->count();

                if($plan->includeSaleNotesLimitDocuments())
                {
                    $quantity_documents += $this->getQuantitySaleNotesByDates($start_end_date['start_date']->format('Y-m-d'), $start_end_date['end_date']->format('Y-m-d'));
                }
    
                if($quantity_documents > $limit_documents)
                {
                    return [
                        'success' => true,
                        'message' => 'Ha superado el límite permitido para la emisión de comprobantes'
                    ];
                }

            }

            }
        }

        return [
            'success' => false,
            'message' => ''
        ];*/
        // Obtener plan del cliente
        $plan = $this->getClientPlan(['id', 'name', 'limit_documents', 'include_sale_notes_limit_documents']);
        $limit_documents = $plan->limit_documents;

        if($limit_documents !== 0)
        {
            if($type === 'document' || ($type === 'sale-note' && $plan->includeSaleNotesLimitDocuments()))
            {
                //fecha de inicio del ciclo de facturacion
                $start_billing_cycle = self::getStartBillingCycleFromSystem();
            
                if($start_billing_cycle) {
                    //obtener fecha inicio y fin del ciclo actual
                    $start_end_date = self::getStartEndDateForFilterDocument($start_billing_cycle);
                
                    //cantidad de documentos emitidos en el rango de fechas del ciclo actual
                    $quantity_documents = Document::whereBetween('date_of_issue', [ 
                        $start_end_date['start_date'], 
                        $start_end_date['end_date'] 
                    ])->count();
                
                    if($plan->includeSaleNotesLimitDocuments())
                    {
                        $quantity_documents += $this->getQuantitySaleNotesByDates(
                            $start_end_date['start_date']->format('Y-m-d'), 
                            $start_end_date['end_date']->format('Y-m-d')
                        );
                    }
                } else {
                    // Si no hay ciclo configurado, usar el mes calendario actual
                    $start_date = Carbon::now()->startOfMonth();
                    $end_date = Carbon::now()->endOfMonth();
                
                    $quantity_documents = Document::whereBetween('date_of_issue', [$start_date, $end_date])->count();
                
                    if($plan->includeSaleNotesLimitDocuments())
                    {
                        $quantity_documents += $this->getQuantitySaleNotesByDates(
                            $start_date->format('Y-m-d'), 
                            $end_date->format('Y-m-d')
                        );
                    }
                }
            
                // Verificación del límite de documentos
                if($quantity_documents > $limit_documents)
                {
                    return [
                        'success' => true,
                        'message' => "Ha alcanzado el límite de {$limit_documents} comprobantes permitidos en su plan para este ciclo"
                    ];
                }
            }
        }

        return [
            'success' => false,
            'message' => ''
        ];
    }

    public function checkLimitWithPackages($type = 'document')
    {
        $plan = $this->getClientPlan(['id', 'name', 'limit_documents', 'include_sale_notes_limit_documents']);
        $limitDocuments = (int) $plan->limit_documents;

        if ($limitDocuments === 0) {
            return [
                'success' => false,
                'message' => '',
            ];
        }

        if ($type === 'sale-note' && !$plan->includeSaleNotesLimitDocuments()) {
            return [
                'success' => false,
                'message' => '',
            ];
        }

        $cycle = $this->getCurrentCycleForTenant();
        $quantityDocuments = $this->getQuantityDocumentsByCycle(
            $cycle['start_date'],
            $cycle['end_date'],
            $plan->includeSaleNotesLimitDocuments()
        );

        if ($quantityDocuments < $limitDocuments) {
            return [
                'success' => false,
                'message' => '',
            ];
        }

        $clientId = $this->getClientIdFromSystem();
        $remaining = $this->getRemainingPackageUnits(
            $clientId,
            $cycle['cycle_start_at'],
            $cycle['cycle_end_at'],
            $type
        );

        if ($remaining > 0) {
            return [
                'success' => false,
                'message' => '',
            ];
        }

        return [
            'success' => true,
            'message' => "Ha alcanzado el límite de {$limitDocuments} comprobantes permitidos en su plan para este ciclo. Adquiera un paquete de comprobantes o espere la renovación del ciclo.",
        ];
    }

    public function getPackageConsumptionContextAfterCreate($type = 'document')
    {
        $plan = $this->getClientPlan(['id', 'name', 'limit_documents', 'include_sale_notes_limit_documents']);
        $limitDocuments = (int) $plan->limit_documents;

        if ($limitDocuments === 0) {
            return [
                'should_consume' => false,
            ];
        }

        if ($type === 'sale-note' && !$plan->includeSaleNotesLimitDocuments()) {
            return [
                'should_consume' => false,
            ];
        }

        $cycle = $this->getCurrentCycleForTenant();
        $quantityDocuments = $this->getQuantityDocumentsByCycle(
            $cycle['start_date'],
            $cycle['end_date'],
            $plan->includeSaleNotesLimitDocuments()
        );

        if ($quantityDocuments <= $limitDocuments) {
            return [
                'should_consume' => false,
            ];
        }

        $clientId = $this->getClientIdFromSystem();
        $remaining = $this->getRemainingPackageUnits(
            $clientId,
            $cycle['cycle_start_at'],
            $cycle['cycle_end_at'],
            $type
        );

        if ($remaining <= 0) {
            return [
                'should_consume' => false,
                'exceed' => true,
                'message' => "Ha alcanzado el límite de {$limitDocuments} comprobantes permitidos en su plan para este ciclo.",
            ];
        }

        return [
            'should_consume' => true,
            'client_id' => $clientId,
            'cycle_start_at' => $cycle['cycle_start_at'],
            'cycle_end_at' => $cycle['cycle_end_at'],
            'type' => $type,
        ];
    }

    public function consumeOnePackageUnit($clientId, $cycleStartAt, $cycleEndAt, $type = 'document')
    {
        $clientId = (int) $clientId;

        return DB::connection('system')->transaction(function () use ($clientId, $cycleStartAt, $cycleEndAt, $type) {
            $query = ClientDocumentPackage::query()
                ->activeForCycle($clientId, $cycleStartAt, $cycleEndAt)
                ->whereColumn('units_consumed', '<', 'units_total');

            if ($type === 'sale-note') {
                $query->where('include_sale_notes', true);
            }

            for ($attempt = 0; $attempt < 3; $attempt++) {
                $package = (clone $query)->orderBy('created_at')->lockForUpdate()->first();

                if (!$package) {
                    return false;
                }

                $updated = ClientDocumentPackage::query()
                    ->where('id', $package->id)
                    ->whereColumn('units_consumed', '<', 'units_total')
                    ->update([
                        'units_consumed' => DB::raw('units_consumed + 1'),
                    ]);

                if ($updated === 1) {
                    return true;
                }
            }

            return false;
        });
    }

    private function getClientIdFromSystem()
    {
        $tenancy = app(Environment::class);
        $hostname = $tenancy->hostname();
        $client = Client::select('id')->where('hostname_id', $hostname->id)->firstOrFail();

        return (int) $client->id;
    }

    private function getCurrentCycleForTenant()
    {
        $startBillingCycle = self::getStartBillingCycleFromSystem();

        if ($startBillingCycle) {
            $startEnd = self::getStartEndDateForFilterDocument($startBillingCycle);
            $cycleStart = $startEnd['start_date']->copy()->startOfDay();
            $cycleEnd = $cycleStart->copy()->addMonthNoOverflow()->subDay()->endOfDay();

            return [
                'start_date' => $startEnd['start_date'],
                'end_date' => $startEnd['end_date'],
                'cycle_start_at' => $cycleStart->format('Y-m-d'),
                'cycle_end_at' => $cycleEnd->format('Y-m-d'),
            ];
        }

        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'cycle_start_at' => $startDate->format('Y-m-d'),
            'cycle_end_at' => $endDate->format('Y-m-d'),
        ];
    }

    private function getQuantityDocumentsByCycle($startDate, $endDate, $includeSaleNotes)
    {
        $quantity = Document::whereBetween('date_of_issue', [$startDate, $endDate])->count();

        if ($includeSaleNotes) {
            $quantity += $this->getQuantitySaleNotesByDates(
                Carbon::parse($startDate)->format('Y-m-d'),
                Carbon::parse($endDate)->format('Y-m-d')
            );
        }

        return (int) $quantity;
    }

    private function getRemainingPackageUnits($clientId, $cycleStartAt, $cycleEndAt, $type = 'document')
    {
        $query = ClientDocumentPackage::query()
            ->activeForCycle((int) $clientId, $cycleStartAt, $cycleEndAt)
            ->selectRaw('COALESCE(SUM(units_total - units_consumed), 0) as remaining');

        if ($type === 'sale-note') {
            $query->where('include_sale_notes', true);
        }

        return (int) $query->value('remaining');
    }


    /**
     * 
     * Obtener fecha de inicio y fin para filtrar documentos en base 
     * a la fecha de inicio del ciclo de facturacion (planes) del cliente
     *
     * Usado en: 
     * App\Http\Controllers\System\ClientController
     * 
     * @param  $start_billing_cycle
     * @return array
     */
    public static function getStartEndDateForFilterDocument($start_billing_cycle)
    { 
        
        $day_start_billing = date_format($start_billing_cycle, 'j');
        $day_now = (int) date('j');
        $end = Carbon::parse(date('Y-m-d'));
        // $day_now = 6;
        // $end = Carbon::parse(date('2022-01-06'));

        if ($day_now <= $day_start_billing) {

            $init = Carbon::parse(date('Y') . '-' . ((int)date('n') - 1) . '-' . $day_start_billing);

        } else {

            $init = Carbon::parse(date('Y') . '-' . ((int)date('n')) . '-' . $day_start_billing);
            
        }

        return [
            'start_date' => $init,
            'end_date' => $end,
        ];

    }

        
    /**
     * Obtener modelo por tipo de documento
     *
     * @param  string $document_type_id
     * @return string
     */
    public function getModelByDocumentType($document_type_id)
    {
        $model = null;

        switch ($document_type_id)
        {
            case '01':
            case '03':
                $model = Document::class;
                break;
            
            case '80':
                $model = SaleNote::class;
                break;
        }

        if(is_null($model)) throw new Exception('No se encontró un modelo para el tipo de documento.');

        return $model;
    }

    
    /**
     * 
     * Obtener documento para envio de mensaje por ws
     *
     * @param  string $model
     * @param  string $id
     * @return Document|SaleNote
     */
    public function getDocumentDataForSendMessage($model, $id)
    {
        return $model::filterDataForSendMessage()->findOrFail($id);
    }

    
    /**
     *
     *  Obtener parametros para envio de mensaje por ws
     *
     * @param  mixed $phone_number
     * @param  mixed $format
     * @param  mixed $document
     * @return void
     */
    public function getParamsForAppSendMessage($phone_number, $format, $document)
    {
        return [
            'send_type' => 'text',
            'phone_number' => $phone_number,
            'message' => "Su comprobante {$document->number_full} ha sido generado correctamente.",
            'document' => [
                'filename'=> "{$document->filename}.pdf",
                'link'=> $document->getUrlPrintByFormat($format)
            ]
        ];
    }
 
}
