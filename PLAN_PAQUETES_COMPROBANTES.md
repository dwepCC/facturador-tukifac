# Plan de Implementación: Paquetes de Comprobantes (Add-on sin cambiar Plan)

## Objetivo
- Permitir a un tenant adquirir “paquetes” de comprobantes adicionales consumibles durante el ciclo de facturación actual, sin modificar su plan base.
- Cuando el tenant supera el límite de su plan, puede seguir emitiendo si tiene paquetes activos con saldo. Al renovarse el ciclo, se reestablece el límite del plan y los paquetes expiran (no arrastran saldo).

## Alcance
- No altera la estructura ni comportamiento actual de planes.
- No afecta el conteo base por ciclo ni la lógica de bloqueo existente, salvo para incorporar la capacidad de los paquetes.
- Se mantiene la configuración de si las notas de venta se incluyen en el límite conforme al plan; los paquetes siguen la misma política por defecto, con opción de parametrización futura.

## Supuestos
- Los límites de documentos son por ciclo de facturación definido en System por `clients.start_billing_cycle` (system DB).
- Si no hay ciclo configurado, se usa mes calendario (comportamiento actual).
- El “paquete” es válido únicamente hasta el fin del ciclo en curso; cualquier saldo no utilizado expira.
- Concurrency baja/media en emisión; se agregarán salvaguardas básicas para consumo atómico.

## Modelo de Datos (System DB)
- Tabla: `client_document_packages`
  - `id` (PK)
  - `client_id` (FK a clients, system)
  - `units_total` (int, p. ej. 100)
  - `units_consumed` (int, default 0)
  - `include_sale_notes` (bool, default null → hereda del plan al momento de compra; opcionalmente configurable)
  - `cycle_start_at` (date) y `cycle_end_at` (date) capturados al momento de compra según ciclo del cliente
  - `status` (enum: active, expired, canceled)
  - `created_at`, `updated_at`

- Opcional (catálogo): `document_packages`
  - `id`, `name`, `units_total`, `price`, `include_sale_notes` (default), `locked`
  - Permite definir SKU de paquetes. Si no se requiere catálogo, puede omitirse y capturar solo `units_total` en la compra.

