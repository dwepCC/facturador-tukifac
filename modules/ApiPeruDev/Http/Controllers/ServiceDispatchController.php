<?php

namespace Modules\ApiPeruDev\Http\Controllers;

use App\CoreFacturalo\Helpers\Storage\StorageDocument;
use App\CoreFacturalo\Helpers\Xml\XmlFormat;
use App\CoreFacturalo\Template;
use App\CoreFacturalo\Facturalo;
use App\Models\Tenant\Company;
use App\Models\Tenant\Dispatch;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\ApiPeruDev\Helpers\CdrRead;
use Modules\ApiPeruDev\Helpers\ServiceDispatch;
use Modules\Store\Helpers\StorageHelper;
use Modules\PseService\Http\Gior\Service as GiorService;
use Modules\PseService\Http\Gior\ServiceSendFact as ServiceSendFact; 
use Exception;
use Modules\PseService\Http\Gior\ServiceOseSendFact;

class ServiceDispatchController extends Controller
{
    use StorageDocument;

    public function getServiceInitial()
    {
        $cp = Company::query()
            ->select('number', 'soap_type_id', 'soap_sunat_username', 'soap_sunat_password', 'api_sunat_id', 'api_sunat_secret')
            ->first();

        $serviceDispatch = new ServiceDispatch();
        $serviceDispatch->setCredentials(
            $cp->number,
            ($cp->soap_type_id === '01'),
            $cp->soap_sunat_username,
            $cp->soap_sunat_password,
            $cp->api_sunat_id,
            $cp->api_sunat_secret
        );

        return $serviceDispatch;
    }

