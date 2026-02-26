<?php
namespace App\Http\Controllers\Tenant\Api;

use App\CoreFacturalo\Facturalo;
use App\CoreFacturalo\Helpers\Number\NumberLetter;
use App\CoreFacturalo\Helpers\Storage\StorageDocument;
use App\Http\Controllers\Controller;
use App\Http\Resources\Tenant\DocumentCollection;
use App\Models\Tenant\BankAccount;
use App\Models\Tenant\Company;
use App\Models\Tenant\Document;
use App\Models\Tenant\StateType;
use Exception;
use Facades\App\Http\Controllers\Tenant\DocumentController as DocumentControllerSend;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use App\Jobs\ProcessDocumentPdf;
use App\Jobs\ProcessDocumentEmail;
use Hyn\Tenancy\Environment;

use App\Models\Tenant\Cash;

class DocumentController extends Controller
{
    use StorageDocument;

    public function __construct()
    {
        $this->middleware('input.request:document,api', ['only' => ['store', 'storeServer']]);
    }

    public function store(Request $request)
    {
        // Validación de caja
        if (!$this->validationOpenCash($request)) {
            return [
                'success' => false,
                'message' => 'Ocurrió un error: Caja seleccionada en métodos de pago se encuentra cerrada o no tiene una caja aperturada.'
            ];
        }

        // dd($request->all());
        $fact = DB::connection('tenant')->transaction(function () use ($request) {
            $facturalo = new Facturalo();
            $facturalo->save($request->all());
            $facturalo->createXmlUnsigned();
            $service_pse_xml = $facturalo->servicePseSendXml();
            $facturalo->signXmlUnsigned($service_pse_xml['xml_signed']);
            $facturalo->updateHash($service_pse_xml['hash']);
            $facturalo->updateQr();
            // $facturalo->createPdf();
            $facturalo->senderXmlSignedBill($service_pse_xml['code']);
            // $facturalo->sendEmail();

            return $facturalo;
        });

        $document = $fact->getDocument();
        $response = $fact->getResponse();

        // Dispatch jobs
        $website_id = app(Environment::class)->tenant()->id;
        ProcessDocumentPdf::withChain([
            new ProcessDocumentEmail($document->id, $website_id)
        ])->dispatch($document->id, $website_id);

        // Construir print_data según la estructura documentada
        $printData = $this->buildPrintData($document);

        return [
            'success' => true,
            'data' => [
                'number' => $document->number_full,
                'filename' => $document->filename,
                'external_id' => $document->external_id,
                'state_type_id' => $document->state_type_id,
                'state_type_description' => $this->getStateTypeDescription($document->state_type_id),
                'number_to_letter' => $document->number_to_letter,
                'hash' => $document->hash,
                'qr' => $document->qr,
                'id' => $document->id,
                'print_ticket' =>  $document->getUrlPrintByFormat('ticket'),
            ],
            'data_ws' => [
                'message_text' => "Su comprobante de pago electrónico {$document->number_full} ha sido generado correctamente, puede revisarlo en el siguiente enlace: ".url('')."/print/document/{$document->external_id}/a4"."",
                "pdf_a4_filename" => url('')."/api/document-file/document/{$document->external_id}/a4",
                "full_filename" => $document->filename.".pdf",
                "customer_telephone" => optional($document->person)->telephone
            ],
            'links' => [
                'xml' => $document->download_external_xml,
                'pdf' => $document->download_external_pdf,
                'cdr' => ($response['sent']) ? $document->download_external_cdr : '',
            ],
            'response' => ($response['sent']) ? Arr::except($response, 'sent') : [],
            'print_data' => $printData,
        ];
    }

    public function send(Request $request)
    {
        if ($request->has('external_id')) {
            $external_id = $request->input('external_id');
            $document = Document::where('external_id', $external_id)->first();
            if (!$document) {
                throw new Exception("El documento con código externo {$external_id}, no se encuentra registrado.");
            }
            if ($document->group_id !== '01') {
                throw new Exception("El tipo de documento {$document->document_type_id} es inválido, no es posible enviar.");
            }
            $fact = new Facturalo();
            $fact->setDocument($document);
            $fact->loadXmlSigned();
            $fact->onlySenderXmlSignedBill();
            $response = $fact->getResponse();
            return [
                'success' => true,
                'data' => [
                    'number' => $document->number_full,
                    'filename' => $document->filename,
                    'external_id' => $document->external_id,
                    'state_type_id' => $document->state_type_id,
                    'state_type_description' => $this->getStateTypeDescription($document->state_type_id),
                ],
                'links' => [
                    'cdr' => $document->download_external_cdr,
                ],
                'response' => Arr::except($response, 'sent'),
            ];
        }
    }

