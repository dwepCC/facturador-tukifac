<?php

namespace Modules\ApiPeruDev\Helpers;

use Illuminate\Support\Facades\Log;

class CdrRead
{
    /*public function getCrdContent($arcCdr)
    {
        $content = base64_decode($arcCdr);
        $filter = function ($filename) {
            return 'xml' === strtolower($this->getFileExtension($filename));
        };
        $files = (new Zip())->decompress($content, $filter);

        return 0 === count($files) ? '' : $files[0]['content'];
    }*/
        public function getCrdContent($arcCdr)
{
    Log::info("=== INICIO getCrdContent ===");
    
    // Verificar si la entrada está vacía
    if (empty($arcCdr) || trim($arcCdr) === '') {
        Log::warning("arcCdr está vacío o nulo");
        return '';
    }
    
    Log::info("Longitud entrada: " . strlen($arcCdr));
    Log::debug("Primeros 50 chars de entrada: " . substr($arcCdr, 0, 50));
    
    // PASO 1: Decodificar base64
    $decoded_content = base64_decode($arcCdr, true);
    $is_base64 = ($decoded_content !== false);
    
    if (!$is_base64) {
        Log::info("Contenido no es base64 válido");
        
        // Verificar si ya es XML directo (sin codificar)
        if (strpos($arcCdr, '<?xml') === 0 || strpos($arcCdr, '<ar:ApplicationResponse') === 0) {
            Log::info("✅ Contenido es XML directo (sin base64)");
            return $arcCdr;
        }
        
        Log::error("❌ Contenido no es base64 ni XML válido");
        return '';
    }
    
    Log::info("✅ Base64 decodificado exitosamente");
    Log::info("Tamaño decodificado: " . strlen($decoded_content) . " bytes");
    Log::debug("Primeros 100 chars decodificados: " . substr($decoded_content, 0, 100));
    
    // PASO 2: Verificar si es un archivo ZIP (formato tradicional SUNAT)
    // Los archivos ZIP comienzan con la firma PK\x03\x04
    $is_zip = (strlen($decoded_content) >= 4 && substr($decoded_content, 0, 4) === "PK\x03\x04");
    
    if ($is_zip) {
        Log::info("📦 Contenido parece ser un archivo ZIP (firma PK\x03\x04 encontrada)");
        
        try {
            $filter = function ($filename) {
                $extension = $this->getFileExtension($filename);
                Log::debug("Verificando archivo en ZIP: {$filename}, extensión: {$extension}");
                return 'xml' === strtolower($extension);
            };
            
            $files = (new Zip())->decompress($decoded_content, $filter);
            
            if (count($files) === 0) {
                Log::warning("ZIP descomprimido pero no contiene archivos XML");
                return '';
            }
            
            Log::info("✅ ZIP descomprimido exitosamente, archivos encontrados: " . count($files));
            
            // Buscar el archivo XML (generalmente el primero)
            foreach ($files as $index => $file) {
                Log::info("Archivo {$index}: {$file['filename']}, tamaño: " . strlen($file['content']) . " bytes");
                if (strtolower($this->getFileExtension($file['filename'])) === 'xml') {
                    Log::info("✅ Archivo XML encontrado: {$file['filename']}");
                    return $file['content'];
                }
            }
            
            Log::warning("No se encontró archivo XML con extensión .xml en el ZIP");
            return $files[0]['content']; // Retornar el primero aunque no tenga extensión .xml
            
        } catch (\Exception $e) {
            Log::error("❌ Error descomprimiendo ZIP: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());
            return '';
        }
    }
    
    // PASO 3: Verificar si es XML directo después de decodificar (formato PSE)
    $is_xml = (strpos($decoded_content, '<?xml') === 0 || 
               strpos($decoded_content, '<ar:ApplicationResponse') === 0 ||
               strpos($decoded_content, '<ApplicationResponse') === 0);
    
    if ($is_xml) {
        Log::info("✅ Contenido decodificado es XML directo (formato PSE)");
        return $decoded_content;
    }
    
    // PASO 4: Verificar si es base64 doblemente codificado (caso raro)
    Log::info("⚠️ Contenido no es ZIP ni XML, verificando doble base64...");
    $double_decoded = base64_decode($decoded_content, true);
    
    if ($double_decoded !== false) {
        Log::info("Doble base64 decodificado exitosamente");
        
        if (strpos($double_decoded, '<?xml') === 0 || 
            strpos($double_decoded, '<ar:ApplicationResponse') === 0 ||
            strpos($double_decoded, '<ApplicationResponse') === 0) {
            Log::info("✅ Doble decodificación resultó en XML");
            return $double_decoded;
        }
        
        // Verificar si el doble decodificado es ZIP
        if (strlen($double_decoded) >= 4 && substr($double_decoded, 0, 4) === "PK\x03\x04") {
            Log::info("Doble decodificación resultó en ZIP, procesando...");
            try {
                $filter = function ($filename) {
                    return 'xml' === strtolower($this->getFileExtension($filename));
                };
                
                $files = (new Zip())->decompress($double_decoded, $filter);
                
                if (count($files) > 0) {
                    Log::info("✅ ZIP descomprimido desde doble base64");
                    return $files[0]['content'];
                }
            } catch (\Exception $e) {
                Log::error("Error descomprimiendo ZIP doble: " . $e->getMessage());
            }
        }
    }
    
    // PASO 5: Último intento - podría ser XML con encoding diferente
    Log::info("Realizando último intento de detección...");
    
    // Buscar cualquier indicio de XML
    if (strpos($decoded_content, '<') !== false && strpos($decoded_content, '>') !== false) {
        Log::info("Contenido tiene etiquetas XML, asumiendo que es XML");
        return $decoded_content;
    }
    
    // Log detallado del contenido para diagnóstico
    Log::error("❌ No se pudo determinar el formato del contenido CDR");
    Log::info("=== RESUMEN DIAGNÓSTICO ===");
    Log::info("Tipo entrada: " . gettype($arcCdr));
    Log::info("Es base64: " . ($is_base64 ? 'Sí' : 'No'));
    Log::info("Es ZIP: " . ($is_zip ? 'Sí' : 'No'));
    Log::info("Es XML: " . ($is_xml ? 'Sí' : 'No'));
    Log::info("Primeros 10 bytes (hex): " . bin2hex(substr($decoded_content, 0, 10)));
    Log::info("Primeros 200 chars decodificados: " . substr($decoded_content, 0, 200));
    Log::info("=== FIN DIAGNÓSTICO ===");
    
    return '';
}

    private function getFileExtension($filename)
    {
        $lastDotPos = strrpos($filename, '.');
        if (!$lastDotPos) {
            return '';
        }

        return substr($filename, $lastDotPos + 1);
    }

    public function getCdrData($xmlContent)
    {
        try {
            $doc = new \DOMDocument();
            $doc->loadXML($xmlContent);
            $xpath = new \DOMXPath($doc);
            $xpath->registerNamespace('x', $doc->documentElement->namespaceURI);

            $cdr_data = [];

            $resp = $xpath->query('/x:ApplicationResponse/cac:DocumentResponse/cac:Response');
            if ($resp->length === 1) {
                $obj = $resp[0];
                $cdr_data['code'] = $this->getValueByName($obj, 'ResponseCode');
                $cdr_data['message'] = $this->getValueByName($obj, 'Description');
            }

            $qr = $xpath->query('/x:ApplicationResponse/cac:DocumentResponse/cac:DocumentReference');
            if ($qr->length === 1) {
                $obj = $qr[0];
                $cdr_data['qr_url'] = $this->getValueByName($obj, 'DocumentDescription');
            }

            $nodes = $xpath->query('/x:ApplicationResponse/cbc:Note');
            $cdr_data['notes'] = [];
            if ($nodes->length > 0) {
                foreach ($nodes as $node) {
                    $cdr_data['notes'][] = $node->nodeValue;
                }
            }

            return $cdr_data;

        } catch (\Exception $e) {
            return null;
        }
    }

    private function getValueByName(\DOMElement $node, $name)
    {
        $values = $node->getElementsByTagName($name);
        if ($values->length !== 1) {
            return '';
        }

        return $values[0]->nodeValue;
    }
}