    public function send($external_id)
    {
        DB::connection('tenant')->beginTransaction();
        try {

            $dispatch = Dispatch::query()
                ->select('id', 'document_type_id', 'series', 'number', 'filename', 'ticket')
                ->where('external_id', $external_id)->first();
            if ($dispatch) {
                $xml_signed = (new StorageHelper())->getXmlSigned($dispatch->filename);

                $facturalo = new Facturalo();
                $hasPseSend = $facturalo->hasPseSend();
                // dd($hasPseSend);
                $company = Company::first();
                if ($company->pse_provider_id == 4) {
                    $service = new ServiceSendFact();
                } else {
                    $service = new GiorService();
                }
                if($hasPseSend){
                
                    $response = $service->sendXmlSigned($dispatch->filename, $xml_signed, true);

                    if(!$response['success']) {
                        $res_errors = is_array($response['errors']) ? implode(" ",$response['errors']) : '';
                        throw new Exception("PSE. SEND - Code: {$response['code']}; Description: {$response['message']} ;detalle: {$res_errors}");
                    } else {
                        Dispatch::query()
                            ->where('id', $dispatch->id)
                            ->update([
                                // 'ticket' => $response['ticket'],
                                'state_type_id' => '03'
                            ]);
                        DB::connection('tenant')->commit();
                    }

                    return [
                        'success' => true,
                        'message' => 'PSE - Se obtuvo el nro. de ticket correctamente',
                    ];
                } else {

                    // Ose sendfact a través de api rest 
                    if ($company->soap_send_id === '04') {
                        $ose = new ServiceOseSendFact;
                        $response = $ose->sendXmlSigned($dispatch->filename, $xml_signed);
                        if(!$response['success']) {
                            $res_errors = is_array($response['errors']) ? implode(" ",$response['errors']) : '';
                            throw new Exception("PSE. SEND - Code: {$response['code']}; Description: {$response['message']} ;detalle: {$res_errors}");
                        } else {
                            Dispatch::query()
                                ->where('id', $dispatch->id)
                                ->update([
                                    // 'ticket' => $response['ticket'],
                                    'state_type_id' => '03'
                                ]);
                            DB::connection('tenant')->commit();
                        }

                            return [
                                'success' => true,
                                'message' => 'PSE - Se obtuvo el nro. de ticket correctamente',
                            ];


                    } else {
                        $res = $this->getServiceInitial()->send(
                            $dispatch->filename,
                            $xml_signed
                        );

                        if ($res['success']) {
                            $data = $res['data'];
                            Log::info("Dispatch { $dispatch->filename } send response: ", $data);
                            if (is_array($data) && array_key_exists('numTicket', $data)) {
                                $ticket = $data['numTicket'];
                                $reception_date = $data['fecRecepcion'];
                                $updated = Dispatch::where('id', $dispatch->id)
                                    ->update([
                                        'ticket' => $ticket,
                                        'reception_date' => $reception_date,
                                        'state_type_id' => '03'
                                    ]);
                                Log::info("Dispatch update result: " . $updated);
                                DB::connection('tenant')->commit();
                                return [
                                    'success' => true,
                                    'message' => "Se obtuvo el nro. de ticket correctamente. Ticket: {$ticket}, Fecha de recepción: {$reception_date}, ID guia: {$dispatch->id}",
                                ];
                            } else {
                                Log::error('No se obtuvo ticket', $res);
                                return [
                                    'success' => false,
                                    'message' => 'No se obtuvo el ticket correctamente',
                                ];
                            }
                        } else {
                            Log::error('No se obtuvo ticket', $res);
                            return $res;
                        }

                    }
                }
            }
            return [
                'success' => false,
                'message' => 'El external id es incorrecto'
            ];
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            DB::connection('tenant')->rollBack();
            return [
                'success' => false,
                'message' => 'No fue posible enviar a SUNAT'
            ];
        }
    }

public function statusTicket($external_id, $simple_result = false)
{
    $dispatch = Dispatch::query()
        ->select('id', 'series', 'number', 'state_type_id', 'ticket', 'filename', 'external_id')
        ->where('external_id', $external_id)->first();

    if ($dispatch) {
        $storage = new StorageHelper();
        $facturalo = new Facturalo();
        $hasPseSend = $facturalo->hasPseSend();
        $company = Company::first();
        
        Log::info("Consulta statusTicket iniciada para external_id: {$external_id}");
        Log::info("Dispatch encontrado: {$dispatch->series}-{$dispatch->number}");
        Log::info("hasPseSend: " . ($hasPseSend ? 'true' : 'false'));
        Log::info("company->pse_provider_id: " . ($company->pse_provider_id ?? 'no definido'));
        Log::info("company->soap_send_id: " . ($company->soap_send_id ?? 'no definido'));

        if ($company->pse_provider_id == 4) {
            $service = new ServiceSendFact();
        } else {
            $service = new GiorService();
        }

        if($hasPseSend){
            Log::info("Usando PSE Send. Consultando summary...");
            $response = $service->querySummary($dispatch->filename);
            Log::info("Respuesta de querySummary PSE:", $response);
            
            if ($company->pse_provider_id == 4) {
                Log::info("Procesando respuesta para PSE provider 4");
                if ($response['document_status'] == 4) {
                    Log::warning("Document Status 4 recibido");
                    return [
                        'success' => false,
                        'data' => [
                            'number' => $dispatch->number_full,
                            'filename' => $dispatch->filename,
                            'external_id' => $dispatch->external_id,
                            'state_type_id' => $dispatch->state_type_id,
                        ],
                        'message' => "PSE. TICKET - Document Status: {$response['document_status']}; Message: {$response['message']} ",
                    ];
                }  

                if(!$response['success']) {
                    Log::error("PSE error - Code: {$response['code']}; Message: {$response['message']}");
                    throw new Exception("PSE. TICKET - Code: {$response['code']}; Description: {$response['message']}");
                } else {
                    Log::info("Procesando respuesta exitosa PSE");
                    $message = $response['message'];
                    $state_type_id =  $service->validationCodeResponseIntegration($response['document_status'], $response['message']);
                    $has_cdr = false;
                    $qr_url = null;
                    $download_external_cdr = null;

                    if($response['cdr'] != null) {
                        Log::info("CDR recibido en respuesta PSE");
                        $has_cdr = true;
                        $download_external_cdr = $dispatch->download_external_cdr;
                        $this->uploadStorage($dispatch->filename, $response['cdr'], 'cdr_b64');
                        $file_content_cdr = base64_decode($response['cdr']);
                        
                        // Validar CDR antes de procesar
                        if(empty($file_content_cdr)) {
                            Log::error("CDR decodificado está vacío para: {$dispatch->filename}");
                        } else {
                            Log::info("CDR decodificado tamaño: " . strlen($file_content_cdr) . " bytes");
                        }
                        
                        $storage->uploadCdr($dispatch->filename, $file_content_cdr);
                        $cdr_content = $storage->getCdr($dispatch->filename);
                        
                        // LOG CRÍTICO: Verificar contenido del CDR antes de procesar
                        Log::info("Contenido CDR obtenido de storage (primeros 100 chars): " . substr($cdr_content ?? '', 0, 100));
                        Log::info("CDR contenido es nulo/vacío: " . (empty($cdr_content) ? 'true' : 'false'));
                        
                        if(empty($cdr_content)) {
                            Log::error("ERROR: CDR obtenido de storage está vacío para: {$dispatch->filename}");
                            return [
                                'success' => false,
                                'message' => 'CDR obtenido está vacío, no se puede procesar',
                                'data' => [
                                    'number' => $dispatch->number_full,
                                    'filename' => $dispatch->filename,
                                ]
                            ];
                        }
                        
                        $res['cdr_data'] = (new CdrRead())->getCdrData($cdr_content);
                        Log::info("Resultado getCdrData:", $res['cdr_data'] ?? ['error' => 'no data']);
                        $qr_url = $res['cdr_data']['qr_url'] ?? null;
                    }

                    $dispatch->has_cdr = $has_cdr;
                    $dispatch->state_type_id = $state_type_id;
                    $dispatch->qr_url = $qr_url;
                    $dispatch->save();

                    return [
                        'success' => true,
                        'data' => [
                            'number' => $dispatch->number_full,
                            'filename' => $dispatch->filename,
                            'external_id' => $dispatch->external_id,
                            'state_type_id' => $dispatch->state_type_id,
                        ],
                        'links' => [
                            'xml' => $dispatch->download_external_xml,
                            'pdf' => $dispatch->download_external_pdf,
                            'cdr' => $download_external_cdr,
                        ],
                        'message' => 'PSE. '.$message,
                    ];
                }
            } else {
                Log::info("Procesando respuesta para otro provider PSE");
                if ($response['code'] != 200) {
                    $message = array_key_exists('message', $response) ? $response['message'] : '';
                    $errors = array_key_exists('errores', $response) ? $response['errores'] : '';
                    Log::warning("Código respuesta no 200: {$response['code']}");
                    return [
                        'success' => false,
                        'data' => [
                            'number' => $dispatch->number_full,
                            'filename' => $dispatch->filename,
                            'external_id' => $dispatch->external_id,
                            'state_type_id' => $dispatch->state_type_id,
                        ],
                        'message' => "PSE. TICKET - Code: {$response['code']}; Errores: {$message} - {$errors}",
                    ];
                }

                if(!$response['success']) {
                    Log::error("PSE error general - Code: {$response['code']}; Message: {$response['message']}");
                    throw new Exception("PSE. TICKET - Code: {$response['code']}; Description: {$response['message']}");
                } else {
                    Log::info("Procesando respuesta exitosa otro provider");
                    $message = $response['message'];
                    $state_type_id = '05';
                    $has_cdr = false;
                    $qr_url = null;
                    $download_external_cdr = null;

                    if($response['rejected']) {
                        $state_type_id = '09';
                    }
                    
                    if($response['cdr'] != null) {
                        Log::info("CDR recibido en respuesta otro provider");
                        $has_cdr = true;
                        $download_external_cdr = $dispatch->download_external_cdr;
                        $this->uploadStorage($dispatch->filename, $response['cdr'], 'cdr_b64');
                        $file_content_cdr = (new CdrRead())->getCrdContent($response['cdr']);
                        
                        // Validar contenido
                        if(empty($file_content_cdr)) {
                            Log::error("getCrdContent retornó vacío para: {$dispatch->filename}");
                        }
                        
                        $storage->uploadCdr($dispatch->filename, $file_content_cdr);
                        $cdr_content = $storage->getCdr($dispatch->filename);
                        
                        // LOG CRÍTICO
                        Log::info("CDR obtenido de storage (primeros 100 chars): " . substr($cdr_content ?? '', 0, 100));
                        
                        if(empty($cdr_content)) {
                            Log::error("ERROR: CDR vacío al obtener de storage");
                            return [
                                'success' => false,
                                'message' => 'Error: CDR vacío al procesar',
                                'data' => ['filename' => $dispatch->filename]
                            ];
                        }
                        
                        $res['cdr_data'] = (new CdrRead())->getCdrData($cdr_content);
                        Log::info("Resultado getCdrData:", $res['cdr_data'] ?? ['error' => 'sin datos']);
                        $qr_url = $res['cdr_data']['qr_url'] ?? null;
                    }

                    $dispatch->has_cdr = $has_cdr;
                    $dispatch->state_type_id = $state_type_id;
                    $dispatch->qr_url = $qr_url;
                    $dispatch->save();

                    DB::connection('tenant')->commit();

                    return [
                        'success' => true,
                        'data' => [
                            'number' => $dispatch->number_full,
                            'filename' => $dispatch->filename,
                            'external_id' => $dispatch->external_id,
                            'state_type_id' => $dispatch->state_type_id,
                        ],
                        'links' => [
                            'xml' => $dispatch->download_external_xml,
                            'pdf' => $dispatch->download_external_pdf,
                            'cdr' => $download_external_cdr,
                        ],
                        'message' => 'PSE. '.$message,
                    ];
                }
            }
        } else {
            Log::info("Usando método tradicional (no PSE)");
            if ($company->soap_send_id === '04') {
                Log::info("Usando ServiceOseSendFact");
                $ose = new ServiceOseSendFact;
                $response = $ose->querySummary($dispatch->filename);
                Log::info("Respuesta OSE:", $response);
                
                if ($response['document_status'] == 4) {
                    Log::warning("Document Status 4 en OSE");
                    return [
                        'success' => false,
                        'data' => [
                            'number' => $dispatch->number_full,
                            'filename' => $dispatch->filename,
                            'external_id' => $dispatch->external_id,
                            'state_type_id' => $dispatch->state_type_id,
                        ],
                        'message' => "PSE. TICKET - Document Status: {$response['document_status']}; Message: {$response['message']} ",
                    ];
                }  

                if(!$response['success']) {
                    Log::error("OSE error - Code: {$response['code']}; Message: {$response['message']}");
                    throw new Exception("PSE. TICKET - Code: {$response['code']}; Description: {$response['message']}");
                } else {
                    Log::info("Procesando respuesta exitosa OSE");
                    $message = $response['message'];
                    $state_type_id =  $ose->validationCodeResponseIntegration($response['document_status'], $response['message']);
                    $has_cdr = false;
                    $qr_url = null;
                    $download_external_cdr = null;

                    if($response['cdr'] != null) {
                        Log::info("CDR recibido en OSE");
                        $has_cdr = true;
                        $download_external_cdr = $dispatch->download_external_cdr;
                        $this->uploadStorage($dispatch->filename, $response['cdr'], 'cdr_b64');
                        $file_content_cdr = (new CdrRead())->getCrdContent($response['cdr']);
                        
                        // Validar
                        if(empty($file_content_cdr)) {
                            Log::error("getCrdContent retornó vacío para OSE: {$dispatch->filename}");
                        }
                        
                        $storage->uploadCdr($dispatch->filename, $file_content_cdr);
                        $cdr_content = $storage->getCdr($dispatch->filename);
                        
                        // LOG CRÍTICO
                        Log::info("CDR OSE obtenido (primeros 100 chars): " . substr($cdr_content ?? '', 0, 100));
                        
                        if(empty($cdr_content)) {
                            Log::error("ERROR: CDR OSE vacío");
                            return [
                                'success' => false,
                                'message' => 'Error: CDR vacío en OSE',
                                'data' => ['filename' => $dispatch->filename]
                            ];
                        }
                        
                        $res['cdr_data'] = (new CdrRead())->getCdrData($cdr_content);
                        Log::info("Resultado getCdrData OSE:", $res['cdr_data'] ?? ['error' => 'sin datos']);
                        $qr_url = $res['cdr_data']['qr_url'] ?? null;
                    }

                    $dispatch->has_cdr = $has_cdr;
                    $dispatch->state_type_id = $state_type_id;
                    $dispatch->qr_url = $qr_url;
                    $dispatch->save();

                    return [
                        'success' => true,
                        'data' => [
                            'number' => $dispatch->number_full,
                            'filename' => $dispatch->filename,
                            'external_id' => $dispatch->external_id,
                            'state_type_id' => $dispatch->state_type_id,
                        ],
                        'links' => [
                            'xml' => $dispatch->download_external_xml,
                            'pdf' => $dispatch->download_external_pdf,
                            'cdr' => $download_external_cdr,
                        ],
                        'message' => 'PSE. '.$message,
                    ];
                }
            } else {
                Log::info("Usando servicio tradicional SUNAT");
                Log::info("Consultando ticket: {$dispatch->ticket}");
                $res = $this->getServiceInitial()->ticket($dispatch->ticket);
                Log::info("Respuesta ticket SUNAT:", $res);

                if (key_exists('codRespuesta', $res)) {
                    Log::info("Procesando respuesta SUNAT con codRespuesta: {$res['codRespuesta']}");
                    $has_cdr = false;
                    $qr_url = null;
                    $state_type_id = '01';
                    $message = '';
                    $success = true;
                    
                    switch ($res['codRespuesta']) {
                        case '98':
                            $state_type_id = '03';
                            $message = 'La guía aún está en proceso, vuelva a consultar.';
                            Log::info("Ticket en proceso (98)");
                            break;
                        case '0':
                            $state_type_id = '05';
                            $has_cdr = true;
                            Log::info("Ticket aceptado (0) - tiene CDR");
                            break;
                        case '99':
                            $state_type_id = '09';
                            if ($res['indCdrGenerado'] === '1') {
                                $has_cdr = true;
                                Log::info("Ticket rechazado (99) pero con CDR generado");
                            } else {
                                $message = $res['error']['desError'] ?? 'Error desconocido';
                                Log::warning("Ticket rechazado sin CDR: {$message}");
                            }
                            break;
                    }

                    if ($has_cdr) {
                        Log::info("Procesando CDR SUNAT");
                        // Validar si existe arcCdr
                        if(!isset($res['arcCdr']) || empty($res['arcCdr'])) {
                            Log::error("ERROR: arcCdr no existe o está vacío en respuesta SUNAT");
                            return [
                                'success' => false,
                                'message' => 'SUNAT no retornó CDR en la respuesta',
                                'data' => [
                                    'filename' => $dispatch->filename,
                                    'codRespuesta' => $res['codRespuesta']
                                ]
                            ];
                        }
                        
                        Log::info("arcCdr recibido, tamaño: " . strlen($res['arcCdr']) . " caracteres");
                        $file_content_cdr = (new CdrRead())->getCrdContent($res['arcCdr']);
                        
                        // Validar contenido CDR
                        if(empty($file_content_cdr)) {
                            Log::error("ERROR: getCrdContent retornó contenido vacío");
                            Log::info("arcCdr primeros 100 chars: " . substr($res['arcCdr'] ?? '', 0, 100));
                        } else {
                            Log::info("CDR decodificado tamaño: " . strlen($file_content_cdr) . " bytes");
                        }
                        
                        $storage->uploadCdr($dispatch->filename, $file_content_cdr);
                        $cdr_content = (new StorageHelper())->getCdr($dispatch->filename);
                        
                        // LOG CRÍTICO PARA DIAGNÓSTICO
                        Log::info("CDR obtenido de storage (primeros 200 chars): " . substr($cdr_content ?? '', 0, 200));
                        Log::info("CDR es null/empty: " . (empty($cdr_content) ? 'true' : 'false'));
                        
                        if(empty($cdr_content)) {
                            Log::error("ERROR FATAL: CDR obtenido de storage está vacío. Esto causa el error DOMDocument::loadXML()");
                            Log::info("Archivo CDR en storage verificado: {$dispatch->filename}");
                            
                            return [
                                'success' => false,
                                'message' => 'Error: No se pudo recuperar el CDR del storage',
                                'data' => [
                                    'filename' => $dispatch->filename,
                                    'ticket' => $dispatch->ticket,
                                    'codRespuesta' => $res['codRespuesta']
                                ]
                            ];
                        }
                        
                        $res['cdr_data'] = (new CdrRead())->getCdrData($cdr_content);
                        Log::info("Resultado getCdrData:", $res['cdr_data'] ?? ['error' => 'sin datos']);
                        $qr_url = $res['cdr_data']['qr_url'] ?? null;
                    }

                    Dispatch::query()
                        ->where('id', $dispatch->id)
                        ->update([
                            'state_type_id' => $state_type_id,
                            'qr_url' => $qr_url
                        ]);

                    $record = Dispatch::query()
                        ->select('id', 'series', 'number', 'state_type_id', 'filename', 'external_id')
                        ->where('external_id', $external_id)->first();

                    $download_external_cdr = null;
                    if ($has_cdr) {
                        $download_external_cdr = $record->download_external_cdr;
                        $message = $res['cdr_data']['message'] ?? $message;
                    }

                    if($simple_result) {
                        return [
                            'success' => $success,
                            'data' => [
                                'number' => $record->number_full,
                                'filename' => $record->filename,
                                'external_id' => $record->external_id,
                                'state_type_id' => $record->state_type_id,
                            ],
                            'message' => $message,
                        ];
                    }

                    return [
                        'success' => $success,
                        'data' => [
                            'number' => $record->number_full,
                            'filename' => $record->filename,
                            'external_id' => $record->external_id,
                            'state_type_id' => $record->state_type_id,
                        ],
                        'links' => [
                            'xml' => $record->download_external_xml,
                            'pdf' => $record->download_external_pdf,
                            'cdr' => $download_external_cdr,
                        ],
                        'message' => $message,
                    ];
                }

                Log::warning("Respuesta SUNAT no contiene codRespuesta:", $res);
            }
        }

        Log::warning("Retornando respuesta sin procesar:", $res ?? ['no_data' => true]);
        return $res ?? [
            'success' => false,
            'message' => 'Respuesta inesperada del servicio'
        ];
    }

    Log::warning("External ID no encontrado: {$external_id}");
    return [
        'success' => false,
        'message' => 'El external id es incorrecto'
    ];
}


