# Análisis de optimización (Fase 2) – `/api/documents` y `/api/sale-note`

**Objetivo:** Identificar causas restantes de latencia y proponer mejoras **sin modificar aún el código**, para que el tiempo de respuesta baje más después de las optimizaciones ya aplicadas (PDF/email en cola, validación de caja integrada, `print_data` sin logo).

---

## 1. Resumen de lo que sigue siendo síncrono

### 1.1 Endpoint `/api/documents` (Facturas/Boletas)

Dentro de la **misma transacción** y antes de devolver la respuesta ocurre:

| Paso | Dónde | Coste estimado | Observación |
|------|--------|------------------|-------------|
| Validación caja | `validationOpenCash()` | ~5–20 ms | 1 query `Cash::where(...)`. Aceptable. |
| `Facturalo::save()` | Transacción | 100–400 ms | Varios INSERTs (document, items, payments, invoice, etc.). |
| `createXmlUnsigned()` | Transacción | 50–150 ms | Construcción del XML en memoria. |
| **`servicePseSendXml()`** | Transacción | **500–3000 ms** | **Llamadas HTTP al PSE:** `getToken()` + `sendXml()`. Si el PSE está lento o la red tiene latencia, todo el request espera. |
| `signXmlUnsigned()` | Transacción | 100–300 ms | Firma criptográfica (CPU). |
| `updateHash()` / `updateQr()` | Transacción | 20–50 ms | UPDATEs en BD. |
| **`senderXmlSignedBill()`** | Transacción | **500–5000 ms** | **Envío al PSE/OSE/SUNAT** (`sendXmlSigned` o enlace SOAP). De nuevo, red y proveedor determinan el tiempo. |
| **`buildPrintData($document)`** | Fuera transacción | **100–400 ms** | Muchas consultas y un JSON muy grande (ver sección 2). |

**Conclusión:** Los dos mayores cuellos de botella que siguen en la respuesta son:

1. **Llamadas HTTP síncronas al PSE y a SUNAT/OSE** (varios segundos en el peor caso).
2. **Construcción y tamaño de `print_data`** (consultas + payload).

---

### 1.2 Endpoint `/api/sale-note` (Notas de venta)

| Paso | Dónde | Coste estimado | Observación |
|------|--------|------------------|-------------|
| Validación caja | `validationOpenCash()` | ~5–20 ms | Igual que documentos. |
| **`mergeData()`** (con `force_create_if_not_exist`) | Antes/dentro transacción | **200–800 ms** | Búsquedas/creación de cliente y, por cada ítem, búsqueda/creación de producto. Varias queries y posibles escrituras. |
| **`ExtraLog()`** en `mergeData()` | Si `force_create_if_not_exist` | **20–100 ms** | **3 llamadas activas** a `self::ExtraLog(...)` (líneas 227, 232, 250) escribiendo a disco en cada nota con ítems “full_item”. |
| Transacción: `updateOrCreate` + ítems + lotes + **`new SaleNoteController()`** | Dentro transacción | 150–400 ms | Instanciar otro controlador solo para `savePayments()` es innecesario y suma algo de memoria/CPU. |
| **`buildPrintData($sale_note)`** | Fuera transacción | **100–350 ms** | Mismo patrón que documentos: muchas relaciones y consultas adicionales (ver sección 2). |

**Conclusión:** Aquí los focos son:

1. **PSE/SUNAT** no aplica; el peso está en **mergeData**, **logs** y **buildPrintData**.
2. **`print_data`** completo en cada respuesta hace la respuesta pesada y más lenta.

---

## 2. Análisis de `buildPrintData` (ambos endpoints)

En **DocumentController** y **SaleNoteController**, `buildPrintData` se ejecuta **siempre** antes de devolver 200 y hace lo siguiente:

### 2.1 Consultas a base de datos

- **`$document->load([...])`** o **`$saleNote->load([...])`**: carga muchas relaciones en varias consultas (items, payments, person, establishment, document_type, seller, payment_condition, etc.).
- **Document:** no se incluye `invoice` ni `currency_type` en el `load()`, por lo que al usar `$document->invoice` y `$document->currency_type` se disparan **2 consultas lazy** adicionales por request.
- **`Company::active()`**: en ambos es `Company::first()` sin caché; se ejecuta en **cada** venta.
- **`StateType::find($id)`** en Document: una consulta por documento solo para la descripción del estado.
- **`BankAccount::where('show_in_documents', true)->where('status', 1)->with('bank')->get()`**: se ejecuta en **cada** venta aunque las cuentas bancarias no cambien.

En conjunto son **del orden de 15–25 consultas** por venta solo para montar `print_data`.

### 2.2 Tamaño de la respuesta

El objeto `print_data` incluye:

