<?php

namespace Modules\Dashboard\Helpers;

use App\Models\Tenant\Document;
use App\Models\Tenant\Configuration;
use App\Models\Tenant\DocumentPayment;
use App\Models\Tenant\SaleNote;
use App\Models\Tenant\SaleNotePayment;
use Carbon\Carbon;
use App\Models\Tenant\Person;
use App\Models\Tenant\Item;
use App\Models\Tenant\Purchase;
use Modules\Expense\Models\Expense;
use Modules\Dashboard\Traits\TotalsTrait;


class DashboardData
{

    use TotalsTrait;

    public function data($request)
    {
        $establishment_id = $request['establishment_id'] ?? null;
        $period = $request['period'] ?? 'month';
        $date_start = $request['date_start'] ?? null;
        $date_end = $request['date_end'] ?? null;
        $month_start = $request['month_start'] ?? now()->format('Y-m');
        $month_end = $request['month_end'] ?? now()->format('Y-m');

        $d_start = null;
        $d_end = null;

        switch ($period) {
            case 'month':
                $parsed = $this->parseMonth($month_start) ?? now()->startOfMonth()->format('Y-m-d');
                $d_start = Carbon::parse($parsed)->format('Y-m-d');
                $d_end = Carbon::parse($parsed)->endOfMonth()->format('Y-m-d');
                break;
            case 'between_months':
                $parsedStart = $this->parseMonth($month_start) ?? now()->startOfMonth()->format('Y-m-d');
                $parsedEnd = $this->parseMonth($month_end) ?? now()->endOfMonth()->format('Y-m-d');
                $d_start = Carbon::parse($parsedStart)->format('Y-m-d');
                $d_end = Carbon::parse($parsedEnd)->endOfMonth()->format('Y-m-d');
                break;
            case 'date':
                $parsed = $this->parseDate($date_start) ?? now()->format('Y-m-d');
                $d_start = $parsed;
                $d_end = $parsed;
                break;
            case 'between_dates':
                $d_start = $this->parseDate($date_start) ?? now()->format('Y-m-d');
                $d_end = $this->parseDate($date_end) ?? now()->format('Y-m-d');
                break;
            case 'last_week':
                $d_start = $this->parseDate($date_start) ?? now()->format('Y-m-d');
                $d_end = $this->parseDate($date_end) ?? now()->format('Y-m-d');
                break;
            default:
                $d_start = $d_start ?? now()->startOfMonth()->format('Y-m-d');
                $d_end = $d_end ?? now()->endOfMonth()->format('Y-m-d');
                break;
        }

        // $customers = Person::whereType('customers')->orderBy('name')->take(100)->get()->transform(function($row) {
        //     return [
        //         'id' => $row->id,
        //         'description' => $row->number.' - '.$row->name,
        //         'name' => $row->name,
        //         'number' => $row->number,
        //         'identity_document_type_id' => $row->identity_document_type_id,
        //     ];
        // });

        $documentData = $this->document_totals($establishment_id, $d_start, $d_end);
        $balanceData = $this->balance($establishment_id, $d_start, $d_end);

        return [
            'document' => $documentData,
            'sale_note' => $this->sale_note_totals($establishment_id, $d_start, $d_end),
            'general' => $this->totals($establishment_id, $d_start, $d_end, $period, $month_start, $month_end),
            'balance' => $balanceData,
            'items' => $this->getItems($establishment_id, $d_start, $d_end),
            'document_count' => $this->get_document_count($establishment_id, $d_start, $d_end),
        ];
    }

    public function globalData()
    {
        return [
            'total_cpe' => Configuration::first()->quantity_documents,
            'document_total_global' => $this->document_totals_globals(),
            'sale_note_total_global' => $this->sale_note_totals_global(),
        ];
    }

    private function parseDate($date)
    {
        if (!$date) return null;
        try {
            return Carbon::createFromFormat('d/m/Y', $date)->format('Y-m-d');
        } catch (\Exception $e) {
            try {
                return Carbon::parse($date)->format('Y-m-d');
            } catch (\Exception $e) {
                return $date;
            }
        }
    }

    private function parseMonth($month)
    {
        if (!$month) return null;
        try {
            return Carbon::createFromFormat('Y-m', $month)->startOfMonth()->format('Y-m-d');
        } catch (\Exception $e) {
            try {
                return Carbon::createFromFormat('m/Y', $month)->startOfMonth()->format('Y-m-d');
            } catch (\Exception $e) {
                try {
                    return Carbon::parse($month)->startOfMonth()->format('Y-m-d');
                } catch (\Exception $e) {
                    return null;
                }
            }
        }
    }

