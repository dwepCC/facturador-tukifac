# Diseño e Implementación: Métricas en Tiempo Real para Lista de Clientes (Opción B – Event‑Driven)

Este documento define un diseño exhaustivo para reemplazar el cálculo en vivo (N+1) que hoy consulta cada base de datos tenant al cargar la lista de clientes, por un **modelo de lectura centralizado** (proyección) actualizado **en tiempo real por eventos** generados desde cada tenant.

El objetivo es **no afectar la vista actual** (la actual se mantiene), y construir una **nueva vista** y un **nuevo endpoint** que lean 100% desde la base central (system) con datos en tiempo real.

---

## 1) Contexto del Sistema (Estado Actual)

### Multi‑tenant (Hyn Tenancy)
- Base **system**: contiene `clients`, `hostnames`, `websites`, planes, pagos, etc.
- Base **tenant** (una por cliente): contiene `documents`, `sale_notes`, `users`, `establishments`, `companies`, etc.
- La aplicación cambia la conexión tenant dinámicamente usando `Environment::tenant(...)`.

### Vista actual de lista de clientes (por qué es costosa)
El endpoint [ClientController@records](file:///c:/xampp/htdocs/facturador-tukifac/app/Http/Controllers/System/ClientController.php#L131-L325) hace:
- `Client::latest()->get()` (trae todos los clientes).
- Por cada cliente:
  - Cambia al tenant DB (`$tenancy->tenant($row->hostname->website)`).
  - Ejecuta múltiples `count()` y cálculos sobre tablas tenant (`documents`, `sale_notes`, `users`, `companies`, `configurations`, etc.).

Esto es el problema **N+1 multiplicado**: N clientes × M consultas por cliente.

---

## 2) Objetivo del Nuevo Mecanismo (Tiempo Real con Eventos)

### Objetivo funcional
Mostrar en el dashboard/lista de clientes los mismos campos que hoy se muestran (o equivalentes), sin conectarse a las bases tenant al renderizar la tabla.

Campos que hoy consume el frontend (extraídos de [ClientCollection.php](file:///c:/xampp/htdocs/facturador-tukifac/app/Http/Resources/System/ClientCollection.php#L18-L84) y [index.vue](file:///c:/xampp/htdocs/facturador-tukifac/resources/js/views/system/clients/index.vue)):
- Identidad/estado del cliente (system): `id`, `hostname`, `name`, `email`, `number`, `plan`, `locked_*`, `start_billing_cycle`, etc.
- Métricas tenant que hoy se calculan en vivo:
  - `count_doc` (total docs o por rango)
  - `count_sales_notes` (total NV o por rango)
  - `current_count_doc_month` (docs del mes calendario o por rango)
  - `count_doc_month` (docs en ciclo de facturación o por rango)
  - `count_sales_notes_month` (NV en ciclo de facturación o por rango)
  - `count_doc_pse` (docs enviados a PSE)
  - Notificaciones por estado: `document_regularize_shipping`, `document_not_sent`, `document_to_be_canceled`, `document_rejected`, `document_observed`
  - `count_user` (usuarios tenant)
  - `quantity_establishments` (sucursales tenant)
  - `soap_type` (entorno desde `companies.soap_type_id`)
  - `monthly_sales_total` (ventas del ciclo, fórmula actual)
  - `queries_to_apiperu` (consultas API Perú por mes, hoy desde system)

### Objetivo no funcional
- Respuesta en milisegundos/segundos con paginación (sin timeouts).
- Actualización en “tiempo real” (segundos) al emitir/actualizar documentos.
- Tolerancia a fallas (idempotencia; reintentos; backfill).

---

## 3) Enfoque Recomendado: Proyección Central (Read Model) + Eventos

Para tener datos exactos y consultables por rango sin tocar tenant DB, la opción más robusta no es solo “incrementar contadores”, sino mantener un **índice central de entidades tenant**:

1) **Índice de documentos** (en system) que replica los campos mínimos de `tenant.documents` necesarios para contar, filtrar y calcular ventas/notificaciones.
2) **Índice de notas de venta** (en system) que replica lo mínimo de `tenant.sale_notes`.
3) **Contadores directos** (usuarios, sucursales) mantenidos por eventos.
4) **Un job/backfill** inicial para poblar el índice con data histórica.

Esto se conoce como:
- CQRS (separar lectura de escritura).
- Proyección/event‑driven read model.

---

## 4) Modelo de Datos Propuesto (Base Central / System)

### 4.1 Tabla: `system_client_documents_index`
Una fila por documento tenant.

