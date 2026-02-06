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

class DocumentController extends Controller
{
    use StorageDocument;

    public function __construct()
    {
        $this->middleware('input.request:document,api', ['only' => ['store', 'storeServer']]);
    }

    public function store(Request $request)
    {
        // dd($request->all());
        $fact = DB::connection('tenant')->transaction(function () use ($request) {
            $facturalo = new Facturalo();
            $facturalo->save($request->all());
            $facturalo->createXmlUnsigned();
            $service_pse_xml = $facturalo->servicePseSendXml();
            $facturalo->signXmlUnsigned($service_pse_xml['xml_signed']);
            $facturalo->updateHash($service_pse_xml['hash']);
            $facturalo->updateQr();
            $facturalo->createPdf();
            $facturalo->senderXmlSignedBill($service_pse_xml['code']);
            $facturalo->sendEmail();

            return $facturalo;
        });

        $document = $fact->getDocument();
        $response = $fact->getResponse();

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
            
            // Obtener logo (igual que en el PDF)
            $logoBase64 = null;
            $logo = null;
            if ($company->logo) {
                $logo = "storage/uploads/logos/{$company->logo}";
            }
            // Si el establishment tiene logo, se usa ese (igual que en el PDF)
            if ($establishment && isset($establishment->logo) && $establishment->logo) {
                $logo = $establishment->logo;
            }
            
            // Convertir logo a base64 (igual que en el PDF)
            if ($logo && file_exists(public_path($logo))) {
                try {
                    $logoBase64 = base64_encode(file_get_contents(public_path($logo)));
                } catch (\Exception $e) {
                    // Si falla la lectura, dejar null
                    $logoBase64 = null;
                }
            }
            
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
                'ruc' => $company->number ?? '',
                'address' => $address,
                'commercial_address' => ($establishment && isset($establishment->trade_address) && $establishment->trade_address !== '-') ? $establishment->trade_address : '',
                'phone' => ($establishment && isset($establishment->telephone) && $establishment->telephone !== '-') ? $establishment->telephone : '',
                'email' => ($establishment && isset($establishment->email) && $establishment->email !== '-') ? $establishment->email : '',
                'web' => ($establishment && isset($establishment->web_address) && $establishment->web_address !== '-') ? $establishment->web_address : '',
                'slogan' => ($establishment && isset($establishment->aditional_information) && $establishment->aditional_information !== '-') ? $establishment->aditional_information : '',
                'logo' => $logoBase64,
            ];
        }

        // Obtener tipo de documento
        $documentType = $document->document_type ? $document->document_type->description : '';

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
            'subtotal' => (float) ($document->total_value ?? 0),
            'total_value' => (float) ($document->total_value ?? 0),
            'taxable_operations' => (float) ($document->total_taxed ?? 0),
            'tax' => (float) ($document->total_igv ?? 0),
            'total_igv' => (float) ($document->total_igv ?? 0),
            'total_taxes' => (float) ($document->total_igv ?? 0),
            'total' => (float) ($document->total ?? 0),
            'total_venta' => (float) ($document->total ?? 0),
            'total_in_words' => $totalInWords,
            'payment_method' => $paymentMethod,
            'paymentMethod' => $paymentMethod,
            'payment_method_name' => $paymentMethod,
            'payment_condition' => $paymentCondition,
            'payments' => $payments,
            'cash' => $cash,
            'efectivo' => $cash,
            'change' => $change,
            'vuelto' => $change,
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
