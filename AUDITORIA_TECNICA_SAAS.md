# Informe de auditoría técnica — Facturador Tukifac (Laravel 9, multi-tenant `hyn/multi-tenant`, Vue)

**Alcance y método:** Se revisó la arquitectura global (`composer.json`, `docker-compose.yml`, `Kernel.php`, `AppServiceProvider`, rutas centrales/tenant), servicios de métricas centrales, observadores, controladores de descarga, dashboard, colas/supervisor, patrones de consulta (N+1, `DB::raw`, agregados), dependencias y riesgos de despliegue. El repositorio supera **~2000 archivos** PHP/Vue/Blade; un barrido literal línea a línea de “todo” no es operativo ni aporta más que un **enfoque de auditoría dirigida + búsqueda de patrones** (equivalente a lo que haría un arquitecto en discovery). Los hallazgos siguientes están **fundamentados en código concreto**; para CPU real en producción conviene complementar con **APM** (New Relic, Datadog, Elastic APM) y **slow query log** de MySQL/MariaDB.

**Stack detectado:** Laravel **^9**, `hyn/multi-tenant` **^5.8**, `nwidart/laravel-modules`, Vue en `resources/js`, **no hay uso de Livewire** en el proyecto (búsqueda sin coincidencias de `Livewire` / `wire:poll`). La sección Livewire del briefing original queda como **N/A** salvo que añadan el paquete más adelante.

---

## A. Problemas críticos

| # | Archivo (aprox.) | Problema | Impacto | Solución / ejemplo |
|---|------------------|----------|---------|-------------------|
| 1 | `app/Services/System/TenantCentralMetricsSyncService.php` **90–117**, **255–332**, **338–380** | Tras cada `syncDocument` / `syncSaleNote` con `withRefresh = true` se ejecuta `refreshAggregatesForClient`, que lanza **varias decenas de consultas** (múltiples `count()` sobre `client_central_documents`) y `computeSalesTotalCached` hace **`get()` de todos los comprobantes del ciclo** en PHP para sumar. Los observers llaman a esto en **created/updated/deleted** del documento. | **Alto consumo de CPU en PHP y MySQL**, contención en filas de `tenant_metrics_current` y tablas centrales, riesgo de **timeout de FPM** y cola de conexiones bajo picos de facturación. | Desacoplar: (1) `syncDocument(..., false)` desde observers y **un solo** `refreshAggregatesForClient` vía **job en cola** con debounce (ej. `Bus::batch` o `ShouldBeUnique` + `delay(5s)`). (2) Reemplazar suma en PHP por **SQL agregado** (`SUM(CASE … END)`) con una sola consulta. (3) En bulk, usar el patrón ya presente en backfill (`withRefresh = false` + refresh final). |
| 2 | `app/Observers/DocumentObserver.php` **19–30**, **39–63** | En `creating` se llama a `Company::active()` y `Functions::newNumber(...)`; en `created/updated/deleted` se sincroniza métricas centrales (vía servicio anterior). | Latencia extra en **cada emisión** y acoplamiento request↔métricas centrales. | Cachear contexto de empresa por request; delegar métricas a cola; revisar si `newNumber` puede optimizarse (bloqueo/transaction + índice único en `series+number+tipo`). |
| 3 | `modules/Dashboard/Helpers/DashboardData.php` **136–175** (y ramas similares), `modules/Dashboard/Traits/TotalsTrait.php` **~100–216** | Se cargan `SaleNote`/`Document` con `get()` y en bucle se accede a `$sale_note->payments` / `$document->payments` **sin `with('payments')`**. | **N+1 clásico**: 1 + N consultas por dashboard; con muchos comprobantes **satura MySQL y FPM**. | `SaleNote::query()->with('payments')->...` y equivalente para `Document`; idealmente **agregados en SQL** (`withSum`, subconsultas) en lugar de iterar en PHP. |
| 4 | `app/Http/Controllers/Tenant/DocumentController.php` **1384–1386** | Filtro por guías: `where('guides', 'like', DB::raw(...) . $guides . DB::raw(...))` — valor **sin binding** y uso incorrecto de `DB::raw` en `LIKE`. | **Riesgo de SQL injection** si `$guides` viene del cliente; además el SQL puede ser **inválido** o no usar índices. | Usar binding y JSON: `where('guides', 'like', '%"number":"' . str_replace(['%', '_'], ['\\%', '\\_'], $guides) . '%')` o, mejor, **columna generada / JSON_EXTRACT** + índice funcional según motor. Ejemplo seguro: `$records->where('guides->number', 'like', '%'.$escaped.'%');` si el esquema lo permite. |
| 5 | `app/Http/Controllers/Tenant/DownloadController.php` **18–37**, **84–118** | Construcción dinámica `$model = "App\\Models\\Tenant\\".ucfirst($model)` y resolución por `external_id` en rutas **públicas** (`routes/web.php` ~23–26). | **IDOR** si el UUID filtra; peor: enumeración de clases (`User`, etc.) si existiera fila con `external_id` conocido. **DoS** al regenerar PDF en caliente (`reloadPDF`). | Lista blanca de modelos permitidos; **tokens firmados de un solo uso** o autenticación; nunca concatenar nombre de clase desde input sin mapa fijo. |
| 6 | `app/CoreFacturalo/Services/Extras/ValidateCpe2.php` **144–160** | Cookie jar en `public_path('cookie.txt')` + **OCR Tesseract** en request. | **Condiciones de carrera** entre workers, posible **escritura en `public/`**, en Linux **sin ruta a `tesseract`** (fallo o binario inesperado). | Mover cookies a `storage_path('app/sunat_cookie.txt')`; ruta de tesseract vía `config()`; externalizar validación a **job** + rate limit. |

