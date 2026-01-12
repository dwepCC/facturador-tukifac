# Estructura de `print_data` para Impresión Directa Bluetooth

Este documento describe la estructura que debe tener el campo `print_data` en la respuesta del backend para habilitar la impresión directa en impresoras Bluetooth térmicas, evitando el procesamiento de PDF.

## Ubicación en la Respuesta

El campo `print_data` debe estar en el nivel raíz de la respuesta del endpoint de creación de documentos de venta:

```json
{
  "data": {
    "number": "NV01-00000606",
    "external_id": "...",
    "filename": "...",
    "print_ticket": "https://..."
  },
  "print_data": {
    // Estructura detallada abajo
  },
  "links": {
    "pdf": "https://..."
  },
  "data_ws": {
    "pdf_a4_filename": "https://...",
    "message_text": "..."
  }
}
```

## Estructura Completa de `print_data`

### Campos Principales

```json
{
  // ========== INFORMACIÓN DE LA EMPRESA ==========
  "company": {                   // Información de la empresa (OPCIONAL, se usa la de sesión si no se envía)
    "name": "string",            // Nombre o razón social de la empresa
    "ruc": "string",             // RUC de la empresa
    "address": "string",          // Dirección fiscal
    "commercial_address": "string", // Dirección comercial (si es diferente)
    "phone": "string",           // Teléfono
    "email": "string",           // Email
    "web": "string",             // Sitio web
    "slogan": "string"           // Mensaje/slogan de la empresa
  },
  
  // ========== INFORMACIÓN DEL DOCUMENTO ==========
  "document_type": "string",     // Tipo de documento: "BOLETA DE VENTA ELECTRÓNICA", "FACTURA ELECTRÓNICA", "NOTA DE VENTA"
  "number": "string",            // Número del documento (ej: "B002-00000142", "NV01-00000606")
  "date": "string",              // Fecha de emisión (ej: "2026-01-08" o "2026-01-08 16:11:52")
  "date_of_issue": "string",     // Alternativa a "date"
  "issue_time": "string",        // Hora de emisión (ej: "11:39:20")
  "due_date": "string",          // Fecha de vencimiento (OPCIONAL)
  
  // ========== INFORMACIÓN DEL CLIENTE ==========
  "customer": {                  // Datos del cliente (OPCIONAL)
    "name": "string",            // Nombre o razón social (por defecto: "999999999 - 999999999 - Clientes - Varios")
    "number": "string",          // DNI/RUC (por defecto: "99999999")
    "address": "string",         // Dirección (por defecto: "lima,, -")
    "doc_trib_no_dom_sin_ruc": "string" // Alternativa a "number"
  },
  
  // ========== ITEMS DEL DOCUMENTO ==========
  "items": [                     // Array de productos/items (REQUERIDO)
    {
      "code": "string",          // Código del producto (OPCIONAL)
      "cod": "string",           // Alternativa a "code"
      "name": "string",          // Nombre del producto (REQUERIDO)
      "description": "string",   // Alternativa a "name"
      "product_name": "string",  // Alternativa a "name"
      "quantity": number,        // Cantidad (REQUERIDO)
      "qty": number,             // Alternativa a "quantity"
      "amount": number,          // Alternativa a "quantity"
      "unit": "string",          // Unidad de medida (por defecto: "NIU")
      "unidad": "string",        // Alternativa a "unit"
      "price": number,           // Precio unitario (REQUERIDO)
      "unit_price": number,      // Alternativa a "price"
      "sale_unit_price": number, // Alternativa a "price"
      "subtotal": number,        // Subtotal del item (quantity * price)
      "total": number            // Alternativa a "subtotal"
    }
  ],
  
  // ========== TOTALES ==========
  "subtotal": number,            // Subtotal de la venta (sin IGV)
  "total_value": number,         // Alternativa a "subtotal"
  "taxable_operations": number,  // Operaciones gravadas (OP. GRAVADAS)
  "tax": number,                 // Total de IGV/impuestos
  "total_igv": number,           // Alternativa a "tax"
  "total_taxes": number,         // Alternativa a "tax"
  "total": number,               // Total de la venta (con IGV) (REQUERIDO)
  "total_venta": number,         // Alternativa a "total"
  "total_in_words": "string",    // Total en palabras (ej: "Son: Quince con 28/100 Soles") - Se genera automáticamente si no se envía
  
  // ========== INFORMACIÓN DE PAGO ==========
  "payment_method": "string",    // Método de pago (ej: "Efectivo", "Tarjeta")
  "paymentMethod": "string",     // Alternativa a "payment_method"
  "payment_method_name": "string", // Alternativa a "payment_method"
  "payment_condition": "string",  // Condición de pago (por defecto: "Contado")
  "cash": number,                // Efectivo recibido
  "efectivo": number,            // Alternativa a "cash"
  "change": number,              // Vuelto/cambio
  "vuelto": number,              // Alternativa a "change"
  
  // ========== INFORMACIÓN BANCARIA ==========
  "bank_accounts": [            // Array de cuentas bancarias (OPCIONAL)
    {
      "bank_name": "string",     // Nombre del banco (ej: "BANCO DE CREDITO DEL PERU")
      "account_number": "string", // Número de cuenta
      "cci": "string"            // Código de cuenta interbancario
    }
  ],
  
  // ========== QR CODE Y HASH ==========
  "qr_data": "string",          // Datos para generar el código QR (solo para boletas/facturas, NO para notas de venta)
                                 // Si no se envía, se usa "aqui va el qr" como valor por defecto
  "hash_code": "string",         // Código hash del documento (OPCIONAL)
  
  // ========== OTROS ==========
  "seller": "string",           // Nombre del vendedor
  "vendedor": "string"          // Alternativa a "seller"
}
```

