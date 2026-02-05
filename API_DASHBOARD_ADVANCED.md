# Documentación Avanzada de Endpoints del Dashboard

Esta documentación detalla los endpoints avanzados disponibles en el módulo de Dashboard (`modules/Dashboard/Routes/api.php`) para obtener datos estadísticos, reportes y métricas del sistema.

**Nota:** Todos los endpoints requieren autenticación mediante Token Bearer (`Authorization: Bearer {token}`).

## 1. Obtener Datos Globales
Obtiene los totales globales de comprobantes y notas de venta.

*   **URL:** `/api/dashboard/global-data`
*   **Método:** `GET`
*   **Parámetros:** Ninguno
*   **Respuesta Exitosa (JSON):**
    ```json
    {
        "total_cpe": 1500,
        "document_total_global": "12500.50",
        "sale_note_total_global": "5300.20"
    }
    ```

## 2. Obtener Datos Principales del Dashboard
Obtiene los totales de documentos, notas de venta, balance general y gráficos asociados, filtrados por establecimiento y periodo.

*   **URL:** `/api/dashboard/data`
*   **Método:** `POST`
*   **Parámetros (Body JSON):**
    *   `establishment_id` (int, requerido): ID del establecimiento.
    *   `period` (string, requerido): Periodo de filtro. Valores: `month`, `between_months`, `date`, `between_dates`, `last_week`.
    *   `date_start` (string, opcional): Fecha de inicio. Formatos soportados: `Y-m-d` (2023-10-01) o `d/m/Y` (01/10/2023). Usado si `period` es `date`, `between_dates` o `last_week`.
    *   `date_end` (string, opcional): Fecha de fin. Formatos soportados: `Y-m-d` o `d/m/Y`. Usado si `period` es `between_dates` o `last_week`.
    *   `month_start` (string, opcional): Mes de inicio. Formatos soportados: `Y-m` (2023-10) o `m/Y` (10/2023). Usado si `period` es `month` o `between_months`.
    *   `month_end` (string, opcional): Mes de fin. Formatos soportados: `Y-m` o `m/Y`. Usado si `period` es `between_months`.

*   **Ejemplo de Payload:**
    ```json
    {
        "establishment_id": 1,
        "period": "month",
        "month_start": "2023-10",
        "month_end": "2023-10"
    }
    ```

*   **Respuesta Exitosa (JSON):**
    ```json
    {
        "document": {
            "totals": {
                "total_payment": "1000.00",
                "total_to_pay": "200.00",
                "total": "1200.00"
            },
            "graph": { ... }
        },
        "sale_note": {
            "totals": {
                "total_payment": "500.00",
                "total_to_pay": "50.00",
                "total": "550.00"
            },
            "graph": { ... }
        },
        "general": {
            "totals": {
                "total_documents": "1200.00",
                "total_sale_notes": "550.00",
                "total": "1750.00"
            },
            "graph": { ... }
        },
        "balance": {
            "totals": { ... },
            "graph": { ... }
        },
        "items": [ ... ] // Top productos vendidos
    }
    ```

## 3. Obtener Datos Adicionales (Compras y Clientes)
Obtiene totales de compras, productos más vendidos y mejores clientes.

*   **URL:** `/api/dashboard/data_aditional`
*   **Método:** `POST`
*   **Parámetros (Body JSON):**
    *   Mismos parámetros de fecha/periodo que `/api/dashboard/data`.
    *   `enabled_move_item` (boolean): Habilitar movimiento de ítems.
    *   `enabled_transaction_customer` (boolean): Habilitar transacciones de clientes.
    *   `no_take` (boolean, opcional): Si es true, ignora el límite de registros.
    *   `page` (int, opcional): Número de página para paginación de productos.

*   **Respuesta Exitosa (JSON):**
    ```json
    {
        "purchase": {
            "totals": {
                "purchases_total": "5000.00",
                "total": "5000.00"
            },
            "graph": { ... }
        },
        "items_by_sales": [
            {
                "total": "1500.00",
                "description": "Producto A",
                "internal_id": "P001",
                "move_quantity": "50.00"
            },
            ...
        ],
        "top_customers": [
            {
                "total": "2000.00",
                "name": "Cliente Juan",
                "number": "12345678",
                "transaction_quantity": 5
            },
            ...
        ]
    }
    ```

## 4. Obtener Filtros Disponibles
Obtiene la lista de establecimientos para usar en los filtros.

*   **URL:** `/api/dashboard/filter`
*   **Método:** `GET`
*   **Parámetros:** Ninguno
*   **Respuesta Exitosa (JSON):**
    ```json
    {
        "establishments": [
            {
                "id": 1,
                "name": "Oficina Principal"
            },
            {
                "id": 2,
                "name": "Sucursal Norte"
            }
        ]
    }
    ```

## 5. Stock por Producto
Obtiene el listado de productos con stock bajo (menor o igual a 20).

*   **URL:** `/api/dashboard/stock-by-product/records`
*   **Método:** `GET`
*   **Parámetros (Query String):**
    *   `establishment_id` (int, opcional): ID del establecimiento. Si no se envía, toma el primero.
    *   `page` (int, opcional): Número de página (paginación de Laravel).

*   **Respuesta Exitosa (JSON):**
    Retorna una colección paginada de productos (`DashboardStockCollection`).

## 6. Productos por Vencer
Obtiene productos con fecha de vencimiento en un rango dado.

*   **URL:** `/api/dashboard/product-of-due/records`
*   **Método:** `GET`
*   **Parámetros (Query String):**
    *   `establishment_id` (int, opcional): ID del establecimiento.
    *   `date_start` (string, opcional): Fecha inicio vencimiento. Formatos: `Y-m-d` o `d/m/Y`.
    *   `date_end` (string, opcional): Fecha fin vencimiento. Formatos: `Y-m-d` o `d/m/Y`.

*   **Respuesta Exitosa (JSON):**
    Retorna una colección paginada de productos (`DashboardInventoryCollection`).

## 7. Utilidades
Calcula las utilidades (Ingresos - Egresos) basado en ventas, compras y gastos.

*   **URL:** `/api/dashboard/utilities`
*   **Método:** `POST`
*   **Parámetros (Body JSON):**
    *   Mismos parámetros de fecha/periodo que `/api/dashboard/data`.
    *   `enabled_expense` (boolean): Incluir gastos en el cálculo.
    *   `item_id` (int, opcional): Filtrar por un producto específico.

*   **Respuesta Exitosa (JSON):**
    ```json
    {
        "utilities": {
            "totals": {
                "total_income": "15000.00",
                "total_egress": "8000.00",
                "utility": "7000.00"
            },
            "graph": {
                "labels": ["Ingreso", "Egreso"],
                "datasets": [ ... ]
            }
        }
    }
    ```