- company, document_type, number, dates, customer, **items** (todos los ítems con muchos campos duplicados: code/cod, name/description/product_name, etc.), totales, impuestos, pagos, cuentas bancarias, total en letras, vendedor, etc.

Aunque el logo ya no va en base64, el resto sigue siendo un JSON grande (varios KB por comprobante). Eso aumenta:

- Tiempo de serialización en PHP.
- Tiempo de envío por la red.
- Tiempo de parsing en el frontend.

### 2.3 Resumen de impacto de `buildPrintData`

- **Tiempo:** ~100–400 ms por request (consultas + construcción del array).
- **Tamaño:** Respuesta más grande de lo necesario si el frontend no siempre imprime al instante.

---

## 3. Propuestas de optimización (ordenadas por impacto)

### 3.1 Prioridad muy alta – Desacoplar envío a PSE/SUNAT (`/api/documents`)

**Problema:** Toda la respuesta del API espera a:

- `servicePseSendXml()` (token + envío XML al PSE).
- `senderXmlSignedBill()` (envío firmado a PSE/OSE/SUNAT).

**Propuesta:**

- Registrar el documento en BD con un estado tipo “Registrado / Pendiente de envío” y **cerrar la transacción**.
- Devolver **200** con `id`, `number_full`, `external_id`, etc., **sin esperar** al PSE ni a SUNAT.
- Lanzar un **Job** (por ejemplo `SendDocumentToPseSunatJob`) que:
  - Genere XML sin firmar, llame al PSE, firme, actualice hash/QR, envíe a SUNAT/OSE y actualice `state_type_id` y CDR.

**Forma de implementación:**

- Ajustar `DocumentController::store()` para que, tras `save()` + `createXmlUnsigned()` (y lo mínimo para dejar el documento consistente), haga `commit` y `dispatch(SendDocumentToPseSunatJob::class, ...)`.
- El frontend puede seguir mostrando “Venta registrada” de inmediato y, si necesita el estado final o el CDR, consultar un endpoint tipo `GET /api/documents/{id}/status` o usar el flujo actual de descarga de PDF/CDR cuando el Job haya terminado.

**Beneficio:** Reducción de **1–5+ segundos** en el tiempo de respuesta del `store`, dependiendo del PSE/SUNAT.

---

### 3.2 Prioridad alta – Hacer `print_data` opcional o bajo demanda

**Problema:** En cada venta se construye y envía un `print_data` muy grande, con muchas consultas.

**Opciones (de mayor a menor impacto):**

**A) No enviar `print_data` por defecto**

- En la respuesta del `store` devolver solo `success`, `data` (id, number_full, external_id, print_ticket, etc.) y `links`.
- Si el frontend necesita imprimir al momento, que llame a un endpoint dedicado, por ejemplo:
  - `GET /api/documents/{id}/print-data` o
  - `GET /api/sale-note/{id}/print-data`
- Esos endpoints harían el `load` + `buildPrintData` solo cuando realmente se vaya a imprimir.

**Beneficio:** Respuesta del `store` más rápida (~100–400 ms menos) y mucho más liviana.

**B) Si se mantiene `print_data` en el `store`**

- Incluir en el `load()` del documento **`invoice`** y **`currency_type`** para evitar las 2 consultas lazy.
- Reducir campos duplicados en cada ítem (un solo nombre, un solo código, etc.) para bajar el tamaño del JSON.

---

### 3.3 Prioridad alta – Cachear datos maestros usados en `buildPrintData`

**Problema:** En cada venta se consultan datos que cambian poco:

- `Company::active()` → `Company::first()`.
- `BankAccount::where(...)->with('bank')->get()`.
- `StateType::find($id)` (o listado de estados).

**Propuesta:**

- Cachear en **Redis** o **caché de Laravel** (por tenant):
  - Empresa activa (clave por tenant).
  - Cuentas bancarias “para documentos”.
  - Catálogo de `StateType` (por id).
- TTL corto (ej. 5–15 minutos) o invalidación al guardar en Configuración / Cuentas bancarias.
- Usar ese caché dentro de `buildPrintData` (y en el Job de PDF si lo usa).

**Beneficio:** Menos consultas por venta y respuestas algo más rápidas.

---

### 3.4 Prioridad media – Optimizar `buildPrintData` (consultas)

**Problema:** Varias consultas y relaciones que se pueden cargar de forma más eficiente.

**Propuesta:**

- Incluir en el `load()` del documento/nota de venta **todas** las relaciones que usa `buildPrintData`, incluyendo **`invoice`** y **`currency_type`** en Document, para **cero lazy loading** en ese método.
- Revisar que no se hagan N+1 (por ejemplo en ítems o pagos); ya se usa `load()` con relaciones anidadas, pero conviene asegurar que no quede ningún acceso a relación no cargada.
- Si se implementa el endpoint bajo demanda de `print_data` (3.2), estas optimizaciones tienen aún más valor porque ese endpoint será el único que pague el coste.