## Ejemplo Completo

### Ejemplo 1: Boleta de Venta Electrónica (con QR)

```json
{
  "data": {
    "number": "B002-00000142",
    "external_id": "...",
    "filename": "B002-00000142.pdf"
  },
  "print_data": {
    "company": {
      "name": "INVERSIONES DORICONTA S.A.C.",
      "ruc": "20604903824",
      "address": "CALLE PALACION VIEJO 210, 210, LIMA, LIMA - LIMA",
      "commercial_address": "calle santo domingo N 113",
      "phone": "916547430",
      "email": "assiria.malaga.5@gmail.com",
      "web": "comprobantes.grouprkm.com",
      "slogan": "AGRADECEMOS SU PREFERENCIA Y LA CONFIANZA DEPOSITADA EN NUESTROS PRODUCTOS. CADA ADQUISICIÓN RESPALDA A TODA UNA CADENA DE PRODUCCIÓN COMPROMETIDA CON LA CALIDAD Y AL BIENESTAR DE SUS FAMILIAS"
    },
    "document_type": "BOLETA DE VENTA ELECTRÓNICA",
    "number": "B002-00000142",
    "date": "2026-01-09",
    "date_of_issue": "2026-01-09",
    "issue_time": "11:39:20",
    "due_date": "2026-01-09",
    "customer": {
      "name": "999999999 - 999999999 - Clientes - Varios",
      "number": "99999999",
      "address": "lima,, -",
      "doc_trib_no_dom_sin_ruc": "99999999"
    },
    "items": [
      {
        "code": "3",
        "quantity": 1,
        "unit": "NIU",
        "name": "Cebolla Roja Metro x kg",
        "price": 2.29,
        "subtotal": 2.29
      },
      {
        "code": "2",
        "quantity": 1,
        "unit": "NIU",
        "name": "Pimiento Verde x kg",
        "price": 8.80,
        "subtotal": 8.80
      },
      {
        "code": "6",
        "quantity": 1,
        "unit": "NIU",
        "name": "Zanahoria Especial x kg",
        "price": 4.19,
        "subtotal": 4.19
      }
    ],
    "subtotal": 12.95,
    "taxable_operations": 12.95,
    "tax": 2.33,
    "total_igv": 2.33,
    "total": 15.28,
    "total_in_words": "Son: Quince con 28/100 Soles",
    "payment_condition": "Contado",
    "payment_method": "Efectivo",
    "cash": 0,
    "change": 0,
    "bank_accounts": [
      {
        "bank_name": "BANCO DE CREDITO DEL PERU",
        "account_number": "2154621466367",
        "cci": "564125987511230004"
      },
      {
        "bank_name": "INTERBANK",
        "account_number": "2415865133254",
        "cci": "120024500748790007"
      }
    ],
    "qr_data": "https://e-consulta.sunat.gob.pe/cl-ti-itconsvalicme/consValicMe",
    "hash_code": "cgb8uveyNhbFMsftEjXe1WL4fdo=",
    "seller": "demo1"
  }
}
```

### Ejemplo 2: Nota de Venta (sin QR)