    public function createXmlUnsigned($document)
    {
        $template = new Template();
        $template_name = ($document['document_type_id'] === '31')?'dispatch_carrier':'dispatch';
        Log::info($template_name);
        $xmlUnsigned = XmlFormat::format($template->xml($template_name, null, $document));
        $this->uploadStorage($document['filename'], $xmlUnsigned, 'unsigned');

        return $xmlUnsigned;
    }

    public function getData($id)
    {
        $company = Company::query()
            ->first();

        $record = Dispatch::query()
            ->find($id);

        $items = [];
        foreach ($record->items as $it) {
            $items[] = [
                'internal_id' => $it->item->internal_id,
                'name' => $it->item->description,
                'unit_type_id' => $it->item->unit_type_id,
                'quantity' => $it->quantity,
            ];
        }
        return [
            'company_name' => $company->name,
            'company_number' => $company->number,
            'company_trade_name' => $company->trade_name,
            'customer_identity_document_type_id' => optional($record->customer)->identity_document_type_id,
            'customer_number' => optional($record->customer)->number,
            'customer_name' => optional($record->customer)->name,
            'document_type_id' => $record->document_type_id,
            'series' => $record->series,
            'number' => $record->number,
            'date_of_issue' => $record->date_of_issue->format('Y-m-d'),
            'time_of_issue' => $record->time_of_issue,
            'transfer_reason_type_id' => $record->transfer_reason_type_id,
            'transfer_reason_type_name' => optional($record->transfer_reason_type)->description,
            'unit_type_id' => $record->unit_type_id,
            'total_weight' => $record->total_weight,
            'packages_number' => $record->packages_number,
            'transport_mode_type_id' => $record->transport_mode_type_id,
            'date_of_shipping' => $record->date_of_shipping->format('Y-m-d'),
            'observations' => $record->observations,
            'filename' => $record->filename,
            'origin_location_id' => optional($record->origin)->location_id,
            'origin_address' => optional($record->origin)->address,
            'origin_code' => optional($record->origin)->code,
            'delivery_location_id' => optional($record->delivery)->location_id,
            'delivery_address' => optional($record->delivery)->address,
            'delivery_code' => optional($record->delivery)->code,
            'driver_identity_document_type_id' => optional($record->driver)->identity_document_type_id,
            'driver_number' => optional($record->driver)->number,
            'driver_names' => optional($record->driver)->name,
            'driver_lastnames' => optional($record->driver)->name,
            'driver_license' => optional($record->driver)->license,
            'transport_plate_number' => $record->transport_data ? $record->transport_data['plate_number'] : null,
            'transport_tuc' => $record->transport_data ? $record->transport_data['tuc'] : null,
            'dispatcher_identity_document_type_id' => optional($record->dispatcher)->identity_document_type_id,
            'dispatcher_number' => optional($record->dispatcher)->number,
            'dispatcher_name' => optional($record->dispatcher)->name,
            'dispatcher_number_mtc' => optional($record->dispatcher)->number_mtc,

            'sender_identity_document_type_id' => $record->sender_data ? $record->sender_data['identity_document_type_id'] : null,
            'sender_number' => $record->sender_data ? $record->sender_data['number'] : null,
            'sender_name' => $record->sender_data ? $record->sender_data['name'] : null,

            'receiver_identity_document_type_id' => $record->receiver_data ? $record->receiver_data['identity_document_type_id'] : null,
            'receiver_number' => $record->receiver_data ? $record->receiver_data['number'] : null,
            'receiver_name' => $record->receiver_data ? $record->receiver_data['name'] : null,

            'sender_address_location_id' => $record->sender_address_data ? $record->sender_address_data['location_id'] : null,
            'sender_address_address' => $record->sender_address_data ? $record->sender_address_data['address'] : null,

            'receiver_address_location_id' => $record->receiver_address_data ? $record->receiver_address_data['location_id'] : null,
            'receiver_address_address' => $record->receiver_address_data ? $record->receiver_address_data['address'] : null,
            'payer_identity_document_type_id' => $record->payer ? $record->payer['identity_document_type_id'] : null,
            'payer_number' => $record->payer ? $record->payer['number'] : null,
            'payer_name' => $record->payer ? $record->payer['name'] : null,
            'payer_description' => $record->payer ? $record->payer['description'] : null,
            'items' => $items,
            'secondary_transports' => $record->secondary_transports,
            'secondary_drivers' => $record->secondary_drivers,
            'related_number' => optional($record->related)->number,
            'related_document_type_id' => optional($record->related)->document_type_id,
            'has_transport_driver_01' => $record->has_transport_driver_01,
            'is_transport_m1l' => $record->is_transport_m1l ? $record->is_transport_m1l : false,
            'license_plate_m1l' => $record->license_plate_m1l ? $record->license_plate_m1l : null,
            'reference_documents' => $record->reference_documents,
            'buyer_id' => $record->buyer_id,
            'buyer' => (array)$record->buyer,
        ];
    }