## Integraciones y Lógica
- Conteo actual del ciclo (existente): 
  - En [DocumentHelper::getStartEndDateForFilterDocument](file:///c:/xampp/htdocs/facturador-tukifac/modules/Document/Helpers/DocumentHelper.php#L172-L196).
  - En [DocumentHelper::exceedLimitDocuments](file:///c:/xampp/htdocs/facturador-tukifac/modules/Document/Helpers/DocumentHelper.php#L99-L158).

- Nueva lógica de validación con paquetes:
  - Función nueva en `DocumentHelper`: `checkLimitWithPackages($type = 'document')`:
    - Obtiene `limit_documents` del plan del tenant.
    - Calcula `quantity_documents` del ciclo (incluye NV si el plan lo indica).
    - Si `limit_documents == 0`: ilimitado → permitir (no consume paquetes).
    - Si `quantity_documents < limit_documents`: permitir (no consume paquetes).
    - Si `quantity_documents >= limit_documents`: consulta paquetes activos del cliente dentro del ciclo actual y calcula `remaining = sum(units_total - units_consumed)` filtrando por `include_sale_notes` según tipo y política.
      - Si `remaining > 0`: permitir y marcar para consumo de 1 unidad del paquete (estrategia FIFO por `created_at` o por `cycle_end_at` más próximo).
      - Si `remaining == 0`: bloquear.
  - Consumo de paquete:
    - Se implementa en evento de creación (`Document::created` y `SaleNote::created`) dentro de [LockedEmissionProvider](file:///c:/xampp/htdocs/facturador-tukifac/app/Providers/LockedEmissionProvider.php#L72-L131):
      - Después de crear, recalcular posición relativa vs `limit_documents`. Si el documento “cruza” el límite del plan, consumir 1 unidad del primer paquete activo.
      - Garantizar idempotencia básica con verificación de `units_consumed < units_total` y bloqueo optimista (UPDATE con condición).
    - Alternativa: consumo antes de crear, con transacción; se prefiere consumir post-create para respetar el flujo actual y evitar doble validación, combinándolo con pre-chequeo para permitir el flujo.

- Reemplazo/Extensión de llamadas existentes:
  - En [Tenant\DocumentController::store](file:///c:/xampp/htdocs/facturador-tukifac/app/Http/Controllers/Tenant/DocumentController.php#L603-L614): reemplazar la llamada a `exceedLimitDocuments()` por `checkLimitWithPackages()`.
  - En [LockedEmissionProvider::locked_emission](file:///c:/xampp/htdocs/facturador-tukifac/app/Providers/LockedEmissionProvider.php#L75-L84): usar `checkLimitWithPackages()` para decidir bloqueo y, si aplica, ejecutar consumo.
  - En [LockedEmissionProvider::lockedEmissionSaleNotes](file:///c:/xampp/htdocs/facturador-tukifac/app/Providers/LockedEmissionProvider.php#L116-L122): misma lógica para NV.

## API (System)
- Endpoints (System, panel central):
  - POST `/clients/{client}/document-packages` → crear compra de paquete para cliente
    - Body: `units_total`, `include_sale_notes?`, se autocompleta `cycle_start_at`/`cycle_end_at` según ciclo actual del cliente.
  - GET `/clients/{client}/document-packages/summary` → resumen de paquetes activos y consumo
  - POST `/clients/{client}/document-packages/{id}/cancel` → cancelar paquete (si aplica)

- Seguridad:
  - Autorización solo para roles administrativos del System.
  - Validaciones: ciclo válido, cliente válido, `units_total > 0`.

## UI (System)
- En “Clientes” (panel central) mostrar:
  - Límite del plan y consumo del ciclo (ya expone `current_count_doc_month` y `max_documents` vía [ClientCollection](file:///c:/xampp/htdocs/facturador-tukifac/app/Http/Resources/System/ClientCollection.php#L43-L52)).
  - Bloques de “Paquetes activos” con saldo y vencimiento (fin del ciclo).
  - Botón “Agregar paquete” para crear la compra (100, 200, etc.).

- En “Planes” no se modifica el plan; se puede agregar un panel informativo indicando que los paquetes son add-ons.

## Ciclo y Expiración
- Al comprar se fija `cycle_start_at`/`cycle_end_at` del cliente en ese momento.
- Expiración automática:
  - Al consultar paquetes activos, filtrar por `now() <= cycle_end_at` y `status = active`.
  - Cron opcional para marcar `expired` cuando `now() > cycle_end_at`.

## Concurrency / Consumo atómico
- Implementar consumo con UPDATE condicionado:
  - Seleccionar primer paquete activo con `units_consumed < units_total` y dentro del ciclo.
  - Ejecutar `UPDATE ... SET units_consumed = units_consumed + 1 WHERE id = ? AND units_consumed < units_total` y verificar filas afectadas.
  - Si 0 filas afectadas, reintentar con siguiente paquete; si ninguno, bloquear.

## Migraciones
- Crear tabla `client_document_packages` en System DB con índices por `client_id`, `status`, `cycle_end_at`.
- Opcional: crear `document_packages` catálogo.

## Compatibilidad hacia atrás
- Si el cliente no tiene paquetes, el flujo actual permanece inalterado.
- La política de inclusión de NV en paquetes hereda del plan para evitar inconsistencias.

## Cambios en Código (Resumen)
- `Modules\Document\Helpers\DocumentHelper`:
  - Añadir `checkLimitWithPackages($type)`
  - Añadir helpers: `getActivePackageRemaining($client_id, $type)`, `consumeOneUnitFromPackage($client_id, $type)`

- `App\Providers\LockedEmissionProvider`:
  - Usar `checkLimitWithPackages()` en eventos `Document::created` y `SaleNote::created` y realizar consumo si aplica.

- `App\Http\Controllers\Tenant\DocumentController::store`:
  - Sustituir pre-chequeo por `checkLimitWithPackages()`.

- Modelos (System):
  - `ClientDocumentPackage` con `UsesSystemConnection`.

## End-to-End Flujo
1. Admin compra paquete para un cliente en el System.
2. Cliente emite CPE:
   - Pre-chequeo: si excede plan, verifica paquetes; si hay saldo, permite.
3. Al crear CPE:
   - Evento consume 1 unidad del paquete si la emisión cruza el límite del plan.
4. Al renovar ciclo:
   - Se reestablece el límite del plan; los paquetes expiran al fin del ciclo.

## Verificación
- Pruebas unitarias/funcionales:
  - Sin paquetes: bloqueo como hoy al exceder.
  - Con paquete: permitir hasta agotar unidades; luego bloquear.
  - NV incluida/excluida según plan/paquete.
  - Cambio de ciclo: expira paquetes y reinicia conteo plan.

## Riesgos y Mitigaciones
- Consumo doble por concurrencia: mitigado con UPDATE condicionado y orden FIFO.
- Inconsistencia NV: mitigar heredando la política del plan al crear el paquete.
- Cambio de ciclo en medio de compras: fijar `cycle_start_at`/`cycle_end_at` al momento de compra.

## Despliegue
- Migraciones System DB.
- Añadir modelo y repositorio de paquetes.
- Actualizar helpers y provider.
- Agregar UI y endpoints en System.

