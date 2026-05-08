<?php

namespace App\Http\Controllers\Tenant\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Tenant\{
    Quotation,
    Company
};
use App\Http\Resources\Tenant\QuotationCollection;
use App\Http\Controllers\Tenant\QuotationController as QuotationControllerWeb;
use App\Mail\Tenant\QuotationEmail;
use App\Http\Controllers\Tenant\EmailController;


class QuotationController extends Controller
{
    /**
     * Lista paginada de cotizaciones (misma lógica y filtros que GET /quotations/records en WEB).
     */
    public function records(Request $request)
    {
        return app(QuotationControllerWeb::class)->records($request);
    }

    /**
     * 
     * Listado de cotizaciones (app)
     *
     * @param  Request $request
     * @return array
     */
    public function list(Request $request)
    {
        // $records = Quotation::orderBy('prefix', 'desc')->take(50)->get();
        // $records = new QuotationCollection($records); // crear nuevo collection para apis

        $records = Quotation::where('id','like', "%{$request->input}%")
                            ->take(config('tenant.items_per_page'))
                            ->latest()
                            ->get();

        return new QuotationCollection($records);
    }

    public function records(Request $request)
    {
        $per_page = (int) $request->get('per_page', 10);
        if ($per_page < 1) {
            $per_page = 10;
        }
        if ($per_page > 200) {
            $per_page = 200;
        }

        $records = Quotation::query();

        $user = auth()->user();
        if ($user && $user->type === 'seller') {
            $records->where('user_id', $user->id);
        }

        $input = $request->get('input');
        if (!is_null($input) && $input !== '') {
            $records->where(function ($q) use ($input) {
                $q->where('id', 'like', "%{$input}%")
                    ->orWhere('prefix', 'like', "%{$input}%")
                    ->orWhereHas('person', function ($personQuery) use ($input) {
                        $personQuery->where('name', 'like', "%{$input}%")
                            ->orWhere('number', 'like', "%{$input}%");
                    });
            });
        }

        $d_start = $request->get('d_start');
        $d_end = $request->get('d_end');
        if (!is_null($d_start) && $d_start !== '' && !is_null($d_end) && $d_end !== '') {
            $records->whereBetween('date_of_issue', [$d_start, $d_end]);
        }

        $date_of_issue = $request->get('date_of_issue');
        if (!is_null($date_of_issue) && $date_of_issue !== '') {
            $records->whereDate('date_of_issue', $date_of_issue);
        }

        $state_type_id = $request->get('state_type_id');
        if (!is_null($state_type_id) && $state_type_id !== '') {
            $records->where('state_type_id', $state_type_id);
        }

        $customer_id = $request->get('customer_id');
        if (!is_null($customer_id) && $customer_id !== '') {
            $records->where('customer_id', $customer_id);
        }

        $seller_id = $request->get('seller_id');
        if (!is_null($seller_id) && $seller_id !== '') {
            $records->where('seller_id', $seller_id);
        }

        $prefix = $request->get('prefix');
        if (!is_null($prefix) && $prefix !== '') {
            $records->where('prefix', 'like', "%{$prefix}%");
        }

        $number = $request->get('number');
        if (!is_null($number) && $number !== '') {
            $records->where('id', $number);
        }

        return new QuotationCollection($records->latest('id')->paginate($per_page));
    }


    public function store(Request $request)
    {
        $request['establishment_id'] = $request['establishment_id'] ? $request['establishment_id'] : auth()->user()->establishment_id;

        DB::connection('tenant')->transaction(function () use ($request) {
            $quotation_web = new QuotationControllerWeb;
            $data = $quotation_web->mergeData($request);
            $data['terms_condition'] = $quotation_web->getTermsCondition();

            $this->quotation =  Quotation::create($data);

            foreach ($data['items'] as $row) {
                $this->quotation->items()->create($row);
            }

            $quotation_web->savePayments($this->quotation, $data['payments']);

            $this->setFilename();
            $quotation_web->createPdf($this->quotation, "a4", $this->quotation->filename);
        });

        return [
            'success' => true,
            'data' => [
                'number_full' => $this->quotation->number_full,
                'external_id' => $this->quotation->external_id,
                'filename' => $this->quotation->filename,
                'print_a4'    => url('')."/quotations/print/{$this->quotation->external_id}/a4",
                'print_ticket' => $this->quotation->getUrlPrintPdf('ticket'),
            ],
        ];
    }

    private function setFilename(){

        $name = [$this->quotation->prefix,$this->quotation->id,date('Ymd')];
        $this->quotation->filename = join('-', $name);
        $this->quotation->save();

    }

    
    /**
     *
     * @param  Request $request
     * @return array
     */
    public function email(Request $request)
    {
        $company = Company::active();
        $quotation = Quotation::find($request->id);
        $email = $request->input('email');
        $mailable =  new QuotationEmail($company, $quotation);
        $id = (int) $request->id;
        $sendIt = EmailController::SendMail($email, $mailable, $id, 3);

        return [
            'success' => true,
            'message' => 'Email enviado correctamente.'
        ];
    }

    
    /**
     * 
     * Sirve para evitar error al consultar endpoint desde la app, funcion agregada en prox
     *
     * @return array
     */
    public function tables()
    {
        return [
            'series' => []
        ];
    }

}