    public function getDataCarrier($id)
    {
        $company = Company::query()
            ->first();

        $record = Dispatch::query()
            ->find($id);

        $items = [];
        foreach ($record->items as $it) {
            $items[] = [
                'internal_id' => $it->item->internal_id,
                'name' => $it->item->description,
                'unit_type_id' => $it->item->unit_type_id,
                'quantity' => $it->quantity,
            ];
        }
        return [
            'company_name' => $company->name,
            'company_number' => $company->number,
            'company_trade_name' => $company->trade_name,
//            'customer_identity_document_type_id' => $record->customer->identity_document_type_id,
//            'customer_number' => $record->customer->number,
//            'customer_name' => $record->customer->name,
            'document_type_id' => $record->document_type_id,
            'series' => $record->series,
            'number' => $record->number,
            'date_of_issue' => $record->date_of_issue->format('Y-m-d'),
            'time_of_issue' => $record->time_of_issue,
//            'transfer_reason_type_id' => $record->transfer_reason_type_id,
//            'transfer_reason_type_name' => $record->transfer_reason_type->description,
            'unit_type_id' => $record->unit_type_id,
            'total_weight' => $record->total_weight,
//            'transport_mode_type_id' => $record->transport_mode_type_id,
            'date_of_shipping' => $record->date_of_shipping->format('Y-m-d'),
            'observations' => $record->observations,
            'filename' => $record->filename,
//            'origin_location_id' => $record->origin->location_id,
//            'origin_address' => $record->origin->address,
//            'origin_code' => $record->origin->code,
//            'delivery_location_id' => $record->delivery->location_id,
//            'delivery_address' => $record->delivery->address,
//            'delivery_code' => $record->delivery->code,
            'driver_identity_document_type_id' => optional($record->driver)->identity_document_type_id,
            'driver_number' => optional($record->driver)->number,
            'driver_names' => optional($record->driver)->name,
            'driver_lastnames' => optional($record->driver)->name,
            'driver_license' => optional($record->driver)->license,
            'transport_plate_number' => $record->transport_data ? $record->transport_data['plate_number'] : null,
            'transport_tuc' => $record->transport_data ? $record->transport_data['tuc'] : null,
//            'dispatcher_identity_document_type_id' => optional($record->dispatcher)->identity_document_type_id,
//            'dispatcher_number' => optional($record->dispatcher)->number,
//            'dispatcher_name' => optional($record->dispatcher)->name,
//            'dispatcher_number_mtc' => optional($record->dispatcher)->number_mtc,
            'items' => $items,
            'secondary_transports' => $record->secondary_transports,
            'secondary_drivers' => $record->secondary_drivers,
            'reference_documents' => $record->reference_documents,
        ];
    }
}
