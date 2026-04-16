# Implementación: métricas centralizadas y listado de clientes sin N+1 multitenant

Documento único que consolida **REQUERIMIENTO_CLIENTE_CENTRAL.md** y **DISENO_METRICAS_TIEMPO_REAL_CLIENTES.md**, analiza el sistema actual, evalúa factibilidad y define un plan de implementación **en paralelo** a lo existente (sin modificar `ClientController@records()` ni la vista actual hasta validación).

---

## 1. Resumen ejecutivo

| Aspecto | Conclusión |
|--------|------------|
| **Factibilidad** | Alta. El stack (Laravel, Hyn Tenancy, doble conexión system/tenant) lo soporta. El coste principal es diseño de datos, cobertura de hooks `afterCommit`, rendimiento del sync síncrono y backfill inicial. |
| **Riesgo de regresión** | Bajo si se respeta la convivencia: nuevas rutas, nuevo método, nuevas tablas en `system`, nueva vista Vue. |
| **Alineación de los dos documentos** | El requerimiento propone `tenant_metrics_current` + `tenant_metric_history`. El diseño técnico añade **índices de documentos/NV** (o rollups diarios) porque un historial solo de “eventos” no reproduce por sí solo conteos exactos por rango sobre millones de filas sin recomputar desde proyección. **Recomendación:** usar ambas capas: snapshot + índice mínimo + log de idempotencia. |
| **Tiempo real (decisión de producto)** | **Sincronización inmediata con la base central:** cada alta/cambio/baja relevante en tenant debe reflejarse en `system` en el **mismo ciclo de vida del request**, **después** de que la transacción del tenant haga `commit` (no en cola como camino principal). La lista nueva de clientes lee solo `system` y ve el dato al instante. Los **jobs** quedan relegados a **backfill**, reconciliación o importaciones masivas, no al flujo normal del usuario. |

---

## 2. Sistema actual (verificado en código)

### 2.1 Multitenancy

- Base **system**: `clients`, hostnames, planes, etc.
- Base **tenant** por cliente: `documents`, `sale_notes`, `users`, `establishments`, `companies`, `configurations`, etc.
- Conmutación de contexto vía `Hyn\Tenancy\Environment` (`tenant(...)`).

### 2.2 Endpoint actual costoso

**`ClientController@records`** (`app/Http/Controllers/System/ClientController.php`, aprox. líneas 164–318):

- Carga **todos** los clientes: `Client::latest()->get()` (sin paginación server-side en este método).
- Por cada cliente:
  - Entra al tenant del cliente.
  - Ejecuta múltiples consultas a `documents`, `sale_notes`, `users`, `companies`, `configurations`, `establishments`.
  - Calcula pendientes con **`getQuantityPendingDocuments`** (estados `01`, `03`, `07`, `09`, `13` y reglas de `regularize_shipping` / `date_of_issue`).
  - Si hay `start_billing_cycle`, usa **`ClientHelper::getSalesTotal`** con rango de ciclo (y opcionalmente NV según plan).

Esto es **N × M consultas tenant**: escalable solo hasta cierto número de clientes.

### 2.3 Contrato del frontend actual

**`ClientCollection`** (`app/Http/Resources/System/ClientCollection.php`) expone los campos que cualquier sustituto “optimizado” debe cubrir o documentar como diferencia:

- Identidad y bloqueos del `Client` system.
- Métricas tenant: `count_doc`, `count_sales_notes`, `current_count_doc_month`, `count_doc_month`, `count_sales_notes_month`, `count_doc_pse`, pendientes por tipo, `count_user`, `quantity_establishments`, `soap_type`, `monthly_sales_total`, etc.
- **`queries_to_apiperu`**: hoy se resuelve con `TrackApiPeruServices` en **system** por `client_id` (consulta **por fila** dentro del transform → riesgo de N+1 también en system; en el nuevo endpoint conviene precargar/agrupar por mes).

### 2.4 Observadores y extensión natural

- **`DocumentObserver`** está registrado en `AppServiceProvider` y hoy solo interviene en **`creating`** (numeración/filename); **`updated` / `deleted`** están vacíos → lugar idóneo para enganchar sincronización **sin duplicar** lógica de negocio. Para tiempo real: usar **`Model::withoutEvents` solo donde haga falta evitar bucles**, y sobre todo **`DB::afterCommit()`** / `static::dispatchAfterCommit()` de Laravel para que la escritura en **system** ocurra **después** del commit tenant (si el rollback, no se escribe nada en central).
- No hay observador equivalente citado para `SaleNote`, `User`, `Establishment` a nivel global → habrá que **registrar** observers (o `booted` del modelo) que invoquen el **mismo servicio central** de proyección.