    public function validationOpenCash($request)
    {
        // busca una caja chica en el array de pagos
        if ($request->has('payments')) {
            $find_cash = array_search('cash', array_column($request->payments, 'payment_destination_id'));
            // si ha seleccionado una caja chica
            if ($find_cash !== false) {
                // no hay id de la caja seleccionada por lo que si es abierta una nueva será seleccionada como destino
                $cash = Cash::where([['user_id', auth()->user()->id], ['state', true]])->first();
                if (!$cash) {
                    return false;
                }
            }
        }
        return true;
    }

    public function storeServer(Request $request)
    {
        $fact = DB::connection('tenant')->transaction(function () use ($request) {
            $facturalo = new Facturalo();
            $facturalo->save($request->all());

            return $facturalo;
        });

        $document = $fact->getDocument();
        $data_json = $document->data_json;

        // $zipFly = new ZipFly();

        $this->uploadStorage($document->filename, base64_decode($data_json->file_xml_signed), 'signed');
        $this->uploadStorage($document->filename, base64_decode($data_json->file_pdf), 'pdf');

        $document->external_id = $data_json->external_id;
        $document->hash = $data_json->hash;
        $document->qr = $data_json->qr;
        $document->save();

        // Send SUNAT
        if ($document->group_id === '01') {
            if ($data_json->query) {
                DocumentControllerSend::send($document->id);
            }

        }

        return [
            'success' => true,
        ];
    }

    public function documentCheckServer($external_id)
    {
        $document = Document::where('external_id', $external_id)->first();

        if ($document->state_type_id === '05' && $document->group_id === '01') {
            $file_cdr = base64_encode($this->getStorage($document->filename, 'cdr'));
        } else {
            $file_cdr = null;
        }

        return [
            'success' => true,
            'state_type_id' => $document->state_type_id,
            'file_cdr' => $file_cdr,
        ];
    }

    private function getStateTypeDescription($id)
    {
        return StateType::find($id)->description;
    }

