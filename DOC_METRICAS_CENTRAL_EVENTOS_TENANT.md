# Métricas centrales de clientes: eventos del tenant y sincronización

Documentación técnica para operación y desarrollo: **qué debe ocurrir en el tenant** para que el panel de métricas centrales (`clientes-metricas-central`) refleje datos actuales en la base **system** (`client_central_documents`, `client_central_sale_notes`, `tenant_metrics_current`, `tenant_metric_history`).

---

## 0. Política operativa (alineada al despliegue)

| Fase | Cómo se actualiza la base central |
|------|-------------------------------------|
| **Despliegue inicial / actualización del sistema** | Se ejecutan **solo entonces** los comandos Artisan de esta documentación (§7) para volcar el estado existente de cada tenant al índice central y, si aplica, reconstruir historial de entidades. |
| **Operación normal (después del go-live)** | **No** se deben programar ni ejecutar de rutina los comandos `tenant-metrics:*` para mantener métricas. Toda la sincronización incremental corre por los **observers** del tenant al crear/actualizar/borrar `Document`, `SaleNote`, `User`, `Establishment` y al cambiar `Company::soap_type_id` (§2–§5). |
| **Jobs de la aplicación** | No hay en el código un *schedule* que llame a `tenant-metrics:central-backfill` ni a `tenant-metrics:rebuild-entity-history` (`app/Console/Kernel.php`). Los **jobs habituales** (envío a SUNAT, consulta de estado, etc.) no sustituyen a esos comandos: lo que mantienen el central al día es que **persistan cambios vía Eloquent** sobre los modelos observados (con tenancy resuelto en el worker). |

**Resumen:** los comandos Artisan de métricas centrales son para **carga inicial o recuperación puntual**; el día a día depende de los **eventos Eloquent en el tenant**.

---

## 1. Arquitectura resumida

1. En el **tenant** viven los modelos reales (`Document`, `SaleNote`, `User`, `Establishment`, `Company`).
2. Los **observers** de Laravel disparan, **tras confirmar la transacción** (`dispatchAfterCommit`), llamadas a `App\Services\System\TenantCentralMetricsSyncService`.
3. Ese servicio escribe en **system** una fila de índice por comprobante/NV y luego recalcula agregados en `tenant_metrics_current` (y opcionalmente append en `tenant_metric_history` para usuarios/sucursales).

No existe un webhook dedicado a SUNAT en este flujo: **lo que importa es que el modelo Eloquent del tenant se guarde** con los campos correctos (p. ej. `state_type_id` tras procesar la respuesta de SUNAT).

---

## 2. Dónde se registran los observers

Registro en `app/Providers/AppServiceProvider.php` (método `boot`):

| Modelo tenant | Observer | Rol respecto a métricas centrales |
|---------------|----------|-------------------------------------|
| `App\Models\Tenant\Document` | `App\Observers\DocumentObserver` | Índice de comprobantes + recálculo de agregados (incluye notificaciones `pending_*`). |
| `App\Models\Tenant\SaleNote` | `App\Observers\TenantCentralSaleNoteObserver` | Índice de notas de venta + recálculo de agregados. |
| `App\Models\Tenant\User` | `App\Observers\TenantCentralUserObserver` | Historial usuarios + conteos + agregados. |
| `App\Models\Tenant\Establishment` | `App\Observers\TenantCentralEstablishmentObserver` | Historial sucursales + conteos + agregados. |
| `App\Models\Tenant\Company` | `App\Observers\TenantCentralCompanyObserver` | Solo `soap_type_id` en métricas cuando cambia. |

---

## 3. Principios comunes

### 3.1 `dispatchAfterCommit`

Todas las sincronizaciones relevantes se programan **después del commit** de la transacción que guardó el modelo. Si la transacción hace rollback, **no** se sincroniza.

### 3.2 Resolución del cliente (`resolveClientId`)

`TenantCentralMetricsSyncService::resolveClientId()` obtiene el `client_id` de system a partir del **hostname** del contexto de tenancy (`Hyn\Tenancy\Environment`). En **consola o jobs**, si no hay hostname activo y no se fijó `contextClientId` internamente, el servicio puede **salir sin hacer nada** (`return` temprano).

El comando `tenant-metrics:central-backfill` fija explícitamente hostname + tenant y usa `contextClientId` durante el backfill.

### 3.3 `refreshAggregatesForClient`

