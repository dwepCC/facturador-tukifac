# Documentación de Endpoints de Listas

Documentación técnica basada en el **análisis de las vistas del backend Laravel**. Refleja los filtros reales, endpoints usados y parámetros soportados.

---

## Contexto

- **Backend Laravel** con vistas internas (Vue SPA servido por Blade).
- **API** consumida por frontend externo y apps móviles.
- Las vistas internas usan **rutas WEB** (sesión). La API usa **rutas API** (token).
- Este documento mapea: **Vista → Endpoint → Parámetros reales** para alinear frontend externo con el backend.

---

## Configuración global

| Config | Valor por defecto | Descripción |
|--------|-------------------|-------------|
| `config('tenant.items_per_page')` | 20 (env: ITEMS_PER_PAGE) | Cantidad de registros por página |

---

# PARTE A: Backend – Vistas internas (rutas WEB)

Estos son los endpoints que usa el backend internamente. Controladores: `app/Http/Controllers/Tenant/`.

---

## 1. Documentos (Comprobantes)

### Vista

- **Archivo:** `resources/js/views/tenant/documents/index.vue`
- **Componente tabla:** `DataTableDocuments.vue`
- **Ruta web:** `/documents`

### Endpoint principal

```
GET /documents/records
```

**Controlador:** `DocumentController@records`  
**Respuesta:** `DocumentCollection` con paginación (`data` + `meta`).  
**Registros por página:** 10 (hardcodeado en controlador).

### Filtros reales (DataTableDocuments)

| Parámetro | Tipo | Descripción | Implementación backend |
|-----------|------|-------------|------------------------|
| page | int | Página actual | Laravel paginator |
| limit | int | Registros por página (opcional) | Ignorado; backend usa 10 |
| document_type_id | string | Tipo comprobante (01, 03, 07, 08) | `where document_type_id like %...%` |
| series | string | Serie | `where series like %...%` |
| number | string | Número | `where number = ...` |
| d_start | string | Fecha inicio. Formato: `YYYY-MM-DD` | `whereBetween date_of_issue` |
| d_end | string | Fecha término. Formato: `YYYY-MM-DD` | |
| date_of_issue | string | Fecha emisión exacta | `where date_of_issue like %...%` |
| customer_id | int | ID cliente | `where customer_id = ...` |
| item_id | int | ID producto | `whereHas items` |
| category_id | int | ID categoría | `whereHas items.relation_item` |
| state_type_id | string | Estado (01, 03, 05, 07, 09, 11, 13) | `where state_type_id like %...%` |
| purchase_order | string | Orden de compra | `where purchase_order = ...` |
| observations | string | Observaciones | `where additional_information like %...%` |
| guides | string | Número de guía | `where guides like %...%` |
| plate_numbers | string | Placa | `where plate_number like %...%` |
| pending_payment | bool | Pendiente de pago | `where total_canceled = false` |

**Nota:** `d_start`/`d_end` y `date_of_issue` son mutuamente excluyentes en la UI.

### Endpoints auxiliares

| Endpoint | Uso |
|----------|-----|
| `GET /documents/data_table` | Catálogos: customers, items, categories, state_types, document_types, series, establishments |
| `GET /documents/recordsTotal` | Totales por tipo (FT, BV, NC, ND) según filtros |
| `GET /documents/data-table/customers?input=` | Búsqueda remota de clientes |
| `GET /documents/data-table/items?input=` | Búsqueda remota de productos |

---

## 2. Notas de venta

### Vista

- **Archivo:** `resources/js/views/tenant/sale_notes/index.vue`
- **Componente tabla:** `DataTableSaleNote.vue`
- **Ruta web:** `/sale-notes`

### Endpoint principal

```
GET /sale-notes/records
```

**Controlador:** `SaleNoteController@records`  
**Respuesta:** `SaleNoteLightCollection` con paginación (`data` + `meta`).  
**Registros por página:** 10 (hardcodeado).

### Filtros reales (DataTableSaleNote)

