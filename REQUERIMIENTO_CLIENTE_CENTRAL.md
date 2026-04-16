Necesito que analices profundamente mi aplicación Laravel multitenant con base de datos separada por tenant y desarrolles una NUEVA implementación optimizada del dashboard/listado de clientes usando arquitectura **Event-Driven / Tiempo Real + Historial de Métricas**, SIN modificar ni romper la implementación actual existente.

# IMPORTANTE

La implementación actual en `ClientController@records()` y su vista/dashboard actual:

* NO deben modificarse.
* NO deben eliminarse.
* NO deben refactorizarse todavía.
* Deben permanecer intactos para no afectar producción.

La nueva implementación debe coexistir en paralelo para pruebas y validación.

---

# Objetivo

Crear una nueva versión optimizada y escalable del listado/dashboard de tenants que:

1. Muestre métricas actuales instantáneamente.
2. Permita consultas históricas por rango de fechas.
3. Elimine completamente el problema N+1 de consultas multitenant.

---

# Requerimientos de Implementación

## 1. Analizar Arquitectura Actual

Antes de implementar:

* Analiza toda la aplicación.

* Identifica cómo actualmente se crean/modifican:

  * Documents
  * Sale Notes
  * Users
  * Establishments
  * Configurations / Companies
  * Estados de documentos

* Detecta observers, events, services, actions o listeners existentes reutilizables.

* NO dupliques lógica innecesariamente.

* Respeta arquitectura existente.

---

## 2. Implementar Arquitectura de Métricas Profesional

NO guardar métricas directamente en tabla `clients` salvo que sea estrictamente necesario.

Crear estructura profesional desacoplada:

### Tabla `tenant_metrics_current`

Snapshot actual de métricas por tenant.

Campos sugeridos:

* id
* client_id
* current_month_documents
* total_documents
* total_documents_pse
* total_users
* total_sales_notes
* total_establishments
* pending_regularize_shipping
* pending_not_sent
* pending_to_be_canceled
* pending_rejected
* pending_observed
* monthly_sales_total
* metrics_last_synced_at
* timestamps

---

### Tabla `tenant_metric_history`

Historial temporal / auditoría de métricas.

Campos sugeridos:

* id
* client_id
* metric_type
* event_type
* value
* reference_type
* reference_id
* event_date
* metadata (json nullable si aplica)
* timestamps

---

## 3. Implementar Sincronización Event-Driven

Cada vez que ocurra alguno de estos eventos en tenant:

### Documents

* Crear
* Actualizar
* Cambiar estado
* Anular / Rechazar / Observar / Regularizar / Enviar

### Sale Notes

* Crear / Editar / Eliminar

### Users

* Crear / Eliminar

### Establishments

* Crear / Eliminar

Debe ejecutarse automáticamente:

### A.

Actualizar `tenant_metrics_current`

### B.

Registrar evento en `tenant_metric_history`

---

## 4. Reglas de Implementación de Eventos

La implementación debe:

* Ser segura para multitenancy.
* Identificar correctamente qué tenant originó el evento.
* Actualizar el `client_id` correcto en DB central.
* No romper contexto tenant.
* Ser idempotente.
* Evitar dobles incrementos.
* Manejar correctamente updates vs creates.
* Considerar soft deletes si existen.
* Manejar reversión de estados.

---

## 5. Crear Nuevo Método en ClientController

Implementa un nuevo método en `ClientController`:

### Reglas:

* NO modificar `records()`
* Crear nuevo método con nombre profesional:

  * `recordsOptimized()`
  * `recordsV2()`
  * o mejor nombre según análisis

Este nuevo método debe:

* Leer SOLO desde `tenant_metrics_current`
* NO consultar bases tenant
* NO cambiar contexto tenancy
* NO ejecutar lógica pesada

---

## 6. Crear Nueva Vista Independiente

Crear nueva vista/dashboard para esta implementación nueva.

### Reglas:

* NO modificar vista actual
* Crear nueva vista separada para pruebas
* Reutilizar estilos/componentes actuales si es posible

### Debe incluir:

* Listado de clientes usando métricas optimizadas
* Filtros por rango de fechas utilizando `tenant_metric_history`
* Consultas históricas eficientes por rango

---

## 7. Crear Nueva Ruta de Pruebas

Agregar nueva ruta independiente para validar esta implementación.

Ejemplo:
`/admin/clients-optimized`

Sin afectar rutas actuales.

---

## 8. Arquitectura Obligatoria

Implementar siguiendo:

* SOLID
* Clean Code
* Domain Services
* Events / Listeners / Actions / Jobs cuando corresponda
* Alta cohesión / Bajo acoplamiento

NO poner lógica pesada en controllers.

NO poner lógica compleja directamente en observers.

---

## 9. Performance / Escalabilidad

Diseñar pensando en:

* Cientos o miles de tenants
* Alto volumen de documentos
* Consultas rápidas por rango de fechas

Optimizar índices y estructura para analytics futuros.

---

## 10. Entregables Esperados

Quiero que generes:

1. Análisis técnico de arquitectura propuesta.
2. Explicación de archivos a crear/modificar.
3. Implementación completa.
4. Nueva ruta.
5. Nuevo método en controller.
6. Nueva vista.
7. Explicación de cómo probar la nueva implementación.
8. Riesgos / edge cases detectados.
9. Recomendaciones de mejoras futuras.

---

## Restricciones Finales

* NO tocar implementación actual hasta validación completa.
* La nueva implementación debe convivir en paralelo con la actual.
* No romper funcionalidades existentes.
* No hacer simplificaciones.
* Si detectas limitaciones arquitectónicas, explícalas antes de implementar.

Implementa esta solución como un arquitecto senior especializado en SaaS multitenant Laravel enterprise-grade, priorizando escalabilidad, mantenibilidad, precisión de métricas y cero regresiones.
