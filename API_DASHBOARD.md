# Documentación de API para Dashboard

Esta documentación detalla el endpoint para obtener los datos estadísticos y gráficos del dashboard (totales de ventas, comprobantes, notas de venta, balance, etc.) para ser consumidos desde un frontend externo.

## Autenticación
Todas las peticiones requieren el token de autenticación (`Authorization: Bearer <TOKEN>`).

---

## Obtener Datos del Dashboard
Este endpoint devuelve información completa sobre totales de comprobantes, notas de venta, balances generales y datos para gráficos.

**Endpoint:** `GET /api/report`

**Nota:** Aunque el controlador en el backend define valores por defecto (fecha actual, mes actual), **NO acepta parámetros por GET/POST** para filtrar por fechas desde la petición. Los filtros de fecha están "quemados" (hardcoded) en el código del controlador (`MobileController.php`), por lo que siempre devolverá los datos del **día actual** o del **mes actual** según la lógica interna predefinida.

### Comportamiento del Filtro (Interno)
El servidor ejecuta internamente la consulta con los siguientes parámetros fijos:
- **customer_id:** null (Todos los clientes)
- **date_end:** Fecha actual (Y-m-d)
- **date_start:** Fecha actual (Y-m-d)
- **month_end:** Mes actual (Y-m)
- **month_start:** Mes actual (Y-m)
- **period:** 'month' (Mes actual)

### Respuesta Exitosa
El objeto de respuesta contiene varias secciones clave: `document` (Facturas/Boletas), `sale_note` (Notas de Venta), `general` (Gráficos generales) y `balance`.

```json
{
    "data": {
        "document": {
            "totals": {
                "total_payment": "1500.00",    // Total cobrado
                "total_to_pay": "500.00",      // Pendiente de cobro
                "total": "2000.00"             // Total facturado (menos notas de crédito)
            },
            "graph": {
                "labels": ["Total cobrado", "Pendiente de cobro"],
                "datasets": [
                    {
                        "label": "Comprobantes",
                        "data": [1500, 500],
                        "backgroundColor": ["...", "..."],
                        "borderColor": ["...", "..."]
                    }
                ]
            }
        },
        "sale_note": {
            "totals": {
                "total_payment": "300.00",
                "total_to_pay": "50.00",
                "total": "350.00"
            },
            "graph": { ... }
        },
        "general": {
            "totals": {
                "total_documents": "2000.00",
                "total_sale_notes": "350.00",
                "total": "2350.00"
            },
            "graph": {
                "labels": ["01", "02", "03", ...], // Días del mes
                "datasets": [
                    {
                        "label": "Total notas de venta",
                        "data": [ ... ]
                    },
                    {
                        "label": "Total comprobantes",
                        "data": [ ... ]
                    },
                    {
                        "label": "Total",
                        "data": [ ... ]
                    }
                ]
            }
        },
        "balance": {
            "totals": {
                "total_document": "2000.00",
                "total_payment": "1800.00",
                "total_expense": "200.00",
                "total": "1600.00" // Balance final
            },
            "graph": { ... }
        },
        "items": [ ... ] // Lista de ítems más vendidos (si aplica)
    }
}
```

## Resumen para Implementación Frontend
1.  **Llamada:** Simplemente haz un `GET` a `/api/report`.
2.  **Parámetros:** No envíes ninguno; el servidor los ignorará.
3.  **Datos:** Recibirás un JSON con estructuras listas para usar en librerías de gráficos (como Chart.js) bajo la clave `graph` y totales monetarios formateados bajo la clave `totals`.