    /**
     * @param $establishment_id
     * @param $date_start
     * @param $date_end
     * @return array
     */
    private function sale_note_totals($establishment_id, $date_start, $date_end)
    {

        if($date_start && $date_end){
            $sale_notes = SaleNote::query()->where('establishment_id', $establishment_id)
                                           ->where('changed', false)
                                           ->whereStateTypeAccepted()
                                           ->whereBetween('date_of_issue', [$date_start, $date_end])->get();
        }else{
            $sale_notes = SaleNote::query()->where('establishment_id', $establishment_id)
                                           ->where('changed', false)
                                           ->whereStateTypeAccepted()
                                           ->get();
        }

        //PEN
        $sale_note_total_pen = 0;
        $sale_note_total_payment_pen = 0;

        $sale_note_total_pen = collect($sale_notes->where('currency_type_id', 'PEN'))->sum('total');

        //USD
        $sale_note_total_usd = 0;
        $sale_note_total_payment_usd = 0;

        //TWO CURRENCY
        foreach ($sale_notes as $sale_note)
        {

            if($sale_note->currency_type_id == 'PEN'){

                $sale_note_total_payment_pen += collect($sale_note->payments)->sum('payment');

            }else{

                $sale_note_total_usd += $sale_note->total * $sale_note->exchange_rate_sale;
                $sale_note_total_payment_usd += collect($sale_note->payments)->sum('payment') * $sale_note->exchange_rate_sale;

            }
        }

        //TOTALS
        $sale_note_total = $sale_note_total_pen + $sale_note_total_usd;
        $sale_note_total_payment = $sale_note_total_payment_pen + $sale_note_total_payment_usd;

        $sale_note_total_to_pay = $sale_note_total - $sale_note_total_payment;

        return [
            'totals' => [
                'total_payment' => number_format($sale_note_total_payment ?? 0, 2, ".", ""),
                'total_to_pay' => number_format($sale_note_total_to_pay ?? 0, 2, ".", ""),
                'total' => number_format($sale_note_total ?? 0, 2, ".", ""),
            ],
            'graph' => [
                'labels' => ['Pagado', 'Por cobrar'],
                'datasets' => [
                    [
                        'label' => 'NV',
                        'data' => [round($sale_note_total_payment,2), round($sale_note_total_to_pay,2)],
                        'backgroundColor' => [
                            'rgb(36, 71, 232, .1)',
                            'rgb(254, 0, 108, .1)',
                        ],
                        'borderColor' => [
                            'rgb(36, 71, 232)',
                            'rgb(254, 0, 108)',
                        ]
                    ]
                ],
            ]
        ];
    }


    /**
     * 
     * Obtener totales de cpe
     * 
     * Usado en:
     * App\Traits\LockedEmissionTrait - Control de limite de ventas mensual
     *
     * @param  string $start_date
     * @param  string $end_date
     * @return float
     */
    public function sale_note_totals_global($start_date = null, $end_date = null)
    {
        $sale_notes_query = SaleNote::without(['user', 'soap_type', 'state_type', 'currency_type', 'items'])
            ->where('changed', false)
            ->whereStateTypeAccepted()
            ->select('id', 'currency_type_id', 'total', 'exchange_rate_sale');

        if($start_date && $end_date)
        {
            $sale_notes_query->whereBetween('date_of_issue', [$start_date, $end_date]);
        }

        $sale_notes = $sale_notes_query->get();

        //PEN
        $sale_note_total_pen = 0;
        $sale_note_total_payment_pen = 0;

        $sale_note_total_pen = collect($sale_notes->where('currency_type_id', 'PEN'))->sum('total');

        //USD
        $sale_note_total_usd = 0;
        $sale_note_total_payment_usd = 0;

        //TWO CURRENCY
        foreach ($sale_notes as $sale_note)
        {

            if($sale_note->currency_type_id == 'PEN'){

                $sale_note_total_payment_pen += collect($sale_note->payments)->sum('payment');

            }else{

                $sale_note_total_usd += $sale_note->total * $sale_note->exchange_rate_sale;
                $sale_note_total_payment_usd += collect($sale_note->payments)->sum('payment') * $sale_note->exchange_rate_sale;

            }
        }

        //TOTALS
        $sale_note_total = $sale_note_total_pen + $sale_note_total_usd;

        return number_format($sale_note_total,2, ".", "");
    }