---

## B. Problemas altos

| # | Archivo | Problema | Impacto | Solución |
|---|---------|----------|---------|----------|
| 7 | `app/Http/Controllers/Tenant/DocumentController.php` (~**3741** líneas), `SearchItemController.php` (~**3442** líneas) | **Fat controllers**: lógica de negocio, consultas, reglas y respuestas mezcladas. | Imposible testear bien, duplicación, regresiones y **consultas impredecibles** por refactor. | Extraer **Application services** por caso de uso (`DocumentListingService`, `ItemSearchService`), Form Requests, políticas y repositorios delgados donde aporte. |
| 8 | `modules/Dashboard/Helpers/DashboardSalePurchase.php` (p. ej. **321–365**) | Bucles sobre `items` y relación `document` sin garantizar eager load. | N+1 en reportes de ítems. | `with(['items.document'])` o consultas agregadas. |
| 9 | `app/Models/Tenant/ItemMovement.php` (múltiples `DB::raw`, `getQueryToStock`) | Lógica de stock compleja; riesgo de llamadas repetidas desde POS/listados. | CPU y bloqueos si se invoca por ítem en bucle. | Cache por ítem/almacén (Redis), vistas materializadas o **una** consulta agregada por lote de ítems. |
| 10 | `config/queue.php` + `supervisor.conf` | Cola por defecto `database`, **2** workers, `--tries=3`. | La tabla `jobs` y **locks** pueden ser cuello de botella; pocos workers para carga alta. | Redis + `queue:work` con más procesos, `failed_jobs` monitorizado, timeouts `--max-time` / `retry_after` alineados con jobs PDF. |
| 11 | `routes/web.php` (tenant, sin `auth`) | Endpoints como `exchange_rate/ecommence/{date}` y descargas/impresión públicas. | **Abuso** (scraping SUNAT, generación de PDF) consume ancho de banda y CPU. | `throttle` dedicado, auth o token, cache HTTP del tipo de cambio. |
| 12 | `docker-compose.yml` | Servicios sin `depends_on`, sin **límites de recursos**, MariaDB **10.5.6** antigua, volúmenes bind-mount `./` en producción. | Contenedor “noisy neighbor”, I/O lento en Windows/XAMPP, **imagen PHP opaca** (`rash07/php-fpm`). | Healthchecks, límites `cpus`/`mem_limit`, imagen reproducible con Dockerfile propio, MariaDB LTS. |

---

## C. Problemas medios

