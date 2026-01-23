# Documentación de Endpoints del Dashboard

A continuación se detallan los endpoints disponibles para obtener la información mostrada en el Dashboard.

**Nota:** Estas rutas se encuentran actualmente bajo el middleware `web` (`auth`, `locked.tenant`, `check.email.verified`), por lo que requieren una sesión activa o autenticación compatible.

## 1. Datos Globales
Obtiene los totales globales de comprobantes y notas de venta.

- **Método:** `GET`
- **Ruta:** `/dashboard/global-data`
- **Respuesta (JSON):**

```json
{
  "total_cpe": 123, // Cantidad total de comprobantes
  "document_total_global": {
     // Totales de comprobantes (Facturas, Boletas)
     "total": 1500.00,
     "total_payment": 1000.00,
     "total_to_pay": 500.00,
     "total_currency_type_id": "PEN" // o moneda principal
  },
  "sale_note_total_global": {
     // Totales de Notas de Venta
     "total": 800.00,
     "total_payment": 800.00,
     "total_to_pay": 0.00
  }
}
```

## 2. Datos Detallados (Filtrados)
Obtiene información detallada para gráficos y balances, filtrada por establecimiento y periodo.

- **Método:** `POST`
- **Ruta:** `/dashboard/data`
- **Body (JSON):**

```json
{
  "establishment_id": 1,
  "period": "month", // Opciones: 'month', 'between_months', 'date', 'between_dates', 'last_week'
  "date_start": "2023-10-01", // Requerido si period es 'date', 'between_dates'
  "date_end": "2023-10-31",   // Requerido si period es 'between_dates'
  "month_start": "2023-10",   // Requerido si period es 'month', 'between_months'
  "month_end": "2023-12"      // Requerido si period es 'between_months'
}
```

- **Respuesta (JSON):**

```json
{
  "document": {
    "totals": {
      "total_payment": "1000.00",
      "total_to_pay": "500.00",
      "total": "1500.00"
    },
    "graph": {
      "labels": ["Total cobrado", "Pendiente de cobro"],
      "datasets": [ ... ] // Datos para Chart.js
    }
  },
  "sale_note": {
    "totals": { ... }, // Similar a document
    "graph": { ... }
  },
  "general": {
    // Totales agrupados por tipo de documento
    "purchase": { "totals": { "total": "...", "total_payment": "..." } },
    "expense": { "totals": { "total": "...", "total_payment": "..." } },
    "sale_note": { "totals": { "total": "...", "total_payment": "..." } },
    "document": { "totals": { "total": "...", "total_payment": "..." } }
  },
  "balance": {
    "totals": {
        "total_payment": "...", // Ingresos totales (Ventas + Notas Venta)
        "total_destination": "...", // Egresos totales (Compras + Gastos)
        "balance": "..." // Diferencia
    },
    "graph": { ... }
  },
  "items": [
     // Top productos vendidos
     {
       "description": "Producto A",
       "total": "500.00",
       "quantity": 10
     },
     ...
  ]
}
```

## 3. Otros Endpoints de Interés

- **Utilidades:** `POST /dashboard/utilities`
- **Stock por Producto:** `GET /dashboard/stock-by-product/records`
- **Productos por Vencer:** `GET /dashboard/product-of-due/records`
- **Ventas Adicionales:** `POST /dashboard/data_aditional`