    /**
     * @param $establishment_id
     * @param $date_start
     * @param $date_end
     * @return array
     */
    private function document_totals($establishment_id, $date_start, $date_end)
    {

        if($date_start && $date_end){
            $documents = Document::query()
                ->where('establishment_id', $establishment_id)
                ->whereBetween('date_of_issue', [$date_start, $date_end])
                ->whereIn('state_type_id', ['01','03','05','07','13'])
                ->get();
        }else{
            $documents = Document::query()
                ->where('establishment_id', $establishment_id)
                ->whereIn('state_type_id', ['01','03','05','07','13'])
                ->get();
        }
        //PEN
        $document_total_pen = 0;
        $document_total_payment_pen = 0;
        $document_total_note_credit_pen = 0;

        $document_total_pen = collect($documents->whereIn('state_type_id', ['01','03','05','07','13'])->whereIn('document_type_id', ['01','03','08']))->where('currency_type_id', 'PEN')->sum('total');


        //USD
        $document_total_usd = 0;
        $document_total_note_credit_usd = 0;
        $document_total_payment_usd = 0;

        $documents_usd = $documents->whereIn('state_type_id', ['01','03','05','07','13'])
                                    ->whereIn('document_type_id', ['01','03','08'])
                                    ->where('currency_type_id', 'USD');

        foreach ($documents_usd as $dusd) {
            $document_total_usd += $dusd->total * $dusd->exchange_rate_sale;
        }

        //TWO CURRENCY

        foreach ($documents as $document)
        {
            if($document->currency_type_id == 'PEN'){

                if(in_array($document->state_type_id,['01','03','05','07','13'])){

                    $document_total_payment_pen += collect($document->payments)->sum('payment');
                    $document_total_note_credit_pen += ($document->document_type_id == '07') ? $document->total:0; //nota de credito

                }


            }else{

                if(in_array($document->state_type_id,['01','03','05','07','13'])){

                    $document_total_payment_usd += collect($document->payments)->sum('payment') * $document->exchange_rate_sale;
                    $document_total_note_credit_usd += ($document->document_type_id == '07') ? $document->total * $document->exchange_rate_sale:0; //nota de credito

                }

            }

        }

        //TOTALS
        $document_total = $document_total_pen + $document_total_usd;
        $document_total_note_credit = $document_total_note_credit_pen + $document_total_note_credit_usd;
        $document_total_payment = $document_total_payment_pen + $document_total_payment_usd;

        $document_total = round(($document_total - $document_total_note_credit),2);
        $document_total_to_pay = $document_total - $document_total_payment;

        // dd($document_total , $document_total_payment);
        // dd($document_total, $document_total_pen, $document_total_note_credit, $document_total_payment, $document_total_to_pay);

        return [
            'totals' => [
                'total_payment' => number_format($document_total_payment ?? 0, 2, ".", ""),
                'total_to_pay' => number_format($document_total_to_pay ?? 0, 2, ".", ""),
                'total' => number_format($document_total ?? 0, 2, ".", ""),
            ],
            'graph' => [
                'labels' => ['Pagado', 'Por cobrar'],
                'datasets' => [
                    [
                        'label' => 'CPE',
                        'data' => [round($document_total_payment,2), round($document_total_to_pay,2)],
                        'backgroundColor' => [
                            'rgb(36, 71, 232, .1)',
                            'rgb(254, 0, 108, .1)',
                        ],
                        'borderColor' => [
                            'rgb(36, 71, 232)',
                            'rgb(254, 0, 108)',
                        ],
                    ]
                ],
            ]
        ];
    }
    
    
    /**
     * 
     * Obtener totales de cpe
     * 
     * Usado en:
     * App\Traits\LockedEmissionTrait - Control de limite de ventas mensual
     *
     * @param  string $start_date
     * @param  string $end_date
     * @return float
     */
    public function document_totals_globals($start_date = null, $end_date = null)
    {
        $documents_query = Document::without(['user', 'soap_type', 'state_type', 'document_type', 'currency_type', 'group', 'items', 'invoice', 'note'])
                                    ->select('id', 'state_type_id', 'document_type_id', 'currency_type_id', 'total', 'exchange_rate_sale');


        if($start_date && $end_date)
        {
            $documents_query->whereBetween('date_of_issue', [$start_date, $end_date]);
        }

        $documents = $documents_query->get();
        
        //PEN
        $document_total_pen = 0;
        $document_total_payment_pen = 0;
        $document_total_note_credit_pen = 0;

        $document_total_pen = collect($documents->whereIn('state_type_id', ['01','03','05','07','13'])->whereIn('document_type_id', ['01','03','08']))->where('currency_type_id', 'PEN')->sum('total');


        //USD
        $document_total_usd = 0;
        $document_total_note_credit_usd = 0;
        $document_total_payment_usd = 0;

        $documents_usd = $documents->whereIn('state_type_id', ['01','03','05','07','13'])
                                    ->whereIn('document_type_id', ['01','03','08'])
                                    ->where('currency_type_id', 'USD');

        foreach ($documents_usd as $dusd) {
            $document_total_usd += $dusd->total * $dusd->exchange_rate_sale;
        }

        //TWO CURRENCY

        foreach ($documents as $document)
        {
            if($document->currency_type_id == 'PEN'){

                if(in_array($document->state_type_id,['01','03','05','07','13'])){

                    $document_total_payment_pen += collect($document->payments)->sum('payment');
                    $document_total_note_credit_pen += ($document->document_type_id == '07') ? $document->total:0; //nota de credito

                }


            }else{

                if(in_array($document->state_type_id,['01','03','05','07','13'])){

                    $document_total_payment_usd += collect($document->payments)->sum('payment') * $document->exchange_rate_sale;
                    $document_total_note_credit_usd += ($document->document_type_id == '07') ? $document->total * $document->exchange_rate_sale:0; //nota de credito

                }

            }

        }

        //TOTALS
        $document_total = $document_total_pen + $document_total_usd;
        $document_total_note_credit = $document_total_note_credit_pen + $document_total_note_credit_usd;

        $document_total = round(($document_total - $document_total_note_credit),2);

        return number_format($document_total,2, ".", "");
    }