### 2.5 Resolución tenant → client (system)

- **`LockedEmissionTrait::getClientByHostname($hostname_id)`** ya localiza el `Client` en system por `hostname_id` → patrón reutilizable para obtener `client_id` desde el contexto tenant actual.

### 2.6 Creación de documentos

- Flujo principal vía **`Facturalo`** / modelos tenant (como describe el diseño); los observers cubren todas las vías (API, web, jobs) siempre que persistan por Eloquent.

---

## 3. Unificación del modelo de datos (requerimiento + diseño)

### 3.1 Principio

- **No** persistir métricas pesadas en la tabla `clients` salvo campos de auditoría opcionales (ej. `metrics_last_synced_at` en migración aparte si se desea visibilidad sin join).

### 3.2 Capas recomendadas (nombres pueden ajustarse a convención del proyecto)

| Capa | Tabla (system) | Rol |
|------|----------------|-----|
| **A. Snapshot “ahora”** | `tenant_metrics_current` (como pide el requerimiento) | Una fila por `client_id`: totales y contadores “globales” para listado rápido **sin rango** o como caché. |
| **B. Proyección para rangos** | `system_client_documents_index` y `system_client_sale_notes_index` (diseño) | Replican campos mínimos para reproducir `whereBetween(date_of_issue)`, PSE, estados, ventas netas con la misma semántica que `ClientHelper` / `DashboardData`. |
| **C. Contadores auxiliares** | `system_client_metrics_live` (opcional) | `users_count`, `establishments_count`, `soap_type_id` actualizados por evento. |
| **D. Índices por entidad (rangos usuarios/sucursales)** | `system_client_users_index`, `system_client_establishments_index` (opcional fase 2) | Solo si el nuevo dashboard debe filtrar por rango con semánticas “creados / activos al cierre / solapamiento”. |
| **E. Rollup diario (opcional, escala)** | `system_client_daily_metrics` | Agregación por `(client_id, date)` para reportes largos sin escanear millones de documentos. |
| **F. Historial + idempotencia** | Combinar **`tenant_metric_history`** (requerimiento) con **`system_metric_events`** (diseño) **o** una sola tabla si se prefiere menos objetos: eventos append-only con `event_uuid` UNIQUE, `payload` JSON, `occurred_at`, `processed_at`. |

**Nota importante:** `tenant_metric_history` con `metric_type` + `value` es útil para auditoría y gráficos de series; **no sustituye** la tabla de índice de documentos si se exige el mismo resultado que `records()` con filtros por fechas arbitrarias.

---

## 4. Arquitectura de aplicación (SOLID, sin lógica pesada en controllers)

### 4.1 Flujo de escritura (tenant → system), **tiempo real sin jobs**

Requisito: **por cada usuario creado**, **por cada documento** (y demás entidades acordadas), la base **central** debe actualizarse de forma que la **nueva lista de clientes** muestre datos actuales sin esperar workers.

1. **Observer** (o listener de modelo) en tenant: `Document`, `SaleNote`, `User`, `Establishment`, `Company` (cuando cambie `soap_type_id`).
2. El observer **no** debe abrir escrituras pesadas dentro de la misma transacción antes del commit si eso puede lockar; lo habitual es registrar un **`DB::afterCommit()`** (a nivel request) o usar en el modelo **`$this->dispatchAfterCommit(function () { ... })`** para ejecutar la sincronización **solo si** el guardado tenant confirmó.
3. Un **servicio único** (ej. `CentralTenantMetricsSync`) ejecutado en ese callback, con conexión explícita a **system**:
   - Resolver `client_id` (hostname → `Client`).
   - **Upsert** en tablas de proyección / índice (`system_client_documents_index`, etc.).
   - Actualizar **`tenant_metrics_current`** (y snapshot de contadores que alimenten el listado).
   - Opcional: append a **`tenant_metric_history`** / log de auditoría.
4. **Idempotencia en el mismo request:** si un mismo guard dispara dos veces, usar clave natural `(client_id, tenant_entity_id)` en upsert o deduplicación por hash del payload en la misma ventana; para reintentos manuales, se puede seguir guardando `event_uuid` en una tabla de deduplicación con UNIQUE sin necesidad de cola.
5. El servicio debe ser **delgado y rápido** (pocas queries system). Si en el futuro una operación puntual es muy pesada (import masivo), esa ruta concreta puede usar **solo allí** un job o comando batch; el flujo estándar del usuario permanece síncrono tras commit.