| Parámetro | Tipo | Descripción | Implementación backend |
|-----------|------|-------------|------------------------|
| page | int | Página actual | Laravel paginator |
| column | string | Campo a filtrar | Ver columnas disponibles |
| value | string | Valor de búsqueda | `where column like %value%` o relacional |
| series | string | Serie | `where series like %...%` |
| number | string | Número | `where number like %...%` |
| total_canceled | int | Estado pago: 1=Pagado, 0=Pendiente | `where total_canceled = ...` |
| purchase_order | string | Orden de compra | `where purchase_order = ...` |
| observations | string | Observaciones | `where observation like %...%` |
| license_plate | string | Placa (si search_by_plate) | `where license_plate = ...` |

**Columnas disponibles** (GET `/sale-notes/columns`):

- `date_of_issue` – Fecha de emisión
- `customer` – Cliente (búsqueda en person.name o person.number)

### Endpoints auxiliares

| Endpoint | Uso |
|----------|-----|
| `GET /sale-notes/columns` | Columnas para filtro dinámico |
| `GET /sale-notes/columns2` | Series disponibles (document_type 80) |
| `GET /sale-notes/totals` | Totales (total_pen, total_paid_pen, total_pending_paid_pen) según filtros |

---

## 3. Cotizaciones

### Vista

- **Archivo:** `resources/js/views/tenant/quotations/index.vue`
- **Componente tabla:** `DataTableQuotation.vue`
- **Ruta web:** `/quotations`

### Endpoint principal

```
GET /quotations/records
```

**Controlador:** `QuotationController@records`  
**Respuesta:** `QuotationCollection` con paginación (`data` + `meta`).  
**Registros por página:** `config('tenant.items_per_page')` (20 por defecto).

### Filtros reales (DataTableQuotation)

| Parámetro | Tipo | Descripción | Implementación backend |
|-----------|------|-------------|------------------------|
| page | int | Página actual | Laravel paginator |
| column | string | Campo a filtrar | Ver columnas disponibles |
| value | string | Valor de búsqueda | Depende de column |
| form | string (JSON) | Objeto con periodo y estado | Ver abajo |

**Objeto `form` (JSON):**

| Campo | Tipo | Descripción |
|-------|------|-------------|
| period | string | `month`, `week`, `between_dates` |
| week | string | Si period=week, fecha de la semana |
| month | string | Si period=month, formato `YYYY-MM` |
| d_start | string | Si period=between_dates, fecha inicio `YYYY-MM-DD` |
| d_end | string | Si period=between_dates, fecha fin `YYYY-MM-DD` |
| date_start | string | Calculado: inicio del periodo |
| date_end | string | Calculado: fin del periodo |
| state_type_id | string | Estado (01, 05, 09) |

**Columnas disponibles** (GET `/quotations/columns`):

- `customer`, `date_of_issue`, `delivery_date`, `user_name`, `seller_name`, `referential_information`, `number`, `observations`

**Filtro por column/value:** Según columna: `user_name` (relación user), `customer` (relación person), `seller_name` (relación seller), `observations` (description), `number` (id).

---

## 4. Guías de remisión (Dispatches)

### Vista

- **Archivo:** `resources/js/views/tenant/dispatches/index.vue`
- **Componente tabla:** `DataTableDispatch.vue`
- **Ruta web:** `/dispatches`

### Endpoint principal (WEB)

```
GET /dispatches/records
```

**Controlador:** `DispatchController@records` (Tenant, no Api)  
**Respuesta:** `DispatchCollection` con paginación (`data` + `meta`).  
**Registros por página:** `config('tenant.items_per_page')`.

### Filtros reales (DataTableDispatch)

| Parámetro | Tipo | Descripción | Implementación backend |
|-----------|------|-------------|------------------------|
| page | int | Página actual | Laravel paginator |
| customer_id | int | ID cliente | `where customer_id = ...` |
| series | string | Serie | `where series like %...%` |
| number | string | Número | `where number = ...` |
| d_start | string | Fecha inicio `YYYY-MM-DD` | `whereBetween date_of_issue` |
| d_end | string | Fecha término `YYYY-MM-DD` | |

**Nota:** El controlador WEB filtra siempre por `document_type_id = '09'` (Guía remisión). No expone filtro por tipo.