    /**
     * @param $establishment_id
     * @param $date_start
     * @param $date_end
     * @param $period
     * @param $month_start
     * @param $month_end
     * @return array
     */
    private function totals($establishment_id, $date_start, $date_end, $period, $month_start, $month_end)
    {

        if($date_start && $date_end){
            $sale_notes = SaleNote::query()->where('establishment_id', $establishment_id)
                                           ->where('changed', false)
                                           ->whereBetween('date_of_issue', [$date_start, $date_end])
                                           ->whereStateTypeAccepted()
                                           ->get();

            $documents = Document::query()->where('establishment_id', $establishment_id)->whereBetween('date_of_issue', [$date_start, $date_end])->get();

        }else{
            $sale_notes = SaleNote::query()->where('establishment_id', $establishment_id)
                                           ->where('changed', false)
                                           ->whereStateTypeAccepted()
                                           ->get();

            $documents = Document::query()->where('establishment_id', $establishment_id)->get();
        }





        //DOCUMENT
        //PEN
        $document_total_pen = 0;
        $document_total_note_credit_pen = 0;

        $document_total_pen = collect($documents->whereIn('state_type_id', ['01','03','05','07','13'])->whereIn('document_type_id', ['01','03','08']))->where('currency_type_id', 'PEN')->sum('total');

        //USD
        $document_total_usd = 0;
        $document_total_note_credit_usd = 0;

        $documents_usd = $documents->whereIn('state_type_id', ['01','03','05','07','13'])
                                    ->whereIn('document_type_id', ['01','03','08'])
                                    ->where('currency_type_id', 'USD');

        foreach ($documents_usd as $dusd) {
            $document_total_usd += $dusd->total * $dusd->exchange_rate_sale;
        }

        //TWO CURRENCY

        foreach ($documents as $document)
        {

            if(in_array($document->state_type_id, ['01','03','05','07','13'])){

                if($document->currency_type_id == 'PEN'){
                    $document_total_note_credit_pen += ($document->document_type_id == '07') ? $document->total:0; //nota de credito
                }else{
                    $document_total_note_credit_usd += ($document->document_type_id == '07') ? $document->total * $document->exchange_rate_sale:0; //nota de credito
                }
            }

        }

        $document_total = $document_total_pen + $document_total_usd;
        $document_total_note_credit = $document_total_note_credit_pen + $document_total_note_credit_usd;

        $documents_total = $document_total - $document_total_note_credit;

        // dd($document_total_pen , $document_total_usd, $document_total_note_credit_pen);

        //DOCUMENT

        //SALE NOTE

        //PEN
        $sale_note_total_pen = 0;

        $sale_note_total_pen = collect($sale_notes->where('currency_type_id', 'PEN'))->sum('total');

        //USD
        $sale_note_total_usd = 0;

        //TWO CURRENCY
        foreach ($sale_notes as $sale_note)
        {
            if($sale_note->currency_type_id == 'USD'){
                $sale_note_total_usd += $sale_note->total * $sale_note->exchange_rate_sale;
            }
        }

        //TOTALS
        $sale_notes_total = $sale_note_total_pen + $sale_note_total_usd;

        //SALE NOTE

        $total = $sale_notes_total + $documents_total;

        // dd($period, $month_start, $month_end);

        // if(in_array($period, ['month', 'between_months'])) {
        //     if($month_start === $month_end) {
        //         $data_array = $this->getDocumentsByDays($sale_notes, $documents, $date_start, $date_end);
        //     } else {
        //         $data_array = $this->getDocumentsByMonths($sale_notes, $documents, $month_start, $month_end);
        //     }
        // }

        if($period == 'month')
        {
            $data_array = $this->getDocumentsByDays($sale_notes, $documents, $date_start, $date_end);
        }
        else if($period == 'between_months' && $month_start === $month_end)
        {
            $data_array = $this->getDocumentsByDays($sale_notes, $documents, $date_start, $date_end);
        }
        else if($period == 'between_months')
        {
            $data_array = $this->getDocumentsByMonths($sale_notes, $documents, $month_start, $month_end);
        }
        else
        {
            if($date_start === $date_end) {
                $data_array = $this->getDocumentsByHours($sale_notes, $documents);
            } else {
                $data_array = $this->getDocumentsByDays($sale_notes, $documents, $date_start, $date_end);
            }
        }

        return [
            'totals' => [
                'total_documents' => number_format($documents_total,2, ".", ""),
                'total_sale_notes' => number_format($sale_notes_total,2, ".", ""),
                'total' => number_format($total,2, ".", ""),
            ],
            'graph' => [
                'labels' => array_keys($data_array['total_array']),
                'datasets' => [
                    [
                        'label' => 'Total notas de venta',
                        'data' => array_values($data_array['sale_notes_array']),
                        'backgroundColor' => 'rgba(254, 0, 108, .1)',
                        'borderColor' => 'rgb(254, 0, 108)',
                        'borderWidth' => 2,
                        'fill' => true,
                        'lineTension' => 0.3,
                        'pointRadius' => 1
                    ],
                    [
                        'label' => 'Total comprobantes',
                        'data' => array_values($data_array['documents_array']),
                        'backgroundColor' => 'rgba(36, 71, 232, .1)',
                        'borderColor' => 'rgb(36, 71, 232)',
                        'borderWidth' => 2,
                        'fill' => true,
                        'lineTension' => 0.3,
                        'pointRadius' => 1
                    ],
                    [
                        'label' => 'Total',
                        'data' => array_values($data_array['total_array']),
                        'backgroundColor' => 'rgba(177, 184, 194, .1)',
                        'borderColor' => 'rgb(177, 184, 194)',
                        'borderWidth' => 2,
                        'fill' => true,
                        'lineTension' => 0.3,
                        'pointRadius' => 1
                    ]

                ],
            ]
        ];
    }