Campos mínimos sugeridos:
- `id` (pk)
- `client_id` (fk a `system.clients`)
- `tenant_document_id` (int) o `external_id` (uuid) del documento tenant
- `date_of_issue` (date)
- `document_type_id` (char(2))
- `state_type_id` (char(2))
- `regularize_shipping` (bool)
- `send_to_pse` (bool)
- `currency_type_id` (char(3))
- `exchange_rate_sale` (decimal)
- `total` (decimal)
- `created_at`, `updated_at`

Índices recomendados:
- unique: `(client_id, external_id)` o `(client_id, tenant_document_id)`
- index: `(client_id, date_of_issue)`
- index: `(client_id, state_type_id, date_of_issue)`
- index: `(client_id, send_to_pse)`

Motivación:
- Permite reproducir con exactitud consultas del tipo:
  - “cuántos documentos en un rango”
  - “cuántos por estado en un rango”
  - “cuántos enviados a PSE”
  - “ventas netas” (sumatorias)

### 4.2 Tabla: `system_client_sale_notes_index`
Una fila por nota de venta tenant.

Campos mínimos sugeridos:
- `id` (pk)
- `client_id`
- `tenant_sale_note_id` (int) o `external_id` equivalente si existe
- `date_of_issue`
- `state_type_id`
- `changed` (bool)
- `currency_type_id`
- `exchange_rate_sale`
- `total`
- `created_at`, `updated_at`

Índices recomendados:
- unique: `(client_id, tenant_sale_note_id)`
- index: `(client_id, date_of_issue)`
- index: `(client_id, state_type_id, date_of_issue)`

### 4.3 Tabla: `system_client_metrics_live` (opcional pero útil)
Una fila por cliente con contadores de lectura rápida (no por rango).

Campos sugeridos:
- `client_id` (pk/fk)
- `users_count`
- `establishments_count`
- `soap_type_id` (cacheado desde tenant.companies)
- `updated_at`

Nota:
- Estos contadores también pueden derivarse de índices si decides indexar usuarios/establishments, pero no es necesario.

### 4.4 Tabla: `system_client_users_index` (historial para filtros por rango)
Una fila por usuario tenant para poder responder preguntas del tipo:
- “¿cuántos usuarios se crearon entre fecha A y B?”
- “¿cuántos usuarios estaban activos en una fecha X?”
- “¿cuántos usuarios estuvieron activos en un rango A–B?”

Campos mínimos sugeridos:
- `id` (pk)
- `client_id` (fk a `system.clients`)
- `tenant_user_id` (int)
- `email` (string, opcional)
- `created_at_tenant` (datetime) fecha real de creación en tenant
- `deleted_at_tenant` (datetime, nullable) si el tenant hace soft delete; si no, se guarda la fecha del evento delete
- `is_active` (bool) derivado: `deleted_at_tenant is null`
- `created_at`, `updated_at` (system)

Índices recomendados:
- unique: `(client_id, tenant_user_id)`
- index: `(client_id, created_at_tenant)`
- index: `(client_id, deleted_at_tenant)`

### 4.5 Tabla: `system_client_establishments_index` (historial para filtros por rango)
Una fila por sucursal (establishment) tenant con `created_at_tenant` y `deleted_at_tenant` para responder consultas por rango (análogas a usuarios).

Campos mínimos sugeridos:
- `id` (pk)
- `client_id`
- `tenant_establishment_id` (int)
- `created_at_tenant` (datetime)
- `deleted_at_tenant` (datetime, nullable)
- `is_active` (bool)
- `created_at`, `updated_at`

Índices recomendados:
- unique: `(client_id, tenant_establishment_id)`
- index: `(client_id, created_at_tenant)`
- index: `(client_id, deleted_at_tenant)`

### 4.6 Tabla: `system_client_daily_metrics` (métricas agregadas por día, opcional pero altamente recomendable)
Si vas a filtrar constantemente por rangos amplios (meses/años) y por muchos clientes, aunque tengas índices de documentos/usuarios, las sumas y conteos repetidos pueden ser costosos.

Esta tabla es una “materialización” diaria que se alimenta por eventos y permite consultas muy rápidas.

Campos sugeridos (por `client_id` + `date`):
- `client_id` (fk)
- `date` (date)
- `documents_count` (int)
- `sale_notes_count` (int)
- `documents_pse_count` (int)
- `documents_not_sent_count` (int)
- `documents_regularize_count` (int)
- `documents_to_be_canceled_count` (int)
- `documents_rejected_count` (int)
- `documents_observed_count` (int)
- `sales_total_net` (decimal) ventas netas del día según la fórmula actual
- `users_created_count` (int)
- `users_deleted_count` (int)
- `establishments_created_count` (int)
- `establishments_deleted_count` (int)
- `created_at`, `updated_at`

