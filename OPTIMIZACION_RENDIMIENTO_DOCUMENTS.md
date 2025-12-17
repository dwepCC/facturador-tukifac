# Análisis y Optimización de Rendimiento - Documents

## Problemas Identificados

### 1. **PROBLEMA CRÍTICO: N+1 Queries en DocumentCollection**

**Ubicación:** `app/Http/Resources/Tenant/DocumentCollection.php`

**Problemas:**
- Línea 109: `$row->payments->sum('payment')` - Sin eager loading
- Línea 129: `EmailSendLog::Document()->FindRelationId($row->id)->get()` - **Consulta dentro del loop!**
- Línea 143: `$row->payments` - Acceso repetido
- Línea 157: `$row->soap_type->description` - Sin eager loading
- Línea 161-164: `$row->customer` - Sin eager loading
- Línea 175: `$row->state_type->description` - Sin eager loading
- Línea 176: `$row->document_type->description` - Sin eager loading
- Línea 206: `$row->user` - Sin eager loading
- Línea 213: `$row->affected_documents` - Sin eager loading
- Línea 250: `$row->reference_guides` - Sin eager loading
- Línea 256: `$row->dispatch` - Sin eager loading
- Línea 121: `$row->response_regularize_shipping` - Relación no definida

**Impacto:** Para 50 documentos, esto genera **600+ consultas adicionales**

### 2. **PROBLEMA: recordsTotal() ejecuta getRecords() 4 veces**

**Ubicación:** `app/Http/Controllers/Tenant/DocumentController.php` líneas 136-167

```php
$BV = $this->getRecords($request)->where('document_type_id', $BV_t->id)->where('currency_type_id', 'PEN')->sum('total');
$FT = $this->getRecords($request)->where('document_type_id', $FT_t->id)->where('currency_type_id', 'PEN')->sum('total');
$NC = $this->getRecords($request)->where('document_type_id', $NC_t->id)->where('currency_type_id', 'PEN')->sum('total');
$ND = $this->getRecords($request)->where('document_type_id', $ND_t->id)->where('currency_type_id', 'PEN')->sum('total');
```

**Impacto:** Ejecuta la misma consulta base 4 veces con diferentes filtros

### 3. **PROBLEMA: getRecords() sin eager loading**

**Ubicación:** `app/Http/Controllers/Tenant/DocumentController.php` línea 1256

```php
$records = Document::query();
```

No carga relaciones necesarias antes de la paginación.

### 4. **PROBLEMA: Consultas en métodos del modelo dentro del loop**

**Ubicación:** `DocumentCollection.php`
- Línea 123: `$row->getNvCollection()` - Método que probablemente hace consultas
- Línea 125: `$row->getOrderNoteCollection()` - Método que probablemente hace consultas
- Línea 232: `$row->getPlateNumbers()` - Método que probablemente hace consultas

### 5. **PROBLEMA: Falta de índices en base de datos**

Faltan índices compuestos para consultas frecuentes:
- `(date_of_issue, document_type_id, state_type_id)`
- `(customer_id, date_of_issue)`
- `(state_type_id, date_of_issue)`
- `(series, number)` - Para búsquedas exactas

### 6. **PROBLEMA: Configuration::first() en cada request**

**Ubicación:** `DocumentCollection.php` línea 25 (en otro Resource similar)

Se ejecuta en cada transformación.

## Soluciones Propuestas

### SOLUCIÓN 1: Agregar Eager Loading en getRecords()

```php
public function getRecords($request)
{
    // ... código existente de filtros ...
    
    return $records
        ->with([
            'customer:id,name,number,telephone,email',
            'state_type:id,description',
            'document_type:id,description',
            'soap_type:id,description',
            'user:id,name,email',
            'payments:id,document_id,payment,date_of_payment',
            'invoice:id,document_id,date_of_due',
            'affected_documents.document:id,series,number',
            'reference_guides:id,series,number',
            'dispatch:id,series,number',
            'retention:id,document_id,amount',
            'note.affected_document:id,series,number',
            'note.data_affected_document',
        ])
        ->whereTypeUser()
        ->latest();
}
```

### SOLUCIÓN 2: Optimizar recordsTotal()