    private function getDocumentsByHours($sale_notes, $documents)
    {
        $sale_notes_array = [];
        $documents_array = [];
        $total_array = [];
        $document_total = 0;
        $document_total_note_credit = 0;

        $h_start = 0;
        $h_end = 23;

        for ($h = $h_start; $h <= $h_end; $h++)
        {
            $h_format = str_pad($h, 2, '0', STR_PAD_LEFT);

            //SALE NOTE
            $sale_note_total_pen = 0;
            $sale_note_total_col_usd = [];
            $sale_note_total_usd = 0;

            $sale_note_total_pen = $sale_notes->filter(function ($row) use($h_format) {
                return substr($row->time_of_issue, 0, 2) === $h_format;
            })->where('currency_type_id', 'PEN')->sum('total');

            $sale_note_total_col_usd = $sale_notes->filter(function ($row) use($h_format) {
                return substr($row->time_of_issue, 0, 2) === $h_format;
            })->where('currency_type_id', 'USD');

            foreach ($sale_note_total_col_usd as $sn) {
                $sale_note_total_usd += $sn->total * $sn->exchange_rate_sale;
            }

            $sale_note_total = $sale_note_total_pen + $sale_note_total_usd;
            $sale_notes_array[$h_format.'h'] = round($sale_note_total, 2);

            //SALE NOTE


            //DOCUMENT
            $document_total_pen = 0;
            $document_total_col_usd = [];
            $document_total_usd = 0;
            $document_total_nc_col_usd = [];
            $document_total_note_credit_usd = 0;

            $document_total_pen = $documents->filter(function ($row) use($h_format) {
                return substr($row->time_of_issue, 0, 2) === $h_format;
            })->whereIn('state_type_id', ['01','03','05','07','13'])->where('currency_type_id', 'PEN')->whereIn('document_type_id', ['01','03','08'])->sum('total');

            $document_total_col_usd = $documents->filter(function ($row) use($h_format) {
                return substr($row->time_of_issue, 0, 2) === $h_format;
            })->whereIn('state_type_id', ['01','03','05','07','13'])->where('currency_type_id', 'USD')->whereIn('document_type_id', ['01','03','08']);

            foreach ($document_total_col_usd as $doc) {
                $document_total_usd += $doc->total * $doc->exchange_rate_sale;
            }

            //NC
            $document_total_note_credit_pen = $documents->filter(function ($row) use($h_format) {
                return substr($row->time_of_issue, 0, 2) === $h_format;
            })->whereIn('state_type_id', ['01','03','05','07','13'])->where('document_type_id', '07')->where('currency_type_id', 'PEN')->sum('total');

            $document_total_nc_col_usd = $documents->filter(function ($row) use($h_format) {
                return substr($row->time_of_issue, 0, 2) === $h_format;
            })->whereIn('state_type_id', ['01','03','05','07','13'])->where('document_type_id', '07')->where('currency_type_id', 'USD');

            foreach ($document_total_nc_col_usd as $docnc) {
                $document_total_note_credit_usd += $docnc->total * $docnc->exchange_rate_sale;
            }

            $d_total = $document_total_pen + $document_total_usd;
            $d_total_nc = $document_total_note_credit_pen + $document_total_note_credit_usd;

            $document_total = $d_total - $d_total_nc;
            //DOCUMENT

            $documents_array[$h_format.'h'] = round($document_total, 2);

            $total_array[$h_format.'h'] = round($sale_note_total + $document_total,2);
        }

        return compact('sale_notes_array', 'documents_array', 'total_array');
    }