### 4.2 Servicios sugeridos (nuevos, bajo `app/Services/System/Metrics/` o similar)

- `ResolveSystemClientIdFromTenant` (usa hostname / trait existente).
- `TenantDocumentProjectionWriter` / `TenantSaleNoteProjectionWriter` (escritura inmediata en system).
- `TenantMetricsSnapshotUpdater` (actualiza fila en `tenant_metrics_current` al vuelo).
- `MetricEventRecorder` (historial + claves de idempotencia si aplica).

Controllers solo orquestan HTTP y delegan en servicios / queries.

### 4.3 Lectura (nuevo endpoint)

- Nuevo método en `ClientController`, por ejemplo **`recordsFromCentralMetrics(Request $request)`** (o `recordsV2`): **solo** Eloquent/query builder sobre conexión **system**.
- Paginación obligatoria (`page`, `per_page`) para no repetir el antipatrón de traer todos los clientes.
- Filtros alineados al actual: búsqueda, plan, bloqueos, `documents_date_start` / `documents_date_end`, etc.
- Agregaciones por página: subconsultas o joins a tablas de proyección agrupadas por `client_id` (evitar N+1 en system).
- `TrackApiPeruServices`: una sola consulta agrupada por `client_id` para el mes solicitado, mapeada en memoria.

### 4.4 Rutas y UI (convivencia)

| Elemento | Acción |
|----------|--------|
| `GET clients/records` | **Sin cambios** |
| `GET clients/records` + vista `resources/js/views/system/clients/index.vue` | **Sin cambios** |
| Nueva ruta | Ej. `GET admin/clients/records-central` o `GET clients/records-central-metrics` (definir prefijo acorde a `routes/web.php` actual bajo el mismo middleware admin). |
| Nueva vista Vue | Ej. `clients/index_metrics.vue` o `clients/index_central.vue`, copiando patrones de tabla/filtros de la actual. |
| Menú | Enlace opcional “Vista beta métricas” para pruebas internas. |

---

## 5. Catálogo de eventos (mínimo viable)

Todos deben incluir `client_id` (o datos para resolverlo) y `event_uuid`.

| Origen | Eventos / hooks |
|--------|------------------|
| `Document` | created, updated (campos: estado, PSE, regularize_shipping, totals, moneda, fecha emisión), deleted/softDeleted si aplica |
| `SaleNote` | created, updated, deleted |
| `User` | created, deleted (y soft delete si existe) |
| `Establishment` | created, deleted |
| `Company` | updated cuando cambie `soap_type_id` |

**Reversión de estados:** en updates, el servicio de sincronización debe aplicar **estado completo** en la fila del índice (upsert por clave natural `(client_id, tenant_document_id)`), no solo “+1/-1”, salvo en rollups diarios donde se use estrategia de recomputo parcial documentada.

---

## 6. Fases de implementación (orden sugerido)

### Fase 0 — Preparación

- Documentar semántica exacta de `monthly_sales_total` y NV respecto a `ClientHelper` / `DashboardData` (ya descrita en el diseño).
- Definir límites de retención del log de eventos y política de archivado.

### Fase 1 — Esqueleto seguro

- Migraciones system: `tenant_metrics_current`, `system_metric_events` (idempotencia), índices mínimos.
- Comando `metrics:backfill-client {client_id?}` stub.
- Ruta + método de lectura que devuelva datos **parciales** (solo clientes + snapshot) para prueba de cableado.

### Fase 2 — Proyección documentos / NV

- Tablas `system_client_documents_index`, `system_client_sale_notes_index`.
- Observers + **`afterCommit`** + servicio de upsert **síncrono** hacia system.
- Comando de **backfill** paginado por tenant (puede usar el mismo servicio de upsert en bucle, o proceso por lotes si el volumen lo exige).
- Ampliar el nuevo endpoint para métricas equivalentes a `records()` **sin** rango y con rango de fechas.

### Fase 3 — Paridad funcional y UX

- Nueva vista Vue con paginación y mismos filtros relevantes.
- Precarga de API Perú por agregación.
- Indicador `metrics_last_synced_at` actualizado en cada sync síncrono (no “lag de cola”; opcionalmente tiempo de escritura si se mide).

### Fase 4 — Operación

- Comando de reconciliación: comparar conteos índice vs tenant para un `client_id`.
- Alertas / logs: fallos en el callback `afterCommit` (try/catch + log sin tumbar el request del usuario salvo política explícita de “fail hard”).
- Carga de prueba con muchos tenants antes de deprecar la vista antigua (decisión futura, fuera de alcance inmediato).