    /**
     * Construye la estructura print_data según la documentación ESTRUCTURA_PRINT_DATA.md
     *
     * @param Document $document
     * @return array
     */
    private function buildPrintData($document)
    {
        // Asegurar que las relaciones necesarias estén cargadas
        $document->load([
            'items',
            'payments.payment_method_type',
            'person',
            'document_type',
            'relation_establishment.district',
            'relation_establishment.province',
            'relation_establishment.department',
            'seller',
            'payment_condition'
        ]);

        // Obtener información de la empresa (igual que en el PDF)
        $company = Company::active();
        $companyData = null;
        if ($company) {
            // En Document, establishment es un atributo JSON, no una relación
            $establishment = $document->establishment; // Objeto JSON decodificado
            $establishmentModel = $document->relation_establishment; // Modelo real para datos adicionales

            // Construir dirección completa como en el PDF
            $address = '';
            if ($establishment && isset($establishment->address) && $establishment->address !== '-') {
                $address = $establishment->address;
                if ($establishmentModel && $establishmentModel->district) {
                    $address .= ', ' . $establishmentModel->district->description;
                }
                if ($establishmentModel && $establishmentModel->province) {
                    $address .= ', ' . $establishmentModel->province->description;
                }
                if ($establishmentModel && $establishmentModel->department) {
                    $address .= ' - ' . $establishmentModel->department->description;
                }
            }

            $companyData = [
                'name' => $company->name ?? '',
                'trade_name' => $company->trade_name ?? '',
                'ruc' => $company->number ?? '',
                'address' => $address,
                'commercial_address' => ($establishment && isset($establishment->trade_address) && $establishment->trade_address !== '-') ? $establishment->trade_address : '',
                'phone' => ($establishment && isset($establishment->telephone) && $establishment->telephone !== '-') ? $establishment->telephone : '',
                'email' => ($establishment && isset($establishment->email) && $establishment->email !== '-') ? $establishment->email : '',
                'web' => ($establishment && isset($establishment->web_address) && $establishment->web_address !== '-') ? $establishment->web_address : '',
                'slogan' => ($establishment && isset($establishment->aditional_information) && $establishment->aditional_information !== '-') ? $establishment->aditional_information : '',
                'logo' => null, // Ya no se devuelve el logo en base64
            ];
        }

        // Obtener tipo de documento y moneda
        $documentType = $document->document_type ? $document->document_type->description : '';
        $currencyCode = $document->currency_type_id ?? '';
        $currencySymbol = $document->currency_type ? $document->currency_type->symbol : '';

        // Construir items
        $items = $document->items->map(function ($item) {
            // Obtener el nombre del producto desde el atributo item (objeto JSON)
            $itemName = '';
            $itemCode = '';
            $unitType = 'NIU';
            
            if ($item->item) {
                $itemName = $item->item->description ?? '';
                $itemCode = $item->item->internal_id ?? $item->item->item_code ?? '';
                $unitType = $item->item->unit_type_id ?? 'NIU';
            }
            
            return [
                'code' => $itemCode,
                'cod' => $itemCode,
                'name' => $itemName,
                'description' => $itemName,
                'product_name' => $itemName,
                'quantity' => (float) $item->quantity,
                'qty' => (float) $item->quantity,
                'amount' => (float) $item->quantity,
                'unit' => $unitType,
                'unidad' => $unitType,
                'price' => (float) $item->unit_price,
                'unit_price' => (float) $item->unit_price,
                'sale_unit_price' => (float) $item->unit_price,
                'subtotal' => (float) $item->total,
                'total' => (float) $item->total,
            ];
        })->toArray();

        // Obtener información de pagos detallada
        $payments = $document->payments->map(function($payment) {
            return [
                'name' => $payment->payment_method_type->description ?? '',
                'method' => $payment->payment_method_type->description ?? '',
                'amount' => (float) $payment->payment,
                'reference' => $payment->reference ?? '',
            ];
        })->toArray();

        // Obtener información de pagos
        $firstPayment = $document->payments->first();
        $paymentMethod = null;
        
        if ($firstPayment && $firstPayment->payment_method_type) {
            $paymentMethod = $firstPayment->payment_method_type->description;
        }
        
        // Calcular efectivo (suma de todos los pagos)
        $cash = (float) $document->payments->sum('payment');
        
        // Calcular cambio (suma de todos los cambios o del primero)
        $change = 0;
        if ($firstPayment) {
            $change = (float) ($firstPayment->change ?? 0);
        }

        // Construir fecha con hora si está disponible
        $date = '';
        $issueTime = '';
        if ($document->date_of_issue) {
            if (is_string($document->date_of_issue)) {
                $date = $document->date_of_issue;
            } else {
                $date = $document->date_of_issue->format('Y-m-d');
            }
            
            if ($document->time_of_issue) {
                $issueTime = $document->time_of_issue;
                $date .= ' ' . $issueTime;
            }
        }

        // Fecha de vencimiento
        $dueDate = null;
        if ($document->invoice && isset($document->invoice->date_of_due)) {
            $dueDate = is_string($document->invoice->date_of_due) 
                ? $document->invoice->date_of_due 
                : $document->invoice->date_of_due->format('Y-m-d');
        }

        // Construir datos del cliente si existe
        $customer = null;
        if ($document->person) {
            $customer = [
                'name' => $document->person->name ?? '',
                'number' => $document->person->number ?? '',
                'address' => $document->person->address ?? '',
                'email' => $document->person->email ?? '',
                'telephone' => $document->person->telephone ?? '',
                'doc_trib_no_dom_sin_ruc' => $document->person->number ?? '',
            ];
        }

        // Total en palabras - obtener desde legends directamente (igual que en el PDF)
        // Evitar usar number_to_letter directamente porque puede causar error si legend es null
        $totalInWords = '';
        
        // Acceder directamente al atributo sin usar el accessor que puede causar error
        $legendsRaw = $document->attributes['legends'] ?? null;
        
        if ($legendsRaw !== null) {
            $legendsArray = json_decode($legendsRaw, true);
            if (is_array($legendsArray)) {
                $legend = collect($legendsArray)->where('code', '1000')->first();
                if ($legend && isset($legend['value'])) {
                    $totalInWords = $legend['value'];
                }
            }
        }
        
        // Si no existe en legends, generar automáticamente
        if (empty($totalInWords) && $document->total) {
            $converted = NumberLetter::convertToLetter($document->total, 'Soles');
            if ($converted && $converted !== 'No es posible convertir el numero en letras') {
                $totalInWords = 'Son: ' . ucfirst(trim($converted));
            }
        }

        // Condición de pago
        $paymentCondition = 'Contado';
        if ($document->payment_condition) {
            $paymentCondition = $document->payment_condition->name;
        }

        // Cuentas bancarias
        $bankAccounts = [];
        $bankAccountsData = BankAccount::where('show_in_documents', true)
            ->where('status', 1)
            ->with('bank')
            ->get();
        
        foreach ($bankAccountsData as $bankAccount) {
            if ($bankAccount->bank) {
                $bankAccounts[] = [
                    'bank_name' => $bankAccount->bank->description ?? '',
                    'account_number' => $bankAccount->number ?? '',
                    'cci' => $bankAccount->cci ?? '',
                ];
            }
        }

        // QR Data (solo para boletas y facturas, no para notas de venta)
        $qrData = null;
        if (in_array($document->document_type_id, ['01', '03']) && $document->qr) {
            // Construir URL del QR para consulta SUNAT
            $qrData = $document->qr;
        }

        // Hash code
        $hashCode = $document->hash ?? null;

        // Vendedor
        $seller = null;
        if ($document->seller) {
            $seller = $document->seller->name ?? '';
        }

        return [
            'company' => $companyData,
            'document_type' => $documentType,
            'number' => $document->series."-".str_pad($document->number, 8, '0', STR_PAD_LEFT),
            'date' => $date,
            'date_of_issue' => $date,
            'issue_time' => $issueTime,
            'due_date' => $dueDate,
            'customer' => $customer,
            'items' => $items,

            // Información de moneda
            'currency_type_id' => $currencyCode,
            'currency_code' => $currencyCode,
            'currency_symbol' => $currencySymbol,

            // Totales básicos
            'subtotal' => (float) ($document->total_value ?? 0),
            'total_value' => (float) ($document->total_value ?? 0),
            'total' => (float) ($document->total ?? 0),
            'total_venta' => (float) ($document->total ?? 0),

            // Operaciones por tipo (mismos nombres que en Document)
            'total_taxed' => (float) ($document->total_taxed ?? 0),
            'total_exonerated' => (float) ($document->total_exonerated ?? 0),
            'total_unaffected' => (float) ($document->total_unaffected ?? 0),
            'total_free' => (float) ($document->total_free ?? 0),
            'total_exportation' => (float) ($document->total_exportation ?? 0),

            // Alias para compatibilidad con integraciones existentes
            'taxable_operations' => (float) ($document->total_taxed ?? 0),
            'exonerated_operations' => (float) ($document->total_exonerated ?? 0),
            'unaffected_operations' => (float) ($document->total_unaffected ?? 0),
            'free_operations' => (float) ($document->total_free ?? 0),
            'exportation_operations' => (float) ($document->total_exportation ?? 0),

            // Impuestos
            'tax' => (float) ($document->total_igv ?? 0),
            'total_igv' => (float) ($document->total_igv ?? 0),
            'total_igv_free' => (float) ($document->total_igv_free ?? 0),
            'total_base_isc' => (float) ($document->total_base_isc ?? 0),
            'total_isc' => (float) ($document->total_isc ?? 0),
            'total_base_other_taxes' => (float) ($document->total_base_other_taxes ?? 0),
            'total_other_taxes' => (float) ($document->total_other_taxes ?? 0),
            'total_taxes' => (float) ($document->total_taxes ?? $document->total_igv ?? 0),
            'total_plastic_bag_taxes' => (float) ($document->total_plastic_bag_taxes ?? 0),

            // Descuentos, cargos y anticipos
            'total_discount' => (float) ($document->total_discount ?? 0),
            'total_charge' => (float) ($document->total_charge ?? 0),
            'total_prepayment' => (float) ($document->total_prepayment ?? 0),
            'subtotal_document' => (float) ($document->subtotal ?? 0),

            // Total en letras
            'total_in_words' => $totalInWords,

            // Información de pago
            'payment_method' => $paymentMethod,
            'paymentMethod' => $paymentMethod,
            'payment_method_name' => $paymentMethod,
            'payment_condition' => $paymentCondition,
            'payments' => $payments,
            'cash' => $cash,
            'efectivo' => $cash,
            'change' => $change,
            'vuelto' => $change,

            // Cuentas bancarias y otros datos
            'bank_accounts' => $bankAccounts,
            'qr_data' => $qrData,
            'hash_code' => $hashCode,
            'seller' => $seller,
            'vendedor' => $seller,
        ];
    }

    public function lists($startDate = null, $endDate = null)
    {

        if ($startDate == null)
        {
            $record = Document::whereTypeUser()
                                ->orderBy('date_of_issue', 'desc')
                                ->take(50)
                                ->get();
        }
        else
        {
            $record = Document::whereBetween('date_of_issue', [$startDate, $endDate])
                ->orderBy('date_of_issue', 'desc')
                ->get();
        }

        $records = new DocumentCollection($record);
        return $records;
    }

    public function updatestatus(Request $request)
    {
        $record = Document::whereExternal_id($request->externail_id)->first();
        $record->state_type_id = $request->state_type_id;
        $record->save();

        return [
            'success' => true,
        ];
    }

}