```json
{
  "data": {
    "number": "NV01-00000606",
    "external_id": "14952eeb-0d05-468c-864a-91a1a4944242",
    "filename": "NV01-00000606.pdf"
  },
  "print_data": {
    "document_type": "NOTA DE VENTA",
    "number": "NV01-00000606",
    "date": "2026-01-08 16:11:52",
    "issue_time": "16:11:52",
    "items": [
      {
        "code": "1",
        "quantity": 2,
        "unit": "NIU",
        "name": "Ceviche de Pescado",
        "price": 25.00,
        "subtotal": 50.00
      },
      {
        "code": "2",
        "quantity": 1,
        "unit": "NIU",
        "name": "Lomo Saltado",
        "price": 35.00,
        "subtotal": 35.00
      }
    ],
    "subtotal": 85.00,
    "tax": 15.30,
    "total": 100.30,
    "payment_method": "Efectivo",
    "cash": 150.00,
    "change": 49.70,
    "customer": {
      "name": "Juan Pérez",
      "number": "12345678",
      "address": "Av. Principal 123"
    },
    "seller": "demo1"
  }
}

### Ejemplo 3: Factura con Cliente Empresarial (con QR)

```json
{
  "data": {
    "number": "F001-0000123",
    "external_id": "...",
    "filename": "F001-0000123.pdf"
  },
  "print_data": {
    "company": {
      "name": "INVERSIONES DORICONTA S.A.C.",
      "ruc": "20604903824",
      "address": "CALLE PALACION VIEJO 210, 210, LIMA, LIMA - LIMA",
      "phone": "916547430",
      "email": "assiria.malaga.5@gmail.com",
      "web": "comprobantes.grouprkm.com"
    },
    "document_type": "FACTURA ELECTRÓNICA",
    "number": "F001-0000123",
    "date": "2026-01-08",
    "issue_time": "10:30:00",
    "items": [
      {
        "code": "SERV001",
        "quantity": 10,
        "unit": "NIU",
        "name": "Servicio de Consultoría",
        "price": 150.00,
        "subtotal": 1500.00
      }
    ],
    "subtotal": 1500.00,
    "taxable_operations": 1500.00,
    "total_igv": 270.00,
    "tax": 270.00,
    "total": 1770.00,
    "total_in_words": "Son: Mil setecientos setenta con 00/100 Soles",
    "payment_condition": "Transferencia Bancaria",
    "payment_method": "Transferencia Bancaria",
    "cash": 0,
    "change": 0,
    "customer": {
      "name": "EMPRESA ABC S.A.C.",
      "number": "20123456789",
      "address": "Av. Los Olivos 456, Lima"
    },
    "bank_accounts": [
      {
        "bank_name": "BANCO DE CREDITO DEL PERU",
        "account_number": "2154621466367",
        "cci": "564125987511230004"
      }
    ],
    "qr_data": "https://e-consulta.sunat.gob.pe/cl-ti-itconsvalicme/consValicMe?numDoc=20123456789&tipDoc=6&numSerie=F001&numComp=0000123",
    "hash_code": "abc123def456ghi789",
    "seller": "demo1"
  }
}
```

## Campos Requeridos vs Opcionales

### Campos Requeridos

- `number`: Número del documento
- `total`: Total de la venta (con IGV)
- `items`: Array con al menos un item
  - `name` (o `description` o `product_name`): Nombre del producto
  - `quantity` (o `qty` o `amount`): Cantidad
  - `price` (o `unit_price` o `sale_unit_price`): Precio unitario

### Campos Opcionales con Valores por Defecto

- `company`: Si no se proporciona, se usa la información de la empresa de la sesión actual
  - Si no hay sesión, se usan valores por defecto: "EMPRESA", "00000000000", etc.
- `document_type`: Si no se proporciona, se infiere del número del documento (B=Boleta, F=Factura, NV=Nota de Venta)
- `date` / `date_of_issue`: Si no se proporciona, el frontend usará la fecha/hora actual
- `issue_time`: Si no se proporciona, se usa la hora actual
- `customer`: Si no se proporciona, se usa "999999999 - 999999999 - Clientes - Varios" con DNI "99999999"
- `subtotal` / `total_value` / `taxable_operations`: Si no se proporciona, se calculará sumando los subtotales de los items
- `tax` / `total_igv` / `total_taxes`: Si no se proporciona, se calculará como `total - subtotal`
- `total_in_words`: Si no se proporciona, se genera automáticamente en español
- `payment_method` / `payment_condition`: Si no se proporciona, se usa "Contado"
- `cash`: Si no se proporciona, será 0
- `change`: Si no se proporciona, será 0
- `bank_accounts`: Si no se proporciona, no se mostrará información bancaria
- `qr_data`: 
  - **Para Boletas/Facturas**: Si no se proporciona, se usa "aqui va el qr" como valor por defecto
  - **Para Notas de Venta**: No se muestra QR code (se ignora este campo)
- `hash_code`: Si no se proporciona, no se muestra
- `seller` / `vendedor`: Si no se proporciona, no se muestra
- `items[].code`: Si no se proporciona, se deja vacío
- `items[].unit`: Si no se proporciona, se usa "NIU"

## Mapeo en el Frontend

El frontend mapea automáticamente los diferentes nombres de campos. Por ejemplo:

- `quantity`, `qty`, `amount` → todos se mapean a `quantity`
- `price`, `unit_price`, `sale_unit_price` → todos se mapean a `price`
- `payment_method`, `paymentMethod`, `payment_method_name` → todos se mapean a `payment_method`
- `code`, `cod` → todos se mapean a `code`
- `unit`, `unidad` → todos se mapean a `unit`
- `seller`, `vendedor` → todos se mapean a `seller`
- `date`, `date_of_issue` → todos se mapean a `date`

## Formato de Fechas

Se aceptan los siguientes formatos:

- `"2026-01-08"`
- `"2026-01-08 16:11:52"`
- `"08/01/2026"`
- Cualquier formato que pueda ser parseado por JavaScript

## Formato de Números

Todos los valores numéricos deben ser números (no strings):

```json
// ✅ Correcto
"price": 25.00
"quantity": 2