### Endpoint auxiliar

| Endpoint | Uso |
|----------|-----|
| `GET /dispatches/data_table` | customers, series |

---

# PARTE B: API – Endpoints para frontend externo

Estos endpoints están en `routes/api.php` y se usan por el frontend Vue externo y apps móviles.

---

## Comparativa Backend WEB vs API

| Módulo | Backend (WEB) | API (legacy) | API records (alineado) | Paginación |
|--------|---------------|--------------|------------------------|------------|
| Documentos | `GET /documents/records` | `GET /api/documents/lists` | **`GET /api/documents/records`** | Sí (10/pág) |
| Notas venta | `GET /sale-notes/records` | `GET /api/sale-note/lists` | **`GET /api/sale-note/records`** | Sí (10/pág) |
| Cotizaciones | `GET /quotations/records` | `GET /api/quotations/list` | **`GET /api/quotations/records`** | Sí (20/pág) |
| Guías | `GET /dispatches/records` | - | **`GET /api/dispatches/records`** | Sí (20/pág) |

Los endpoints **`/api/.../records`** replican exactamente la lógica de las rutas WEB (filtros, paginación, estructura de respuesta).

---

## 1. API – Documentos

### records (alineado con WEB) – RECOMENDADO

```
GET /api/documents/records
```

**Controlador:** `App\Http\Controllers\Tenant\Api\DocumentController@records` (delega en `Tenant\DocumentController@records`)  
Acepta los mismos parámetros que `GET /documents/records` (ver Parte A). Paginación con `data` + `meta` + `links`.  
**Tamaño de página:** fijo en **10** registros (igual que la vista WEB; los parámetros `limit` de DataTable o `per_page` en la API **no** cambian este valor).

### lists (legacy)

```
GET /api/documents/lists
GET /api/documents/lists/{startDate}/{endDate}
```

| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| startDate, endDate | path | Solo en la segunda ruta. Formato: `YYYY-MM-DD` |

**Comportamiento:** Sin paginación real. `lists` devuelve 50; `lists/{start}/{end}` devuelve todos en el rango.

---

## 2. API – Notas de venta

### records (alineado con WEB) – RECOMENDADO

```
GET /api/sale-note/records
```

**Controlador:** `App\Http\Controllers\Tenant\Api\SaleNoteController@records` (delega en `Tenant\SaleNoteController@records`)  
Acepta los mismos parámetros que `GET /sale-notes/records` (ver Parte A). Paginación con `data` + `meta` + `links`.

### lists (legacy)

```
GET /api/sale-note/lists
```

| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| input | string (query) | Búsqueda en series y number (LIKE) |

**Comportamiento:** `take(20)`, sin paginación ni `meta`.

---

## 3. API – Cotizaciones

### records (alineado con WEB) – RECOMENDADO

```
GET /api/quotations/records
```

**Controlador:** `App\Http\Controllers\Tenant\Api\QuotationController@records` (delega en `Tenant\QuotationController@records`)  
Acepta los mismos parámetros que `GET /quotations/records` (ver Parte A). Paginación con `data` + `meta` + `links`.

### list (legacy)

```
GET /api/quotations/list
```

| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| input | string (query) | Búsqueda en id (LIKE) |

**Comportamiento:** `take(20)`, sin paginación.

---

## 4. API – Guías de remisión

```
GET /api/dispatches/records
```

**Controlador:** `App\Http\Controllers\Tenant\Api\DispatchController@records` (delega en `Tenant\DispatchController@records`)  
**Parámetros:** los mismos que la vista WEB (`customer_id`, `series`, `number`, `d_start`, `d_end`, `page`). Tipo de documento fijo `09` en la consulta WEB.  
Paginación con `data` + `meta` + `links`.

---

## 5. API – Productos (Sellnow)

```
GET /api/sellnow/items
```

Sin parámetros ni paginación. Devuelve todos los items del almacén del usuario.

---

## 6. API – Búsqueda de clientes

```
GET /api/document/search-customers
```

| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| input | string (query) | Nombre o número documento |
| document_type_id | string (query) | Opcional |
| operation_type_id | string (query) | Opcional |

---

## 7. API – Consignados

```
GET /api/consigneds/records
```

| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| page | int (query) | Página |
| per_page | int (query) | Registros por página |

**Advertencia:** Implementación actual usa `Consigned::all()->paginate()`, lo que puede fallar. Usar `Consigned::query()->paginate()`.

---

# Mapeo Vista → Endpoint → Parámetros

## Resumen de filtros reales por vista

| Vista | Endpoint WEB | Filtros clave |
|-------|--------------|---------------|
| Documentos | `/documents/records` | document_type_id, series, number, d_start, d_end, customer_id, item_id, category_id, state_type_id, purchase_order, observations, guides, plate_numbers, pending_payment |
| Notas venta | `/sale-notes/records` | column, value, series, number, total_canceled, purchase_order, observations, license_plate |
| Cotizaciones | `/quotations/records` | column, value, form (period, date_start, date_end, state_type_id) |
| Guías | `/dispatches/records` | customer_id, series, number, d_start, d_end |

---

## Resumen de paginación

| Endpoint | Paginación | Estructura respuesta |
|----------|------------|----------------------|
| documents/records (WEB) | Sí (10/pág) | data + meta |
| sale-notes/records (WEB) | Sí (10/pág) | data + meta |
| quotations/records (WEB) | Sí (20/pág) | data + meta |
| dispatches/records (WEB) | Sí (20/pág) | data + meta |
| api/documents/lists | No | Array directo |
| api/sale-note/lists | No | Array directo |
| api/quotations/list | No | Array directo |
| api/dispatches/records | Sí | data + meta |

---

## Formato de fechas

| Contexto | Formato |
|----------|---------|
| d_start, d_end, date_of_issue | `YYYY-MM-DD` |
| month (cotizaciones) | `YYYY-MM` |
| value-format (Element UI) | `yyyy-MM-dd` |

---

# Alineación Frontend Vue externo con Backend

## Endpoints API alineados (disponibles)

| Módulo | Endpoint recomendado | Estado |
|--------|----------------------|--------|
| Documentos | `GET /api/documents/records` | ✅ Alineado (módulo MobileApp) |
| Notas de venta | `GET /api/sale-note/records` | ✅ Alineado (módulo MobileApp) |
| Cotizaciones | `GET /api/quotations/records` | ✅ Alineado (módulo MobileApp) |
| Guías | `GET /api/dispatches/records` | ✅ Alineado (módulo MobileApp) |

---

# Guía de implementación para el frontend externo (Vue)

## 1. Parámetros mínimos en la primera carga

**Es obligatorio enviar `page=1`** en la primera petición. Si no se envía, la API usa 1 por defecto, pero es recomendable enviarlo explícitamente.

```javascript
// Primera carga - sin filtros
GET /api/documents/records?page=1
GET /api/sale-note/records?page=1
GET /api/quotations/records?page=1
GET /api/dispatches/records?page=1
```

**Importante:** Los filtros son opcionales. Sin filtros, la API devuelve todos los registros (paginados).

## 2. Estructura de respuesta

La API devuelve:

```json
{
  "data": [ /* array de registros */ ],
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "path": "https://tu-dominio.com/api/documents/records",
    "per_page": 10,
    "to": 10,
    "total": 50
  },
  "links": {
    "first": "https://...",
    "last": "https://...",
    "prev": null,
    "next": "https://..."
  }
}
```

- **`data`**: Array de registros de la página actual.
- **`meta`**: Usar para la paginación (no depender de `links` si hay diferencias de dominio).
  - `meta.current_page`: Página actual
  - `meta.last_page`: Total de páginas
  - `meta.total`: Total de registros
  - `meta.per_page`: Registros por página

## 3. Paginación en el frontend

**Usar `meta` para construir la paginación**, no `links` (los links pueden apuntar al dominio del backend si hay CORS o subdominios distintos).

