<?php

namespace Modules\MobileApp\Http\Controllers\Api;

use App\CoreFacturalo\Helpers\Storage\StorageDocument;
use App\Http\Controllers\Controller;
use App\CoreFacturalo\Facturalo;
use App\Http\Controllers\Tenant\QuotationController;
use App\CoreFacturalo\Template;
use App\Models\Tenant\Company;
use App\Models\Tenant\Document;
use App\Models\Tenant\SaleNote;
use App\Models\Tenant\BankAccount;
use App\CoreFacturalo\Helpers\Number\NumberLetter;
use Mpdf\Mpdf;
use Exception;
use Html2Text\Html2Text;
use Illuminate\Http\Request;
use App\Http\Controllers\Tenant\Api\SaleNoteController;
use App\Http\Controllers\Tenant\DownloadController as HttpDownloadController;

class DownloadController extends Controller
{
    
    /**
     * 
     * Retornar pdf en html
     *
     * @param  string $model
     * @param  string $external_id
     * @param  string $format
     * @return string
     */
    public function documentPrintPdf($model, $external_id, $format, $extend_pdf_height = 0) 
    {
        $path_model = "App\\Models\\Tenant\\".ucfirst($model);
        $document = $path_model::where('external_id', $external_id)->first();

        if (!$document) throw new Exception("El código {$external_id} es inválido, no se encontro documento relacionado");

        $html = $this->getHtmlPdf($model, $document, $format);

        $this->replaceElementsInHtml($html, $format, $extend_pdf_height);

        return $html;
    }

    
    /**
     * 
     * Reemplazar ancho en formato pdf - altura adicional para ticket (impresion directa app)
     *
     * @param  string $html
     * @param  string $format
     * @param  float $extend_pdf_height
     * @return void
     */
    private function replaceElementsInHtml(&$html, $format, $extend_pdf_height)
    {
        // se reemplaza ancho para impresion desde app para tickets
        $size_width = $this->getSizeWidth($format);

        if($size_width)
        {
            $search_key = '<style>';
            $replace_size = "{$search_key} @media print { .page, .page-content, html, body, .framework7-root, .views, .view { height: auto !important; width: {$size_width}mm !important;}}";
    
            $html = str_replace($search_key, $replace_size, $html);
        }

        // se agrega un div para aumentar la altura del pdf, se utiliza para impresion directa desde app
        if($extend_pdf_height > 0)
        {
            $search_key_extend = '</body>';
            $replace_size_extend = "<div style='height:".$extend_pdf_height."px'></div>{$search_key_extend}";
    
            $html = str_replace($search_key_extend, $replace_size_extend, $html);
        }
    }


    /**
     * 
     * Obtener medida del formato ticket para asignar el valor a la impresión
     *
     * @param  string $format
     * @return float
     */
    public function getSizeWidth($format)
    {
        $size_width = null;

        switch ($format) 
        {
            case 'ticket_50':
                $size_width = 45;
                break;
            
            case 'ticket_58':
                $size_width = 56;
                break;

            case 'ticket':
                $size_width = 78;
                break;
        }

        return $size_width;
    }


    /**
     * 
     * Reload Ticket
     * 
     * @param  string $document
     * @param  string $format
     * @return string
     */
    private function getHtmlPdf($model, $document, $format) 
    {
        $html = null;

        if($model === 'document')
        {
            $html = (new Facturalo)->createPdf($document, 'invoice', $format, 'html');
        }
        else
        {
            $html = app(SaleNoteController::class)->createPdf($document, $format, null, 'html');
        }

        return $html;
    }

    
    /**
     * 
     * Retornar data para impresión (reemplaza texto plano)
     *
     * @param  string $model
     * @param  string $external_id
     * @param  string $format
     * @return array
     */
    public function documentPrintText($model, $external_id, $format) 
    {
        $modelClass = $this->getModelClass($model);
        if (!$modelClass) {
            throw new Exception("Modelo no reconocido: {$model}");
        }

        $document = $modelClass::where('external_id', $external_id)->first();
        if (!$document) {
            throw new Exception("El código {$external_id} es inválido, no se encontró documento relacionado");
        }

        if ($modelClass === Document::class) {
            return $this->buildDocumentPrintData($document);
        } elseif ($modelClass === SaleNote::class) {
            return $this->buildSaleNotePrintData($document);
        }

        throw new Exception("Modelo no soportado para impresión de datos");
    }