Tras sincronizar un documento o una NV (cuando `withRefresh` es verdadero), se ejecuta `refreshAggregatesForClient($clientId)`, que **vuelve a contar** sobre `client_central_documents` / `client_central_sale_notes` y actualiza `tenant_metrics_current` (totales, `pending_*`, ventas cache del ciclo, `metrics_last_synced_at`, etc.). Ver implementación en `app/Services/System/TenantCentralMetricsSyncService.php`.

---

## 4. Tabla resumen: métrica / campo API ↔ eventos Eloquent en el tenant

La vista central consume principalmente el payload armado por `CentralClientMetricsQueryService::buildPayload`. La columna **“Eventos tenant”** indica qué hooks deben dispararse para que el dato se actualice **en vivo** (sin backfill).

| Área | Campo / uso en API central | Origen en system | Eventos Eloquent que deben cumplirse (tenant) |
|------|----------------------------|------------------|-----------------------------------------------|
| Comprobantes totales (sin filtro fecha) | `count_doc` → `total_documents` | `tenant_metrics_current` | `Document` **created**, **updated**, **deleted**, **forceDeleted** → `syncDocument` / `removeDocument` + `refreshAggregatesForClient`. |
| Comprobantes en rango fecha | `count_doc` (con filtro) | Conteo sobre `client_central_documents` | Mismo que arriba: cada cambio relevante en `Document` debe persistirse por Eloquent para que el índice central tenga `date_of_issue` y estados correctos. |
| Notificaciones: por enviar / borrador | `document_not_sent` → `pending_not_sent` | Derivado de índice central | `Document` **created** / **updated** cuando cambian `state_type_id`, `date_of_issue` (estados `01`,`03` y fecha ≤ hoy en la lógica de agregado). |
| Notificaciones: rectificación envío | `document_regularize_shipping` → `pending_regularize_shipping` | Índice + agregado | `Document` **updated** (típicamente) cuando cambia `regularize_shipping` y/o `state_type_id` según reglas de negocio. |
| Notificaciones: por anular | `document_to_be_canceled` → `pending_to_be_canceled` | Índice + agregado | `Document` **updated** cuando `state_type_id` pasa a situación anulación (`13` en la lógica central). |
| Otras notif.: rechazados | `document_rejected` → `pending_rejected` | Índice + agregado | `Document` **updated** cuando el flujo SUNAT/validación deja `state_type_id` en `09`. |
| Otras notif.: observados | `document_observed` → `pending_observed` | Índice + agregado | `Document` **updated** con `state_type_id` `07`. |
| Docs mes calendario | `current_count_doc_month` | Agregado | Cualquier **created/updated/deleted** de `Document` que afecte `date_of_issue` en el mes actual. |
| Envío PSE | `count_doc_pse` → `total_documents_pse` | Agregado | `Document` **updated** cuando cambia `send_to_pse` (y existe en índice). |
| Notas de venta totales | `count_sales_notes` | `tenant_metrics_current` | `SaleNote` **created**, **updated**, **deleted** → `syncSaleNote` / `removeSaleNote`. |
| Ventas ciclo (aprox.) | `monthly_sales_total` | `monthly_sales_total_cached` en agregado | Cambios en `Document` y/o `SaleNote` que alteren totales en ciclo; siempre tras `refreshAggregatesForClient`. |
| Usuarios | `count_user` → `total_users` | `tenant_metrics_current` | `User` **created**, **deleted** → `refreshUserAndEstablishmentCounts`. **No** hay observer de `updated` para usuarios: un solo `update` sin crear/borrar no altera el conteo vía este camino. |
| Sucursales | `quantity_establishments` → `total_establishments` | `tenant_metrics_current` | `Establishment` **created**, **deleted** → mismo método de refresco. |
| Entorno SUNAT/OSE | `soap_type` → `soap_type_id` | `tenant_metrics_current` | `Company` **updated** con `wasChanged('soap_type_id')` → `syncCompanySoapType`. |
| Historial usuarios/sucursales (filtros por rango) | `mh_users_*`, `mh_est_*` | `tenant_metric_history` | `User` / `Establishment` **created** y **deleted** → `appendTenantMetricHistoryEvent` + refresco. |
| Consultas API Perú (panel) | `queries_to_apiperu` | Tabla **system** `track_api_peru_services` | **No** pasa por `TenantCentralMetricsSyncService` en los observers listados: depende de cómo la aplicación registre trazas en esa tabla al usar el servicio. |
| Última sincronización agregados | `metrics_last_synced_at` | `tenant_metrics_current` | Se actualiza en cada `refreshAggregatesForClient` exitoso (p. ej. tras documento/NV o tras refresco de usuarios/sucursales). |