```javascript
// Ejemplo Vue/axios
const response = await axios.get('/api/documents/records', {
  params: {
    page: 1,  // obligatorio en cada petición
    // filtros opcionales
  }
});

const { data, meta } = response.data;

// data = registros
// meta.current_page, meta.last_page, meta.total, meta.per_page
```

Para cambiar de página:

```javascript
await axios.get('/api/documents/records', {
  params: { page: 2 }
});
```

## 4. Filtros por endpoint

### Documentos (`/api/documents/records`)
| Parámetro | Tipo | Obligatorio | Ejemplo |
|-----------|------|-------------|---------|
| page | int | Sí | 1 |
| document_type_id | string | No | "01", "03", "07", "08" |
| series | string | No | "F001" |
| number | string | No | "123" |
| d_start | string | No | "2025-01-01" |
| d_end | string | No | "2025-01-31" |
| customer_id | int | No | 5 |
| state_type_id | string | No | "05" |
| ... | ... | ... | Ver Parte A |

### Notas de venta (`/api/sale-note/records`)
| Parámetro | Tipo | Obligatorio |
|-----------|------|-------------|
| page | int | Sí |
| column | string | No (si no se envía, devuelve todos) |
| value | string | No |
| series | string | No |
| number | string | No |
| total_canceled | 0\|1 | No |

### Cotizaciones (`/api/quotations/records`)
| Parámetro | Tipo | Obligatorio |
|-----------|------|-------------|
| page | int | Sí |
| column | string | No |
| value | string | No |
| form | string (JSON) | No. Ej: `{"period":"month","month":"2025-01","date_start":"2025-01-01","date_end":"2025-01-31","state_type_id":null}` |

### Guías (`/api/dispatches/records`)
| Parámetro | Tipo | Obligatorio |
|-----------|------|-------------|
| page | int | Sí |
| customer_id | int | No |
| series | string | No |
| number | string | No |
| d_start | string | No |
| d_end | string | No |

## 5. Ejemplo completo (Vue/Composition API)

```javascript
const state = reactive({
  records: [],
  pagination: {
    current_page: 1,
    last_page: 1,
    total: 0,
    per_page: 10
  },
  filters: {}
});

async function loadRecords() {
  const params = {
    page: state.pagination.current_page,
    ...state.filters
  };
  const { data } = await axios.get('/api/documents/records', { params });
  
  state.records = data.data;
  state.pagination = {
    current_page: data.meta.current_page,
    last_page: data.meta.last_page,
    total: data.meta.total,
    per_page: data.meta.per_page
  };
}

function changePage(page) {
  state.pagination.current_page = page;
  loadRecords();
}
```

## Scroll infinito y caché

- Para scroll infinito: usar endpoints con `data` + `meta` (current_page, last_page, total).
- Clave de caché: incluir todos los parámetros de filtro y `page`.
- Fechas: usar siempre `YYYY-MM-DD` para consistencia.

---

## Referencias de código

| Elemento | Ruta |
|----------|------|
| DocumentController records (API) | `app/Http/Controllers/Tenant/Api/DocumentController.php` |
| SaleNoteController records (API) | `app/Http/Controllers/Tenant/Api/SaleNoteController.php` |
| QuotationController records (API) | `app/Http/Controllers/Tenant/Api/QuotationController.php` |
| DispatchController records (API) | `app/Http/Controllers/Tenant/Api/DispatchController.php` |
| DocumentController getRecords (WEB) | `app/Http/Controllers/Tenant/DocumentController.php` (`getRecords`) |
| SaleNoteController getRecords (WEB) | `app/Http/Controllers/Tenant/SaleNoteController.php` ~línea 491 |
| QuotationController getRecords (WEB) | `app/Http/Controllers/Tenant/QuotationController.php` ~línea 108 |
| DispatchController getRecords (WEB) | `app/Http/Controllers/Tenant/DispatchController.php` ~línea 90 |
| DataTableDocuments | `resources/js/components/DataTableDocuments.vue` |
| DataTableSaleNote | `resources/js/components/DataTableSaleNote.vue` |
| DataTableQuotation | `resources/js/components/DataTableQuotation.vue` |
| DataTableDispatch | `resources/js/components/DataTableDispatch.vue` |