| # | Tema | Detalle | Solución |
|---|------|---------|----------|
| 13 | **API `throttle:60,1`** (`app/Http/Kernel.php` **42–46**) | 60 req/min global para `api` puede ser bajo para apps móviles o alto para abusadores según ruta. | Throttle por ruta/usuario; Redis rate limiter. |
| 14 | **Sesiones** `file` por defecto (`config/session.php` **21**) | Multi-instancia FPM detrás de nginx → **sesiones inconsistentes** salvo sticky sessions. | `redis` + `SESSION_DRIVER=redis`. |
| 15 | **`TrustProxies`** (`app/Http/Middleware/TrustProxies.php`) | `$proxies` indefinido; detrás de `proxynet` suele necesitarse `'*'` o lista de IPs. | Configurar según reverse proxy para `HTTPS` y IP cliente correctos. |
| 16 | **Índices** | Existe migración útil `database/migrations/tenant/2025_12_12_184028_add_performance_indexes_to_documents_table.php` (compuestos por fecha/tipo/estado, etc.). | Verificar despliegue en **todos** los tenants; analizar `EXPLAIN` de listados reales. |
| 17 | **Agregados centrales** `client_central_documents` | Muchos `COUNT` con filtros distintos. | Índice compuesto sugerido (ajustar a EXPLAIN real): `INDEX idx_ccd_client_state_date (client_id, state_type_id, date_of_issue)`, `INDEX idx_ccd_client_pse (client_id, send_to_pse)`, y para `computeSalesTotalCached`: `(client_id, date_of_issue, state_type_id, document_type_id)` covering parcial. |
| 18 | **Pagos** | Tablas `document_payments`, `sale_note_payments`: asegurar `INDEX (document_id)` / `INDEX (sale_note_id)` si no existen (típico en N+1 y reportes). | Revisión de migraciones tenant + `SHOW INDEX`. |
| 19 | **Dependencias** | `fideloper/proxy` (legacy en L9), `fruitcake/laravel-cors`, `hyn/multi-tenant` (mantenimiento limitado vs ecosistema Stancl). | Plan de actualización / migración tenancy documentada. |
| 20 | **Blade `@php`** en layouts (`resources/views/tenant/layouts/partials/header.blade.php`, etc.) | Lógica de presentación densa; difícil de cachear fragmentos. | Mover a View Composers o datos explícitos desde controladores. |

---

## D. Problemas menores / de higiene

- Duplicado de provider de log viewer en `config/app.php` (aparece **dos veces** `LaravelLogViewerServiceProvider`) — revisar registro duplicado.
- `AuthenticateSession` comentado en `web` (`app/Http/Kernel.php` **35**) — valorar reactivar para invalidar sesión al cambiar password.
- Vue: uso extensivo de `setTimeout` (POS, DataTables) — revisar **debounce** y destrucción en `beforeUnmount` para evitar fugas menores.
- Comentarios “borrar después” en `DownloadController` — deuda técnica.

---

## 2. Base de datos (resumen)

- **Bien:** FKs en migración base de `documents` (`database/migrations/tenant/2018_06_17_000002_tenant_documents_table.php`); índices recientes en `documents`.
- **Revisar:** índices en tablas de **índice central** (`client_central_documents`, `client_central_sale_notes`, `tenant_metrics_current`) alineados con `WHERE client_id = ?` + filtros de estado/fecha usados en `refreshAggregatesForClient`.
- **Anti-patrón:** `select *` implícito en muchos Eloquent; en listados masivos usar `select([...])` + paginación estable.

**Ejemplos SQL (plantillas — validar nombres reales con `SHOW CREATE TABLE`):**

```sql
-- Consultas por cliente y rango de fechas en índice central
ALTER TABLE client_central_documents
  ADD INDEX idx_client_date_state (client_id, date_of_issue, state_type_id);

-- Si filtran mucho por tipo y estado
ALTER TABLE client_central_documents
  ADD INDEX idx_client_type_state (client_id, document_type_id, state_type_id);
```

---

## 3. Laravel / arquitectura limpia