---

## 7. Archivos a crear o tocar (checklist)

**Crear (típico):**

- Migraciones en `database/migrations/` (conexión system).
- Modelos Eloquent `System\...` con `$connection = 'system'` donde corresponda.
- Observers tenant + registro en `AppServiceProvider` (solo **nuevos** observers o extensión cuidadosa; no tocar lógica existente de `DocumentObserver` salvo añadir métodos que deleguen en el **servicio de sync central** tras commit).
- *(Opcional)* Jobs o comandos solo para **backfill**, importaciones masivas o reconciliación; **no** para el flujo estándar usuario → lista de clientes.
- Servicios de dominio bajo namespace acordado.
- `ClientController::recordsFromCentralMetrics` (nombre final a elección del equipo).
- Ruta en `routes/web.php`.
- Vista Vue + ruta en el router del frontend system.

**No tocar en la primera entrega en producción:**

- Cuerpo de `records()`.
- `resources/js/views/system/clients/index.vue` (usar archivo nuevo).

---

## 8. Pruebas recomendadas

1. **Unit / feature** en system: upsert idempotente con el mismo `event_uuid` dos veces.
2. **Integración**: crear documento en tenant de prueba → verificar fila en índice y snapshot.
3. **Comparación**: script o comando que para N clientes muestre diff entre `records()` legacy y nuevo endpoint (tolerancia cero en conteos enteros; decimales en ventas según redondeo).
4. **Carga**: listado paginado con 500+ clientes sin conexión tenant en el request de listado.

---

## 9. Riesgos y edge cases

| Riesgo | Mitigación |
|--------|------------|
| Doble disparo en el mismo request / reenvíos | Upsert por clave natural `(client_id, tenant_*_id)`; opcional `event_uuid` UNIQUE en log de deduplicación |
| Transacciones tenant rollback | **`afterCommit`** obligatorio antes de escribir en system |
| **Latencia extra** en cada guardado tenant (usuario crea documento y espera también el round-trip a system) | Servicio mínimo (1 upsert + 1 update snapshot); índices en system; evitar recomputar totales globales innecesarios en cada evento |
| Fallo temporal de la base system | Decidir política: log + reintento síncrono limitado, o cola **solo como DLQ** para reprocessar fallidos sin perder evento |
| Imports masivos o SQL directo que eviten observers | Comando de backfill + reconciliación periódica |
| Cambio de fecha de emisión o correcciones raras | Upsert por entidad + regeneración desde tenant en reconciliación |
| `ClientCollection` hace query por cliente | En el nuevo flujo usar **Resource** distinto que no dispare N+1 |

---

## 10. Limitaciones arquitectónicas conocidas

- **Hyn Tenancy + múltiples bases**: cualquier solución que no sea proyección central obliga a consultar cada tenant en el listado → no cumple el objetivo de escala.
- **Exactitud 100% con el legacy** depende de replicar todas las ramas de `records()` (ciclo de facturación, `includeSaleNotesLimitDocuments`, actualización de `configurations.quantity_*` que hoy ocurre dentro del mismo método). La proyección central **no debe** escribir en `tenant.configurations`; solo leer en system. Si el negocio exige seguir actualizando esos campos, eso permanece en el flujo actual hasta que se rediseñe aparte.

---

## 11. Mejoras futuras (post validación)

- Paginar también el endpoint legacy o redirigir tráfico al nuevo cuando esté validado.
- WebSockets / broadcasting: con sync inmediato a **system**, la lista admin ya refleja el dato en el **siguiente** `GET`; broadcasting solo aporta si varios operadores miran la misma pantalla sin refrescar (opcional).
- Particionamiento de tablas de índice por `client_id` o por rango de fechas si el volumen crece por encima de millones de filas en system.

---

## 12. Criterio de éxito

- El listado optimizado **nunca** llama a `DB::connection('tenant')` durante la generación de la respuesta.
- Los números coinciden con el legacy en casos de prueba acordados (mismos rangos, mismos planes, mismos estados).
- Cero cambios de comportamiento en rutas y vistas existentes hasta decisión explícita del equipo.

---

*Documento generado como guía de implementación única. Los archivos REQUERIMIENTO_CLIENTE_CENTRAL.md y DISENO_METRICAS_TIEMPO_REAL_CLIENTES.md pueden mantenerse como referencia histórica; este archivo es el que debe gobernar el trabajo de implementación y priorización por fases.*