    private function getDocumentsByDays($sale_notes, $documents, $date_start, $date_end)
    {
        $sale_notes_array = [];
        $documents_array = [];
        $total_array = [];
        $document_total = 0;
        $document_total_note_credit = 0;

        $d_start = Carbon::parse($date_start);
        $d_end = Carbon::parse($date_end);

        while ($d_start <= $d_end)
        {

            //SALE NOTE
            $sale_note_total_pen = collect($sale_notes->where('currency_type_id', 'PEN'))->where('date_of_issue', $d_start)->sum('total');

            $sale_note_total_usd = collect($sale_notes->where('currency_type_id', 'USD'))->where('date_of_issue', $d_start)->map(function ($item, $key) {
                return $item->total * $item->exchange_rate_sale;
            })->sum();

            $sale_note_total = round($sale_note_total_pen + $sale_note_total_usd, 2);
            $sale_notes_array[$d_start->format('d').'d'] = $sale_note_total;

            //DOCUMENT
            $document_total_pen = collect($documents)->whereIn('state_type_id', ['01','03','05','07','13'])
                                                 ->whereIn('document_type_id', ['01','03','08'])
                                                 ->where('currency_type_id', 'PEN')
                                                 ->where('date_of_issue', $d_start)->sum('total');

            $document_total_usd = collect($documents)->whereIn('state_type_id', ['01','03','05','07','13'])
                                                 ->whereIn('document_type_id', ['01','03','08'])
                                                 ->where('currency_type_id', 'USD')
                                                 ->where('date_of_issue', $d_start)
                                                 ->map(function ($item, $key) {
                                                    return $item->total * $item->exchange_rate_sale;
                                                 })->sum();

            $document_total_note_credit_pen = collect($documents)->where('document_type_id', '07')
                                                            ->whereIn('state_type_id', ['01','03','05','07','13'])
                                                            ->where('currency_type_id', 'PEN')
                                                            ->where('date_of_issue', $d_start)
                                                            ->sum('total');

            $document_total_note_credit_usd = collect($documents)->where('document_type_id', '07')
                                                            ->whereIn('state_type_id', ['01','03','05','07','13'])
                                                            ->where('currency_type_id', 'USD')
                                                            ->where('date_of_issue', $d_start)
                                                            ->map(function ($item, $key) {
                                                                return $item->total * $item->exchange_rate_sale;
                                                            })->sum();


            $d_total = $document_total_pen + $document_total_usd;
            $d_total_note_credit = $document_total_note_credit_pen + $document_total_note_credit_usd;

            $document_total = round($d_total - $d_total_note_credit,2);

            $documents_array[$d_start->format('d').'d'] = $document_total;

            $total_array[$d_start->format('d').'d'] = round($sale_note_total + $document_total ,2);

            $d_start = $d_start->addDay();
        }

        return compact('sale_notes_array', 'documents_array', 'total_array');
    }

    private function getDocumentsByMonths($sale_notes, $documents, $month_start, $month_end)
    {
        $sale_notes_array = [];
        $documents_array = [];
        $total_array = [];
        $document_total = 0;
        $document_total_note_credit = 0;

        $m_start = (int) substr($month_start, 5, 2);
        $m_end = (int) substr($month_end, 5, 2);
//        dd($m_start);
        for ($m = $m_start; $m <= $m_end; $m++)
        {
            $m_format = str_pad($m, 2, '0', STR_PAD_LEFT);

            //SALE NOTE
            $sale_note_total_pen = 0;
            $sale_note_total_col_usd = [];
            $sale_note_total_usd = 0;

            $sale_note_total_pen = $sale_notes->where('currency_type_id', 'PEN')->filter(function ($row) use($m_format) {
                return $row->date_of_issue->format('m') === $m_format;
            })->sum('total');

            $sale_note_total_col_usd = $sale_notes->filter(function ($row) use($m_format) {
                return $row->date_of_issue->format('m') === $m_format;
            })->where('currency_type_id', 'USD');

            foreach ($sale_note_total_col_usd as $sn) {
                $sale_note_total_usd += $sn->total * $sn->exchange_rate_sale;
            }

            $sale_note_total = round($sale_note_total_pen + $sale_note_total_usd, 2);

            $sale_notes_array[$m_format.'m'] = $sale_note_total;


            //DOCUMENT
            $document_total_pen = 0;
            $document_total_col_usd = [];
            $document_total_usd = 0;
            $document_total_nc_col_usd = [];
            $document_total_note_credit_usd = 0;

            $document_total_pen = $documents->filter(function ($row) use($m_format) {
                return $row->date_of_issue->format('m') === $m_format;
            })->whereIn('state_type_id', ['01','03','05','07','13'])->where('currency_type_id', 'PEN')
            ->whereIn('document_type_id', ['01','03','08'])->sum('total');

            $document_total_col_usd = $documents->filter(function ($row) use($m_format) {
                return $row->date_of_issue->format('m') === $m_format;
            })->whereIn('state_type_id', ['01','03','05','07','13'])->where('currency_type_id', 'USD')->whereIn('document_type_id', ['01','03','08']);

            foreach ($document_total_col_usd as $doc) {
                $document_total_usd += $doc->total * $doc->exchange_rate_sale;
            }

            //NC
            $document_total_note_credit_pen = $documents->filter(function ($row) use($m_format) {
                return $row->date_of_issue->format('m') === $m_format;
            })->whereIn('state_type_id', ['01','03','05','07','13'])->where('document_type_id', '07')->where('currency_type_id', 'PEN')->sum('total');

            $document_total_nc_col_usd = $documents->filter(function ($row) use($m_format) {
                return $row->date_of_issue->format('m') === $m_format;
            })->whereIn('state_type_id', ['01','03','05','07','13'])->where('document_type_id', '07')->where('currency_type_id', 'USD');

            foreach ($document_total_nc_col_usd as $docnc) {
                $document_total_note_credit_usd += $docnc->total * $docnc->exchange_rate_sale;
            }

            $d_total = $document_total_pen + $document_total_usd;
            $d_total_nc = $document_total_note_credit_pen + $document_total_note_credit_usd;

            $document_total = $d_total - $d_total_nc;
            //DOCUMENT

            $documents_array[$m_format.'m'] = round($document_total, 2);

            $total_array[$m_format.'m'] = round($sale_note_total + $document_total, 2);
        }

        return compact('sale_notes_array', 'documents_array', 'total_array');
    }