- **Lógica en controladores y helpers gigantes** (`DashboardData`, `SearchItemController`) dificulta SOLID y pruebas.
- **Observers** acoplados a infraestructura central: mejor **eventos de dominio** + listeners en cola.
- **Validación:** muchos endpoints JSON validan parcialmente; reforzar **Form Requests** uniformes en módulos.

---

## 4. Livewire

**No aplica** (no hay paquete ni directivas en el código analizado).

---

## 5. Colas

- `supervisor.conf`: `queue:work --sleep=3 --tries=3`, `user=root` (no recomendado en producción), `stopwaitsecs=3600` (jobs muy largos bloquean graceful stop).
- Separar colas: `pdf`, `reports`, `metrics` con workers dedicados.

---

## 6. Seguridad (matriz resumida)

| Riesgo | Evidencia | Prioridad |
|--------|-----------|-----------|
| SQLi / LIKE mal formado | `DocumentController` guías | Crítica |
| IDOR / modelo dinámico | `DownloadController` + rutas públicas | Crítica |
| Mass assignment | Pocos `create($request->all())`; mitigado si `$fillable` estricto — seguir auditando otros módulos | Media |
| Logs | `logs` bajo `auth:admin` — **correcto** | OK |
| Cookies en `public` | `ValidateCpe2` | Alta |

---

## 7. Frontend (Vue)

- POS y tablas (`DataTableDocuments.vue`, etc.) concentran lógica y temporizadores; riesgo de **peticiones redundantes** si cada celda dispara fetch (revisar por vista).
- Sin evidencia de **polling agresivo** tipo `setInterval` continuo en el muestreo; predominan `setTimeout` puntuales.

---

## 8. Docker / producción

- **OpCache, `config:cache`, `route:cache`:** no visibles en compose (dependen de imagen); deben verificarse en la imagen `rash07/php-fpm:8.2`.
- **Redis** declarado pero uso principal debe confirmarse (cola, sesión, cache).
- **Nginx** montado desde host (`/root/proxy/...`) — no auditable en repo; revisar `fastcgi_buffers`, timeouts hacia FPM, `client_max_body_size`.

---

## 9. Hipótesis de “CPU alta real” en producción (priorizada)

1. **`refreshAggregatesForClient` + `computeSalesTotalCached`** tras cada cambio de documento/nota de venta.
2. **Dashboard** (`DashboardData` / `TotalsTrait`) con N+1 en `payments`.
3. **Listados masivos** en controladores enormes (`DocumentController`, `SearchItemController`) con `whereHas` anidados (ej. ~1371–1382) sin índice alineado en `document_items` / categorías.
4. **Jobs PDF** y regeneración síncrona en rutas de descarga.
5. **Validación SUNAT con OCR** si se expone a volumen.

---

## 10. Priorización de remediación (orden sugerido)

1. Métricas centrales: **debounce en cola** + agregación SQL.
2. Corregir **LIKE guías** y endurecer **descargas públicas**.
3. Eager loading / SQL en **dashboard**.
4. Infra: Redis para cola/sesión, más workers, límites Docker.
5. Refactor incremental de **controladores monolíticos** hacia servicios.

---

## 11. Objetivos del análisis original (checklist)

### 1. Performance y CPU

- N+1, eager loading faltante, foreach con consultas, Eloquent pesado, joins/subqueries, agregados ineficientes, loops, trabajo en request, observers/listeners/middleware/providers, jobs y exportaciones: cubierto en secciones A–C y §9.
- **Livewire / wire:poll:** no aplicable (§4).

### 2. Base de datos

- Índices, FKs, overfetching, paginación: §2 y tabla C ítems 16–18.

### 3. Laravel best practices

- §3 y problemas altos (fat controllers).

### 4. Livewire

- §4.

### 5. Colas

- §5 y B #10.

### 6. Seguridad

- §6 y críticos #4–6.

### 7. Frontend

- §7.

### 8. Docker y producción

- §8 y B #12.

### 9. Problemas reales (CPU / FPM / MySQL / Docker)

- §9.

### 10. Reporte final A–D

- Secciones **A**, **B**, **C**, **D** al inicio de este documento.

---

*Documento generado a partir de la auditoría técnica del repositorio. Fecha de referencia del análisis: mayo 2026.*