---

### 3.5 Prioridad media – Sale-note: quitar logs y optimizar pagos

**Problema:**

- **ExtraLog:** En `SaleNoteController::mergeData()` siguen activas **3 llamadas** a `self::ExtraLog(...)` cuando se usa `force_create_if_not_exist` (líneas 227, 232, 250). Escriben a disco en cada nota con ítems “full_item”.
- **Instanciación:** `new \App\Http\Controllers\Tenant\SaleNoteController()` solo para llamar a `savePayments()`.

**Propuesta:**

- **Eliminar o comentar** las 3 llamadas a `ExtraLog` en `mergeData()` para el flujo API (o desactivarlas por configuración en entorno de producción).
- **Sustituir** la creación del controlador por una llamada directa a un servicio o al mismo método de pagos desde el controlador actual (por ejemplo `$this->savePayments(...)` si la firma lo permite, o extraer `savePayments` a un `SaleNotePaymentService` y usarlo desde el API).

**Beneficio:** Menos I/O de disco y código más claro; pequeña mejora de tiempo y consistencia.

---

### 3.6 Prioridad media – `mergeData` y creación de clientes/ítems

**Problema:** Con `force_create_if_not_exist`, en cada venta se hacen búsquedas/creaciones de cliente y de ítems (y a veces lotes), todo dentro o muy cerca de la transacción.

**Propuesta (sin cambiar comportamiento funcional):**

- Reutilizar **una sola** instancia de `Company` (por request) en lugar de volver a consultar donde no haga falta.
- Para ítems: si el frontend puede enviar siempre `item_id` cuando el producto ya existe en el sistema, evitar la rama “crear ítem” en la mayoría de los casos y reducir consultas.
- Si hay muchos ítems, valorar **inserciones en lote** de `SaleNoteItem` en lugar de `firstOrNew` + `save()` por fila (cambiaría un poco el flujo; requiere validación).

**Beneficio:** Menos consultas y menos escrituras por venta cuando se usa `force_create_if_not_exist`.

---

### 3.7 Prioridad baja – Índices y consultas pesadas

**Estado actual:** Ya existe la migración `2025_12_12_184028_add_performance_indexes_to_documents_table.php` con índices para documentos (fecha, tipo, estado, cliente, series/number, etc.).

**Propuesta:**

- Revisar en **sale_notes** índices similares si se filtran por `date_of_issue`, `series`, `number`, `customer_id` o `user_id` en listados o reportes.
- Asegurar índice en `external_id` en **documents** y **sale_notes** si el frontend o los Jobs buscan por `external_id`.
- No hace falta tocar código de negocio; solo migraciones de índices.

---

## 4. Resumen de impacto esperado (estimado)

| Medida | Endpoint | Reducción estimada |
|--------|----------|--------------------|
| Envío PSE/SUNAT en Job (asíncrono) | `/api/documents` | **1–5+ s** |
| `print_data` bajo demanda (o opcional) | Ambos | **~100–400 ms** + respuesta más liviana |
| Cache Company + BankAccounts + StateType | Ambos | **~30–80 ms** |
| Incluir `invoice` y `currency_type` en `load()` | `/api/documents` | **~10–30 ms** |
| Eliminar ExtraLog en mergeData | `/api/sale-note` | **~20–100 ms** (cuando se usa force_create) |
| Evitar `new SaleNoteController()` para pagos | `/api/sale-note` | **~5–20 ms** |
| Índices en sale_notes / external_id | Ambos | Variable según volumen y consultas |

---

## 5. Orden sugerido de implementación

1. **Fase 2.1:** Eliminar ExtraLog en `mergeData` y dejar de instanciar `SaleNoteController` para pagos (rápido, bajo riesgo).
2. **Fase 2.2:** Incluir `invoice` y `currency_type` en el `load()` de `buildPrintData` de documentos y cachear Company/BankAccounts/StateType (mejora directa de consultas).
3. **Fase 2.3:** Hacer `print_data` opcional o bajo demanda (endpoint dedicado); ajustar frontend para usar ese endpoint solo al imprimir.
4. **Fase 2.4:** Mover envío a PSE/SUNAT a un Job y devolver 200 tras guardar el documento (cambio de flujo más grande; definir bien el estado “pendiente de envío” y la consulta de estado/CDR).

Con esto se atacan en orden los mayores cuellos de botella que siguen después de las optimizaciones ya realizadas (PDF/email en cola y caja integrada), sin modificar código en este análisis, solo definiendo **qué** cambiar y **de qué manera** para una siguiente fase de implementación.