    private function balance($establishment_id, $date_start, $date_end){

        $document = $this->get_document_totals($establishment_id, $date_start, $date_end);
        $sale_note = $this->get_sale_note_totals($establishment_id, $date_start, $date_end);
        $purchase = $this->get_purchase_totals($establishment_id, $date_start, $date_end);
        $expense = $this->get_expense_totals($establishment_id, $date_start, $date_end);

        $response_totals_document = $document['totals'];
        $response_totals_sale_note = $sale_note['totals'];
        $response_totals_purchase = $purchase['totals'];
        $response_totals_expense = $expense['totals'];

        // dd($response_totals_document, $response_totals_sale_note, $response_totals_purchase, $response_totals_expense);

        $total_document =  $response_totals_document['total'];
        $total_payment_document =  $response_totals_document['total_payment'];

        $total_sale_note =  $response_totals_sale_note['total'];
        $total_payment_sale_note =  $response_totals_sale_note['total_payment'];

        $total_purchase = $response_totals_purchase['total'];
        $total_payment_purchase = $response_totals_purchase['total_payment'];

        $total_expense = $response_totals_expense['total'];
        $total_payment_expense = $response_totals_expense['total_payment'];

        $all_totals = $total_document + $total_sale_note - $total_expense - $total_purchase;
        $all_totals_payment = $total_payment_document + $total_payment_sale_note - $total_payment_purchase - $total_payment_expense;

        // Ingresos totales del periodo (ventas cobradas)
        $total_payment = $total_payment_document + $total_payment_sale_note;
        // Utilidad / balance del periodo
        $balance = $all_totals_payment;

        return [
            'totals' => [
                'total_payment' => number_format($total_payment ?? 0, 2, '.', ''),
                'balance' => number_format($balance ?? 0, 2, '.', ''),
                'total_document' => number_format($total_document ?? 0, 2, '.', ''),
                'total_payment_document' => number_format($total_payment_document ?? 0, 2, '.', ''),
                'total_sale_note' => number_format($total_sale_note ?? 0, 2, '.', ''),
                'total_payment_sale_note' => number_format($total_payment_sale_note ?? 0, 2, '.', ''),
                'total_purchase' => number_format($total_purchase ?? 0, 2, '.', ''),
                'total_payment_purchase' => number_format($total_payment_purchase ?? 0, 2, '.', ''),
                'total_expense' => number_format($total_expense ?? 0, 2, '.', ''),
                'total_payment_expense' => number_format($total_payment_expense ?? 0, 2, '.', ''),
                'all_totals' => number_format($all_totals ?? 0, 2, '.', ''),
                'all_totals_payment' => number_format($all_totals_payment ?? 0, 2, '.', ''),
            ],
            'graph' => [
                'labels' => ['Totales', 'Total pagos'],
                'datasets' => [
                    [
                        'label' => 'Grafico',
                        'data' => [round($all_totals,2), round($all_totals_payment,2)],
                        'backgroundColor' => [
                            'rgb(36, 71, 232, .1)',
                            'rgb(254, 0, 108, .1)',
                        ],
                        'borderColor' => [
                            'rgb(36, 71, 232)',
                            'rgb(254, 0, 108)',
                        ],
                    ]
                ],
            ]
        ];
    }

    /**
     * Cantidad de comprobantes (CPE) en el periodo
     */
    private function get_document_count($establishment_id, $date_start, $date_end)
    {
        if ($establishment_id === null) {
            return 0;
        }

        $query = Document::query()
            ->where('establishment_id', $establishment_id)
            ->whereIn('state_type_id', ['01', '03', '05', '07', '13'])
            ->whereIn('document_type_id', ['01', '03', '08']);

        if ($date_start && $date_end) {
            $query->whereBetween('date_of_issue', [$date_start, $date_end]);
        }

        return (int) $query->count();
    }