Índices recomendados:
- unique: `(client_id, date)`
- index: `(date)`

Regla:
- Esta tabla no reemplaza los índices; los complementa para reportes rápidos.

### 4.7 Tabla: `system_metric_events` (log de eventos + idempotencia)
Registro append‑only de eventos provenientes del tenant (historial) y control de idempotencia.

Esto responde directamente a la necesidad: “guardar un historial vinculado al tenant con fecha del evento y el evento en sí para filtrar por rango”.

Campos sugeridos:
- `id` (pk)
- `event_uuid` (uuid, unique)
- `client_id`
- `event_name` (string)
- `entity_type` (document, sale_note, user, establishment, company)
- `entity_key` (external_id / tenant_id)
- `occurred_at` (datetime)
- `payload` (json)
- `processed_at` (datetime)

---

## 5) Catálogo de Eventos (Acciones precisas por Tenant)

Los eventos que necesitas para mantener la proyección exacta de lo que muestra la lista:

### 5.1 Documentos (tenant.documents)

**Evento: `tenant.document.created`**
Se dispara cuando se crea un registro en `documents` (factura, boleta, nota crédito, nota débito, etc.).
Payload mínimo:
- `event_uuid`
- `client_id` (o `hostname_id` para resolver)
- `external_id` o `tenant_document_id`
- `date_of_issue`
- `document_type_id`
- `state_type_id`
- `regularize_shipping`
- `send_to_pse`
- `currency_type_id`
- `exchange_rate_sale`
- `total`

**Evento: `tenant.document.updated`**
Se dispara cuando cambia alguno de los campos que impacta métricas:
- `state_type_id`
- `regularize_shipping`
- `send_to_pse`
- `total`, `currency_type_id`, `exchange_rate_sale` (si permites edición)
- `date_of_issue` (si se edita; debería ser raro)

Payload mínimo:
- `event_uuid`
- `entity_key`
- `before` y `after` para campos relevantes (estado, flags, total/moneda)

### 5.2 Notas de venta (tenant.sale_notes)

**Evento: `tenant.sale_note.created`**
Campos:
- `tenant_sale_note_id`
- `date_of_issue`
- `state_type_id`
- `changed`
- `currency_type_id`
- `exchange_rate_sale`
- `total`

**Evento: `tenant.sale_note.updated`**
Cuando cambia:
- `state_type_id` (aceptada/anulada)
- `changed` (si la NV se transforma en documento)
- importes

### 5.3 Usuarios (tenant.users)
**Evento: `tenant.user.created`**
**Evento: `tenant.user.deleted`**
Campos:
- `tenant_user_id`

Propósito:
- Mantener `users_count` en `system_client_metrics_live`.

### 5.4 Sucursales (tenant.establishments)
**Evento: `tenant.establishment.created`**
**Evento: `tenant.establishment.deleted`**

Propósito:
- Mantener `establishments_count` en `system_client_metrics_live`.

### 5.5 Entorno (tenant.companies.soap_type_id)
**Evento: `tenant.company.updated`**
Cuando cambia `soap_type_id` (demo/producción/interno).

Propósito:
- Evitar consultar tenant `companies` al renderizar la lista.

---

## 6) Fórmulas Exactas (Cómo reproducir las métricas actuales)

### 6.1 Conteos por rango
Hoy el endpoint usa consultas como:
- `documents.whereBetween(date_of_issue, [start, end]).count()`
- `sale_notes.whereBetween(date_of_issue, [start, end]).count()`

Con el índice central:
- `system_client_documents_index` permite el mismo `whereBetween(date_of_issue)` por `client_id`.
- Igual para `system_client_sale_notes_index`.

### 6.1.1 Usuarios por rango (definir semántica)
En la lista actual, `count_user` es el total actual (`users.count()` en tenant). Cuando pides “cuántos usuarios tiene de tal fecha a tal fecha”, hay 3 interpretaciones comunes. El diseño debe soportar las tres (para que el filtro sea correcto):

1) **Usuarios creados en el rango** (actividad):
- Query (system): `system_client_users_index.whereBetween(created_at_tenant, [A, B]).count()`

2) **Usuarios activos al final del rango** (stock en una fecha):
- “activos al día B” = usuarios con `created_at_tenant <= B` y (`deleted_at_tenant is null` o `deleted_at_tenant > B`)
- Query: `where(created_at_tenant <= B).where(deleted_at_tenant is null OR deleted_at_tenant > B).count()`