---

## 5. Detalle por modelo

### 5.1 `Document` (`DocumentObserver`)

**Archivo:** `app/Observers/DocumentObserver.php`  
**Servicio:** `TenantCentralMetricsSyncService::syncDocument` / `removeDocument`

| Evento Eloquent | Acción central (tras commit) |
|-----------------|------------------------------|
| `created` | Upsert en `client_central_documents` + `refreshAggregatesForClient`. |
| `updated` | Mismo: refleja nuevo `state_type_id`, `regularize_shipping`, fechas, totales, PSE, etc. |
| `deleted` | Borra fila del índice por `tenant_document_id` + refresco agregados. |
| `forceDeleted` | Igual que `deleted`. |

**Nota:** El método `creating` del observer solo asigna número/filename; **no** sincroniza métricas. La primera sincronización al central ocurre en **`created`**.

**Ejemplo SUNAT:** cuando un job o controlador procesa el CDR y hace `$document->update([...])` o `save()` con `state_type_id = 09`, debe dispararse **`updated`** para que el rechazo suba en `pending_rejected`.

---

### 5.2 `SaleNote` (`TenantCentralSaleNoteObserver`)

**Archivo:** `app/Observers/TenantCentralSaleNoteObserver.php`

| Evento Eloquent | Acción central |
|-----------------|----------------|
| `created` | Upsert `client_central_sale_notes` + `refreshAggregatesForClient`. |
| `updated` | Igual. |
| `deleted` | Borra del índice + refresco. |

No está registrado `forceDeleted` en este observer; si el proyecto usa borrado forzado en `SaleNote`, conviene valorar añadirlo por paridad con `Document`.

---

### 5.3 `User` (`TenantCentralUserObserver`)

**Archivo:** `app/Observers/TenantCentralUserObserver.php`

| Evento Eloquent | Acción central |
|-----------------|----------------|
| `created` | `appendTenantMetricHistoryEvent(..., 'users', 'created', ...)` + `refreshUserAndEstablishmentCounts` (actualiza conteos y agregados). |
| `deleted` | `appendTenantMetricHistoryEvent(..., 'users', 'deleted', ...)` + mismo refresco. |

---

### 5.4 `Establishment` (`TenantCentralEstablishmentObserver`)

**Archivo:** `app/Observers/TenantCentralEstablishmentObserver.php`

| Evento Eloquent | Acción central |
|-----------------|----------------|
| `created` | `appendTenantMetricHistoryEvent(..., 'establishments', 'created', ...)` + `refreshUserAndEstablishmentCounts`. |
| `deleted` | Evento `deleted` + historial + refresco. |

---

### 5.5 `Company` (`TenantCentralCompanyObserver`)

**Archivo:** `app/Observers/TenantCentralCompanyObserver.php`

| Evento Eloquent | Acción central |
|-----------------|----------------|
| `updated` **y** `wasChanged('soap_type_id')` | `syncCompanySoapType` → actualiza `soap_type_id` en `tenant_metrics_current` (sin recorrer documentos). |

---

## 6. Criterios de los contadores “notificación” (referencia)

Los `pending_*` se calculan en `TenantCentralMetricsSyncService::refreshAggregatesForClient` sobre `client_central_documents` (misma idea que en consultas por rango en `CentralClientMetricsQueryService::countPendingDocumentsGrouped`):

- **Por enviar / borrador (`pending_not_sent`):** `state_type_id` ∈ `01`, `03` y `date_of_issue` ≤ hoy (fecha del servidor al refrescar).
- **Rectificación envío (`pending_regularize_shipping`):** `state_type_id` = `01` y `regularize_shipping` verdadero.
- **Por anular (`pending_to_be_canceled`):** `state_type_id` = `13`.
- **Rechazados (`pending_rejected`):** `state_type_id` = `09`.
- **Observados (`pending_observed`):** `state_type_id` = `07`.

Cualquier transición de estado en el tenant debe **persistirse en `documents`** vía Eloquent para que el observer ejecute `syncDocument` y el agregado vuelva a contarse bien.

---