// ❌ Incorrecto
"price": "25.00"
"quantity": "2"
```

## Compatibilidad con Versiones Anteriores

Si el backend **no incluye** `print_data` en la respuesta, el frontend automáticamente:

1. Usará el método anterior (descargar y procesar PDF)
2. No generará errores
3. Mantendrá la funcionalidad existente

Esto garantiza compatibilidad hacia atrás mientras se implementa la nueva funcionalidad.

## Endpoints Afectados

Los siguientes endpoints deben incluir `print_data` en su respuesta:

- `POST /api/sale-note` (Nota de Venta)
- `POST /api/documents` (Boletas, Facturas, etc.)

## Beneficios de Implementar `print_data`

1. **Rendimiento**: La impresión es instantánea, sin necesidad de descargar y procesar PDF
2. **Menor carga del servidor**: No se requiere generar PDF para impresión Bluetooth
3. **Mejor experiencia de usuario**: Sin demoras en la impresión
4. **Compatibilidad**: Funciona con el método anterior si no está disponible

## Notas de Implementación

- El campo `print_data` es completamente opcional
- Si está presente, se usará para impresión directa
- Si no está presente, se usará el método de procesamiento de PDF
- No es necesario modificar otros campos de la respuesta existente
- Se puede implementar gradualmente (solo en algunos tipos de documentos)

## Ejemplo de Implementación en PHP (Laravel)

```php
public function createSaleNote(Request $request)
{
    // ... lógica de creación del documento ...
    
    $saleNote = SaleNote::create([...]);
    
    // Preparar print_data
    $printData = [
        'number' => $saleNote->number,
        'date' => $saleNote->date_of_issue->format('Y-m-d H:i:s'),
        'items' => $saleNote->items->map(function ($item) {
            return [
                'name' => $item->item->description,
                'quantity' => $item->quantity,
                'price' => $item->unit_price,
                'subtotal' => $item->total
            ];
        })->toArray(),
        'subtotal' => $saleNote->subtotal,
        'tax' => $saleNote->total_igv,
        'total' => $saleNote->total,
        'payment_method' => $saleNote->payments->first()->payment_method_type->description ?? 'Efectivo',
        'cash' => $saleNote->payments->sum('payment'),
        'change' => $saleNote->payments->sum('change'),
        'customer' => $saleNote->customer ? [
            'name' => $saleNote->customer->name,
            'number' => $saleNote->customer->number,
            'address' => $saleNote->customer->address,
            'email' => $saleNote->customer->email,
            'telephone' => $saleNote->customer->telephone
        ] : null
    ];
    
    return response()->json([
        'data' => [
            'number' => $saleNote->number,
            'external_id' => $saleNote->external_id,
            'filename' => $saleNote->filename,
            'print_ticket' => $saleNote->getPrintTicketUrl()
        ],
        'print_data' => $printData, // ← Agregar este campo
        'links' => [
            'pdf' => $saleNote->getPdfUrl()
        ]
    ]);
}
```

## Validación Recomendada

Antes de enviar `print_data`, validar:

1. Que `items` tenga al menos un elemento
2. Que todos los items tengan `name` y `quantity` > 0
3. Que `total` sea igual a `subtotal + tax` (con tolerancia de redondeo)
4. Que `change` sea igual a `cash - total` (si aplica)

## Soporte

Para más información sobre la implementación en el frontend, consultar:
- `src/assets/js/script.js` (líneas 2797-2839)
- `src/services/bluetoothPrintService.js`