```php
public function recordsTotal(Request $request)
{
    $records = $this->getRecords($request)
        ->select('document_type_id', 'currency_type_id', DB::raw('SUM(total) as total_sum'))
        ->where('currency_type_id', 'PEN')
        ->groupBy('document_type_id', 'currency_type_id')
        ->get()
        ->keyBy('document_type_id');

    $FT_t = DocumentType::find('01');
    $BV_t = DocumentType::find('03');
    $NC_t = DocumentType::find('07');
    $ND_t = DocumentType::find('08');

    return [
        [
            'name' => $FT_t->description,
            'total' => "S/ " . ReportHelper::setNumber($records->get('01')->total_sum ?? 0),
        ],
        [
            'name' => $BV_t->description,
            'total' => "S/ " . ReportHelper::setNumber($records->get('03')->total_sum ?? 0),
        ],
        [
            'name' => $NC_t->description,
            'total' => "S/ " . ReportHelper::setNumber($records->get('07')->total_sum ?? 0),
        ],
        [
            'name' => $ND_t->description,
            'total' => "S/ " . ReportHelper::setNumber($records->get('08')->total_sum ?? 0),
        ],
    ];
}
```

### SOLUCIÓN 3: Optimizar DocumentCollection - Cargar EmailSendLog en batch

```php
public function toArray($request)
{
    // Obtener todos los IDs de documentos
    $documentIds = $this->collection->pluck('id')->toArray();
    
    // Cargar todos los logs de email en una sola consulta
    $emailLogs = EmailSendLog::Document()
        ->whereIn('relation_id', $documentIds)
        ->get()
        ->groupBy('relation_id');
    
    return $this->collection->transform(function ($row, $key) use ($emailLogs) {
        // ... código existente ...
        
        // Usar los logs precargados
        $send_it = $emailLogs->get($row->id, collect());
        if ($send_it->count() > 0) {
            foreach ($send_it as $log) {
                // ... código existente ...
            }
        }
        
        // ... resto del código ...
    });
}
```

### SOLUCIÓN 4: Agregar índices en base de datos

```php
// Crear migración: php artisan make:migration add_indexes_to_documents_table

Schema::table('documents', function (Blueprint $table) {
    // Índice compuesto para filtros comunes
    $table->index(['date_of_issue', 'document_type_id', 'state_type_id'], 'idx_date_type_state');
    
    // Índice para búsqueda por cliente
    $table->index(['customer_id', 'date_of_issue'], 'idx_customer_date');
    
    // Índice para búsqueda por estado
    $table->index(['state_type_id', 'date_of_issue'], 'idx_state_date');
    
    // Índice único compuesto para series/number (si no existe)
    $table->index(['series', 'number'], 'idx_series_number');
    
    // Índice para currency_type_id (usado en recordsTotal)
    $table->index(['currency_type_id', 'document_type_id'], 'idx_currency_doc_type');
});
```

### SOLUCIÓN 5: Cachear Configuration

```php
// En DocumentCollection o en un Service Provider
$configuration = Cache::remember('configuration', 3600, function () {
    return Configuration::first();
});
```

### SOLUCIÓN 6: Optimizar métodos del modelo

Precargar datos en el eager loading o usar subconsultas:

```php
// En getRecords()
->with([
    // ... otras relaciones ...
    'sale_notes:id,number,series,state_type_id', // Si getNvCollection() usa esto
    'order_note:id,identifier', // Si getOrderNoteCollection() usa esto
])
```

### SOLUCIÓN 7: Usar select() para limitar columnas

```php
public function getRecords($request)
{
    // ... filtros ...
    
    return $records
        ->select([
            'documents.id',
            'documents.date_of_issue',
            'documents.number',
            'documents.series',
            'documents.document_type_id',
            'documents.state_type_id',
            'documents.customer_id',
            'documents.total',
            'documents.currency_type_id',
            'documents.soap_type_id',
            'documents.user_id',
            // ... solo las columnas necesarias
        ])
        ->with([/* relaciones */])
        ->whereTypeUser()
        ->latest();
}
```

## Implementación Priorizada

### Prioridad ALTA (Impacto inmediato):
1. ✅ Agregar eager loading en `getRecords()`
2. ✅ Optimizar `recordsTotal()` para una sola consulta
3. ✅ Optimizar `EmailSendLog` en `DocumentCollection`
4. ✅ Agregar índices en base de datos

### Prioridad MEDIA:
5. ✅ Usar `select()` para limitar columnas
6. ✅ Cachear `Configuration`
7. ✅ Optimizar métodos del modelo

### Prioridad BAJA:
8. Considerar paginación con cursor para grandes volúmenes
9. Implementar caché de consultas frecuentes
10. Considerar usar Redis para sesiones y caché

## Estimación de Mejora

**Antes:**
- Consultas: ~650+ queries para 50 documentos
- Tiempo: 7+ segundos

**Después (con optimizaciones):**
- Consultas: ~10-15 queries para 50 documentos
- Tiempo estimado: 0.5-1 segundo

**Mejora esperada: 85-90% de reducción en tiempo de carga**