    private function getModelClass($model) {
        if ($model === 'document') return Document::class;
        if ($model === 'sale_note' || $model === 'salenote') return SaleNote::class;
        return null;
    }

    private function buildDocumentPrintData($document)
    {
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

        $company = Company::active();
        $companyData = null;
        if ($company) {
            $establishment = $document->establishment;
            $establishmentModel = $document->relation_establishment;
            
            $logoBase64 = null;
            $logo = null;
            if ($company->logo) {
                $logo = "storage/uploads/logos/{$company->logo}";
            }
            if ($establishment && isset($establishment->logo) && $establishment->logo) {
                $logo = $establishment->logo;
            }
            
            if ($logo && file_exists(public_path($logo))) {
                try {
                    $logoBase64 = base64_encode(file_get_contents(public_path($logo)));
                } catch (\Exception $e) {
                    $logoBase64 = null;
                }
            }
            
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

        $documentType = $document->document_type ? $document->document_type->description : '';

        $items = $document->items->map(function ($item) {
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

        $firstPayment = $document->payments->first();
        $paymentMethod = null;
        
        if ($firstPayment && $firstPayment->payment_method_type) {
            $paymentMethod = $firstPayment->payment_method_type->description;
        }
        
        $cash = (float) $document->payments->sum('payment');
        
        $change = 0;
        if ($firstPayment) {
            $change = (float) ($firstPayment->change ?? 0);
        }

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

        $dueDate = null;
        if ($document->invoice && isset($document->invoice->date_of_due)) {
            $dueDate = is_string($document->invoice->date_of_due) 
                ? $document->invoice->date_of_due 
                : $document->invoice->date_of_due->format('Y-m-d');
        }

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

        $totalInWords = '';
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
        
        if (empty($totalInWords) && $document->total) {
            $converted = NumberLetter::convertToLetter($document->total, 'Soles');
            if ($converted && $converted !== 'No es posible convertir el numero en letras') {
                $totalInWords = 'Son: ' . ucfirst(trim($converted));
            }
        }

        $paymentCondition = 'Contado';
        if ($document->payment_condition) {
            $paymentCondition = $document->payment_condition->name;
        }

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

        $qrData = null;
        if (in_array($document->document_type_id, ['01', '03']) && $document->qr) {
            $qrData = $document->qr;
        }

        $hashCode = $document->hash ?? null;

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

    private function buildSaleNotePrintData($saleNote)
    {
        $saleNote->load([
            'items', 
            'payments.payment_method_type', 
            'person',
            'establishment.district',
            'establishment.province',
            'establishment.department',
            'seller',
            'payment_condition'
        ]);

        $company = Company::active();
        $companyData = null;
        if ($company) {
            $establishment = $saleNote->establishment;
            
            $logoBase64 = null;
            $logo = null;
            if ($company->logo) {
                $logo = "storage/uploads/logos/{$company->logo}";
            }
            if ($establishment && $establishment->logo) {
                $logo = $establishment->logo;
            }
            
            if ($logo && file_exists(public_path($logo))) {
                try {
                    $logoBase64 = base64_encode(file_get_contents(public_path($logo)));
                } catch (\Exception $e) {
                    $logoBase64 = null;
                }
            }
            
            $address = '';
            if ($establishment && $establishment->address && $establishment->address !== '-') {
                $address = $establishment->address;
                if ($establishment->district) {
                    $address .= ', ' . $establishment->district->description;
                }
                if ($establishment->province) {
                    $address .= ', ' . $establishment->province->description;
                }
                if ($establishment->department) {
                    $address .= ' - ' . $establishment->department->description;
                }
            }
            
            $companyData = [
                'name' => $company->name ?? '',
                'ruc' => $company->number ?? '',
                'address' => $address,
                'commercial_address' => ($establishment && $establishment->trade_address && $establishment->trade_address !== '-') ? $establishment->trade_address : '',
                'phone' => ($establishment && $establishment->telephone && $establishment->telephone !== '-') ? $establishment->telephone : '',
                'email' => ($establishment && $establishment->email && $establishment->email !== '-') ? $establishment->email : '',
                'web' => ($establishment && $establishment->web_address && $establishment->web_address !== '-') ? $establishment->web_address : '',
                'slogan' => ($establishment && $establishment->aditional_information && $establishment->aditional_information !== '-') ? $establishment->aditional_information : '',
                'logo' => $logoBase64,
            ];
        }

        $documentType = 'NOTA DE VENTA';

        $items = $saleNote->items->map(function ($item) {
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
        $payments = $saleNote->payments->map(function($payment) {
            return [
                'name' => $payment->payment_method_type->description ?? '',
                'method' => $payment->payment_method_type->description ?? '',
                'amount' => (float) $payment->payment,
                'reference' => $payment->reference ?? '',
            ];
        })->toArray();

        $firstPayment = $saleNote->payments->first();
        $paymentMethod = null;
        
        if ($firstPayment && $firstPayment->payment_method_type) {
            $paymentMethod = $firstPayment->payment_method_type->description;
        } elseif ($saleNote->payment_method_type) {
            $paymentMethod = $saleNote->payment_method_type->description;
        }
        
        $cash = (float) $saleNote->payments->sum('payment');
        
        $change = 0;
        if ($firstPayment) {
            $change = (float) ($firstPayment->change ?? 0);
        }

        $date = '';
        $issueTime = '';
        if ($saleNote->date_of_issue) {
            if (is_string($saleNote->date_of_issue)) {
                $date = $saleNote->date_of_issue;
            } else {
                $date = $saleNote->date_of_issue->format('Y-m-d');
            }
            
            if ($saleNote->time_of_issue) {
                $issueTime = $saleNote->time_of_issue;
                $date .= ' ' . $issueTime;
            }
        }

        $dueDate = null;
        if ($saleNote->due_date) {
            $dueDate = is_string($saleNote->due_date) 
                ? $saleNote->due_date 
                : $saleNote->due_date->format('Y-m-d');
        }

        $customer = null;
        if ($saleNote->person) {
            $customer = [
                'name' => $saleNote->person->name ?? '',
                'number' => $saleNote->person->number ?? '',
                'address' => $saleNote->person->address ?? '',
                'email' => $saleNote->person->email ?? '',
                'telephone' => $saleNote->person->telephone ?? '',
                'doc_trib_no_dom_sin_ruc' => $saleNote->person->number ?? '',
            ];
        }

        $totalInWords = '';
        $legendsRaw = $saleNote->attributes['legends'] ?? null;
        
        if ($legendsRaw !== null) {
            $legendsArray = json_decode($legendsRaw, true);
            if (is_array($legendsArray)) {
                $legend = collect($legendsArray)->where('code', '1000')->first();
                if ($legend && isset($legend['value'])) {
                    $totalInWords = $legend['value'];
                }
            }
        }
        
        if (empty($totalInWords) && $saleNote->total) {
            $converted = NumberLetter::convertToLetter($saleNote->total, 'Soles');
            if ($converted && $converted !== 'No es posible convertir el numero en letras') {
                $totalInWords = 'Son: ' . ucfirst(trim($converted));
            }
        }

        $paymentCondition = 'Contado';
        if ($saleNote->payment_condition) {
            $paymentCondition = $saleNote->payment_condition->name;
        } elseif ($paymentMethod) {
            $paymentCondition = $paymentMethod;
        }

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

        $qrData = null;
        $hashCode = null;

        $seller = null;
        if ($saleNote->seller) {
            $seller = $saleNote->seller->name ?? '';
        }

        return [
            'company' => $companyData,
            'document_type' => $documentType,
            'number' => $saleNote->series."-".str_pad($saleNote->number, 8, '0', STR_PAD_LEFT),
            'date' => $date,
            'date_of_issue' => $date,
            'issue_time' => $issueTime,
            'due_date' => $dueDate,
            'customer' => $customer,
            'items' => $items,
            'subtotal' => (float) ($saleNote->total_value ?? 0),
            'total_value' => (float) ($saleNote->total_value ?? 0),
            'taxable_operations' => (float) ($saleNote->total_taxed ?? 0),
            'tax' => (float) ($saleNote->total_igv ?? 0),
            'total_igv' => (float) ($saleNote->total_igv ?? 0),
            'total_taxes' => (float) ($saleNote->total_igv ?? 0),
            'total' => (float) ($saleNote->total ?? 0),
            'total_venta' => (float) ($saleNote->total ?? 0),
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

    public function returnFileData($model, $external_id, $format = 'a4' )
    {
        return app(HttpDownloadController::class)->toPrint($model, $external_id,$format);
    }
        
    /**
     * Usado para pruebas
     *
     * @param  Request $request
     * @return void
     */
    // public function documentPrintPdfUpload(Request $request) 
    // {

    //     $data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $request->image));

    //     file_put_contents(public_path('logo'.DIRECTORY_SEPARATOR.$request->external_id.".png"), $data);

    //     return [
    //         'successs' => true
    //     ];
        
    // }


}