## 7. Comandos Artisan (solo despliegue inicial o reparación)

Implementación verificada en el código:

| Comando | Clase | Uso previsto |
|---------|--------|----------------|
| `php artisan tenant-metrics:central-backfill` | `App\Console\Commands\BackfillTenantCentralMetricsCommand` | Todos los clientes con website: replica `Document` y `SaleNote` al índice central y recalcula agregados (`backfillClientFromTenant`). |
| `php artisan tenant-metrics:central-backfill --client_id=123` | Idem | Un solo cliente (id en base **system**). |
| `php artisan tenant-metrics:central-backfill --with-entity-history` | Idem | Tras documentos/NV, reconstruye en el mismo recorrido el historial de usuarios/sucursales (`rebuildUsersAndEstablishmentsHistoryFromTenant`). Equivalente práctico a encadenar backfill + reconstrucción de entidades. |
| `php artisan tenant-metrics:rebuild-entity-history` | `App\Console\Commands\RebuildTenantEntityHistoryCommand` | Todos los clientes: borra historial previo `users`/`establishments` en `tenant_metric_history` por cliente y vuelve a insertar eventos `created` desde el tenant; refresca contadores centrales. |
| `php artisan tenant-metrics:rebuild-entity-history --client_id=123` | Idem | Un solo cliente. |
| `php artisan tenant-metrics:rebuild-entity-history --dry-run` | Idem | Muestra conteos de usuarios/sucursales que se procesarían **sin escribir** en `tenant_metric_history`. |
| `php artisan tenant-metrics:rebuild-entity-history --continue-on-error` | Idem | Si un tenant falla, continúa con los demás. |

**Nota:** `--dry-run` existe en `rebuild-entity-history`, no como opción global de `central-backfill`.

**Cuándo usarlos (excepcional, no rutina):** activación del módulo central en clientes ya operativos, migración de versión, datos corregidos por SQL sin observers, o desfase comprobado entre tenant y panel.

**Verificación:** en `app/Console/Kernel.php` **no** hay tareas programadas (`$schedule`) que ejecuten `tenant-metrics:central-backfill` ni `tenant-metrics:rebuild-entity-history`; la sincronización continua no depende del scheduler.

Flujo de servicio principal del backfill: `TenantCentralMetricsSyncService::backfillClientFromTenant`.

---

## 8. Limitaciones y buenas prácticas

1. **Updates masivos por SQL** (`DB::update`, migraciones que tocan `documents` sin modelos) **no** disparan observers → el central no se entera hasta un **backfill puntual** (§7) o código que llame explícitamente a `syncDocument` / `refreshAggregatesForClient`.
2. **Workers / jobs** que actualicen comprobantes deben ejecutarse con **tenant y hostname resueltos** (mismo criterio que una petición HTTP del cliente); si no, `resolveClientId()` puede ser `null` y **no** se escribirá en central. Esto no sustituye a los comandos `tenant-metrics:*`: son procesos de negocio normales, no “jobs de resync” programados.
3. **`syncDocument($document, false)`** (sin refresco inmediato) se usa en bucles del backfill; al final se llama **una vez** `refreshAggregatesForClient` para no recalcular miles de veces.
4. Las **consultas API Perú** del listado central leen `track_api_peru_services` en system; su actualización depende del módulo que inserte esas filas, no del pipeline de `DocumentObserver`.

---

## 9. Referencias de código

- Servicio de sincronización: `app/Services/System/TenantCentralMetricsSyncService.php`
- Observer comprobantes: `app/Observers/DocumentObserver.php`
- Observers NV / usuario / sucursal / empresa: `app/Observers/TenantCentralSaleNoteObserver.php`, `TenantCentralUserObserver.php`, `TenantCentralEstablishmentObserver.php`, `TenantCentralCompanyObserver.php`
- Registro: `app/Providers/AppServiceProvider.php`
- Consulta panel (payload + conteos por rango): `app/Services/System/CentralClientMetricsQueryService.php`
- Comando backfill: `app/Console/Commands/BackfillTenantCentralMetricsCommand.php`
- Comando historial entidades: `app/Console/Commands/RebuildTenantEntityHistoryCommand.php`
- Scheduler (sin métricas centrales programadas): `app/Console/Kernel.php`

---

*Documento generado para el proyecto facturador-tukifac. Alinear con cambios futuros en observers o en el modelo de datos central.*