    /**
     * Items con total según documentación del dashboard
     */
    public function getItems($establishment_id = null, $date_start = null, $date_end = null)
    {
        $items = Item::orderBy('description')->take(20)->get()->transform(function ($row) use ($establishment_id, $date_start, $date_end) {
            $total = '0.00';
            if ($establishment_id && $date_start && $date_end) {
                $total = $this->getItemTotalForPeriod($row->id, $establishment_id, $date_start, $date_end);
            }

            return [
                'id' => $row->internal_id ?? (string) $row->id,
                'description' => ($row->internal_id) ? "{$row->internal_id} - {$row->description}" : $row->description,
                'total' => $total,
            ];
        });

        return $items;
    }

    /**
     * Total vendido de un item en el periodo (documentos + notas de venta)
     * Usa modelos Eloquent para respetar la conexión tenant
     */
    private function getItemTotalForPeriod($item_id, $establishment_id, $date_start, $date_end)
    {
        $documentItems = \App\Models\Tenant\DocumentItem::query()
            ->where('item_id', $item_id)
            ->whereHas('document', function ($q) use ($establishment_id, $date_start, $date_end) {
                $q->where('establishment_id', $establishment_id)
                    ->whereIn('state_type_id', ['01', '03', '05', '07', '13'])
                    ->whereIn('document_type_id', ['01', '03', '08'])
                    ->whereBetween('date_of_issue', [$date_start, $date_end]);
            })
            ->with('document:id,exchange_rate_sale')
            ->get();

        $documentTotal = 0;
        foreach ($documentItems as $item) {
            $rate = $item->document->exchange_rate_sale ?? 1;
            $documentTotal += (float) $item->total * (float) $rate;
        }

        $saleNoteItems = \App\Models\Tenant\SaleNoteItem::query()
            ->where('item_id', $item_id)
            ->whereHas('sale_note', function ($q) use ($establishment_id, $date_start, $date_end) {
                $q->where('establishment_id', $establishment_id)
                    ->where('changed', false)
                    ->whereIn('state_type_id', ['01', '03', '05', '07', '13'])
                    ->whereBetween('date_of_issue', [$date_start, $date_end]);
            })
            ->with('sale_note:id,exchange_rate_sale')
            ->get();

        $saleNoteTotal = 0;
        foreach ($saleNoteItems as $item) {
            $rate = $item->sale_note->exchange_rate_sale ?? 1;
            $saleNoteTotal += (float) $item->total * (float) $rate;
        }

        return number_format((float) ($documentTotal + $saleNoteTotal), 2, '.', '');
    }

    public function data_mobile($request)
    {
        $establishment_id = $request['establishment_id'];
        $period = $request['period'];
        $date_start = $request['date_start'];
        $date_end = $request['date_end'];
        $month_start = $request['month_start'];
        $month_end = $request['month_end'];

        $d_start = null;
        $d_end = null;

        /** @todo: Eliminar periodo, fechas y cambiar por

        $date_start = $request['date_start'];
        $date_end = $request['date_end'];
        \App\CoreFacturalo\Helpers\Functions\FunctionsHelper\FunctionsHelper::setDateInPeriod($request, $date_start, $date_end);
         */
        switch ($period) {
            case 'month':
                $d_start = Carbon::parse($month_start.'-01')->format('Y-m-d');
                $d_end = Carbon::parse($month_start.'-01')->endOfMonth()->format('Y-m-d');
                break;
            case 'between_months':
                $d_start = Carbon::parse($month_start.'-01')->format('Y-m-d');
                $d_end = Carbon::parse($month_end.'-01')->endOfMonth()->format('Y-m-d');
                break;
            case 'date':
                $d_start = $date_start;
                $d_end = $date_start;
                break;
            case 'between_dates':
                $d_start = $date_start;
                $d_end = $date_end;
                break;
        }

        return [
            'general' => $this->totals($establishment_id, $d_start, $d_end, $period, $month_start, $month_end),
        ];
    }
    

    /**
     * 
     * Método para acceder a los totales (método privado)
     * El gráfico no incluye pedidos
     * 
     * Usado en:
     * ReportController - App
     *
     * @param  int $establishment_id
     * @param  string $d_start
     * @param  string $d_end
     * @param  string $period
     * @param  string $month_start
     * @param  string $month_end
     * @return array
     */
    public function getGeneralTotals($establishment_id, $d_start, $d_end, $period, $month_start, $month_end)
    {
        $data = $this->totals($establishment_id, $d_start, $d_end, $period, $month_start, $month_end);

        $total_order_notes = $this->getTotalsOrderNote($establishment_id, $d_start, $d_end);
        
        $data['totals']['total_order_notes'] = $this->roundNumber($total_order_notes);
        $data['totals']['total'] = $this->roundNumber($total_order_notes + (float) $data['totals']['total']);

        return $data;
    }


}
