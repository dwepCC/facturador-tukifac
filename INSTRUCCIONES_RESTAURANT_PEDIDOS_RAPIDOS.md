# Instrucciones de Integración: Pedidos Rápidos (Sin Mesa) - Módulo Restaurante

## 1. Descripción General
Se ha habilitado la funcionalidad para registrar pedidos rápidos (Para Llevar / Delivery) que no requieren una mesa asignada. Estos pedidos se identifican por tener `table_id` como `null` y un campo `customer_name` opcional.

## 2. Registro de Pedidos (Frontend -> Backend)
**Endpoint:** `POST /api/restaurant/command-item/save`

**Cuerpo de la Petición (JSON):**
```json
{
    "id": null, // null para nuevo, ID para editar
    "table_id": null, // IMPORTANTE: Enviar null para pedidos rápidos
    "item_id": 123,
    "item": { ... }, // Objeto del producto
    "quantity": 1,
    "status": 1, // 1: Recibido
    "status_description": "Recibido",
    "customer_name": "Juan Pérez" // NUEVO CAMPO: Nombre del cliente
}
```

## 3. Gestión de Estados
Los estados del pedido siguen el flujo estándar:
1. **RECIBIDO** (1)
2. **EN PROCESO** (2)
3. **PARA ENTREGAR** (3)
4. **ENTREGADO** (4)

**Endpoint para avanzar estado:** `GET /api/restaurant/command-status/set/{id}`

## 4. Comportamiento de Finalización (NUEVO)
Para evitar la acumulación de pedidos rápidos en el sistema (ya que no tienen una mesa que se "cierre"):

**IMPORTANTE:** Cuando un pedido rápido (`table_id` = `null`) cambia su estado a **4 (ENTREGADO)**, el sistema **eliminará automáticamente el registro** de la tabla `restaurant_item_order_statuses`.

### Implicaciones para el Frontend:
- Al recibir la confirmación de cambio de estado a 4, debe asumir que el pedido ya no existirá en futuras consultas de `getStatusItems`.
- Debe limpiar el pedido de la interfaz localmente si es necesario.

## 5. Resumen de Cambios en Base de Datos
- Tabla: `restaurant_item_order_statuses`
- Columna `table_id`: Ahora admite `NULL`.
- Columna `customer_name`: Nuevo campo `VARCHAR` (nullable).