3) **Usuarios activos en cualquier momento del rango A–B** (solapamiento):
- “existieron en algún momento dentro del rango”
- Query: `where(created_at_tenant <= B).where(deleted_at_tenant is null OR deleted_at_tenant >= A).count()`

Recomendación:
- En UI, nombrar explícitamente el filtro: “Usuarios creados en rango” vs “Usuarios activos al cierre” para evitar confusión.

### 6.1.2 Sucursales por rango
Análogo a usuarios pero sobre `system_client_establishments_index`.

### 6.2 Notificaciones por estado
Hoy se calcula (con filtros opcionales por fechas) en [getQuantityPendingDocuments](file:///c:/xampp/htdocs/facturador-tukifac/app/Http/Controllers/System/ClientController.php#L345-L392) sobre tenant:
- `regularize_shipping`: `state_type_id='01' AND regularize_shipping=1`
- `not_sent`: `state_type_id IN ('01','03') AND date_of_issue <= today`
- `to_be_canceled`: `state_type_id='13'`
- `rejected`: `state_type_id='09'`
- `observed`: `state_type_id='07'`

Con el índice central, replicas exactamente:
- Filtras por `client_id`
- Aplicas `whereBetween(date_of_issue, ...)` si hay rango
- Aplicas reglas por `state_type_id`/`regularize_shipping`/`date_of_issue`

### 6.3 `count_doc_pse`
Hoy:
- `documents.where(send_to_pse=true).count()`

En índice:
- `where send_to_pse = 1`

### 6.4 Ventas mensuales del ciclo (exactitud)
Hoy se calcula con `ClientHelper->getSalesTotal(...)` y deriva de:
- `DashboardData->document_totals_globals(start, end)` y opcionalmente `sale_note_totals_global(start, end)` ([DashboardData.php](file:///c:/xampp/htdocs/facturador-tukifac/modules/Dashboard/Helpers/DashboardData.php#L385-L453))

Resumen de la regla actual (ventas netas CPE):
- Considera documentos con `state_type_id IN ('01','03','05','07','13')`.
- Suma documentos de tipo `01`, `03`, `08`.
- Resta las notas de crédito `07` (por su total).
- Convierte USD a PEN usando `exchange_rate_sale`.

Para reproducir esto en system:
- Consulta `system_client_documents_index` en el rango:
  - suma `total * exchange_rate_sale` para USD y `total` para PEN, solo para tipos `01/03/08` y estados permitidos
  - resta con la misma conversión para tipo `07` en estados permitidos
- Para NV (si el plan incluye): suma `system_client_sale_notes_index` donde `changed=false` y estado aceptado (la regla exacta del scope `whereStateTypeAccepted()` en tenant)

Recomendación para exactitud y flexibilidad:
- Guardar en el índice central los mismos campos de moneda/tipo/estado y aplicar la fórmula en query (server‑side) para obtener el mismo número.

---

## 7) Implementación Técnica (Monolito Actual)

### 7.1 Dónde disparar eventos en el Tenant
En este repositorio, la creación de documentos se produce vía `Facturalo->save()` que hace `Document::create(...)` ([Facturalo.php](file:///c:/xampp/htdocs/facturador-tukifac/app/CoreFacturalo/Facturalo.php#L131-L163)).

La forma más limpia es usar **Observers de Eloquent** sobre modelos tenant:
- `App\Models\Tenant\Document`
- `App\Models\Tenant\SaleNote`
- `App\Models\Tenant\User`
- `App\Models\Tenant\Establishment`
- `App\Models\Tenant\Company`

Ventaja:
- No depende de “desde qué controller” se creó el registro.
- Cubre API, web, procesos, etc.

Regla crítica:
- Despachar la sincronización **después de commit** (afterCommit) para evitar que el sistema central vea entidades que luego se hacen rollback.

### 7.2 Cómo resolver `client_id` desde Tenant
Ya existe un patrón en `LockedEmissionTrait` para mapear tenant → client usando `hostname_id` ([LockedEmissionTrait.php](file:///c:/xampp/htdocs/facturador-tukifac/app/Traits/LockedEmissionTrait.php#L29-L61)).

En tenant:
- obtener hostname actual con `app(Environment::class)->hostname()`
- buscar `Client` por `hostname_id`
- actualizar system usando `Client` (usa conexión system via `UsesSystemConnection`)

### 7.3 Procesamiento de eventos: Listener + Job (recomendado)
Para robustez, no actualices system en el mismo request del usuario; despacha un **Job**:
- Listener: captura el evento de dominio (created/updated) y encola `SyncTenantDocumentToSystemJob`.
- Job: se ejecuta en queue y hace:
  1. Idempotencia (`system_metric_events` con `event_uuid`)
  2. Upsert en índice central
  3. Actualización de contadores live (si aplica)

Esto reduce latencia del usuario y permite reintentos.

### 7.4 Idempotencia (obligatoria)
Motivo: los jobs se reintentan, los requests se pueden duplicar, y un “incremento” sin guardas genera números incorrectos.

Reglas:
- Cada evento debe tener `event_uuid` único.
- En system, `system_metric_events.event_uuid` con unique constraint.
- El job:
  - intenta insertar el event_uuid
  - si ya existe, termina (evento duplicado)
  - si no existe, aplica actualización y marca processed_at

### 7.5 Historial de eventos para consultas por rango (lo que pediste)
El log `system_metric_events` debe quedar como **historial**, no solo como “tabla técnica”.

Buenas prácticas:
- Guardar `occurred_at` con timezone consistente (UTC recomendado).
- Guardar `payload` con:
  - identificador tenant (`hostname_id` o `client_id`)
  - entidad (`external_id`/`tenant_id`)
  - delta (`before/after`) si aplica
- Mantener retención (por ejemplo 6–12 meses) si el volumen se vuelve grande, y materializar a `system_client_daily_metrics` para reportes históricos.

---

## 8) Nueva Vista (Sin afectar la actual)

### 8.1 Estrategia de convivencia
Mantener:
- Vista actual: `system.clients.index` y endpoint `GET /clients/records` (legacy).

Agregar:
- Nueva vista: `system.clients.index_realtime` (o ruta `/clients/realtime`)
- Nuevo endpoint: `GET /clients/records-realtime`
  - Consulta exclusivamente system DB:
    - `clients` (datos base)
    - `system_client_documents_index`
    - `system_client_sale_notes_index`
    - `system_client_metrics_live`
    - `track_api_peru_services` (agregado por mes; sin N+1)

Esto permite probar y migrar sin riesgo.

### 8.2 Paginación y filtros (server‑side)
El nuevo endpoint debe aceptar:
- `page`, `per_page`
- `search` (hostname/nombre/ruc/correo)
- filtros `entorno`, `plan`, `bloqueo`
- rango documentos: `documents_date_start`, `documents_date_end`

Extensión recomendada (para lo que mencionas):
- rango de usuarios: `users_date_start`, `users_date_end` + `users_range_mode` (created | active_end | active_overlap)
- rango de sucursales: `establishments_date_start`, `establishments_date_end` + `establishments_range_mode`

Implementación recomendada:
- construir query base en `clients`
- aplicar filtros
- para cada cliente en la página, resolver métricas con subqueries agregadas o joins pre‑agregados por `client_id`

Nota:
- Evitar N+1 también en system. Para `TrackApiPeruServices`, usar `groupBy(client_id)` en el rango mensual y mapear por id.

---

## 9) Backfill Inicial (Poblar índice con históricos)

Antes de “tiempo real”, necesitas poblar:
- documentos históricos de cada tenant
- sale notes históricas
- contadores de usuarios/sucursales

Propuesta:
- comando `php artisan tenants:backfill-metrics`
  - itera `Client::all()`
  - entra a cada tenant
  - paginación por lotes sobre `documents` y `sale_notes`
  - upsert en tablas index system

Esto se ejecuta una vez (o por cliente nuevo).

---

## 10) Observabilidad, Errores y Recuperación

### Logging
Registrar en system:
- eventos fallidos con excepción y payload
- último `processed_at` por cliente

### Métrica de salud
Exponer en UI/endpoint:
- `metrics_last_synced_at`
- `metrics_lag_seconds` (now - last_event_processed_at)

### Reconciliación
Aunque sea “tiempo real”, siempre incluir un comando de verificación:
- compara conteos índice vs conteos tenant para un cliente específico
- re‑sync de divergencias

---

## 11) Recomendación Final

Para lograr datos exactos como la vista actual y soportar filtros por rango, “tiempo real” no debe basarse solo en `increment()`. El enfoque correcto es:
- **Event‑Driven + Proyección central (índice mínimo de documents y sale_notes)**
- **Idempotencia + backfill + reconciliación**
- **Nueva vista/endpoint** que consulten únicamente system (sin tocar tenant)

Este diseño elimina el problema N+1, mantiene exactitud y habilita una migración segura sin romper la vista actual.

