# Cambios Necesarios en el Backend para el Dashboard

Este documento describe los cambios que el backend debe implementar para que el frontend muestre correctamente los datos del Dashboard, especialmente los **cards del periodo** (Ventas, Por Cobrar, Balance Neto).

---

## 1. Endpoint `/api/dashboard/data` (POST)

### 1.1 Payload que envía el frontend

El frontend **siempre** envía los seis parámetros siguientes. El backend debe aceptarlos todos sin error "Undefined array key":

```json
{
  "establishment_id": 1,
  "period": "month",
  "date_start": "2024-02-01",
  "date_end": "2024-02-29",
  "month_start": "2024-02",
  "month_end": "2024-02"
}
```

**Valores de `period`:**
- `month` – Mensual (usar `month_start`/`month_end` para filtrar)
- `date` – Diario (usar `date_start` = `date_end` para filtrar un solo día)
- `between_dates` – Rango de fechas (usar `date_start` y `date_end`)

---

### 1.2 Estructura de respuesta obligatoria

La respuesta debe incluir al menos la siguiente estructura para que los cards del periodo muestren datos:

```json
{
  "document": {
    "totals": {
      "total_payment": "1000.00",
      "total_to_pay": "200.00",
      "total": "1200.00"
    },
    "graph": {
      "labels": ["Pagado", "Por cobrar"],
      "datasets": [{ "data": [1000, 200], "label": "CPE" }]
    }
  },
  "sale_note": {
    "totals": {
      "total_payment": "500.00",
      "total_to_pay": "50.00",
      "total": "550.00"
    },
    "graph": {
      "labels": ["Pagado", "Por cobrar"],
      "datasets": [{ "data": [500, 50], "label": "NV" }]
    }
  },
  "balance": {
    "totals": {
      "total_payment": "1500.00",
      "balance": "1500.00"
    },
    "graph": {
      "labels": ["Día 1", "Día 2", "..."],
      "datasets": [{ "label": "Ingresos", "data": [...] }]
    }
  },
  "general": {
    "totals": {
      "total_documents": "1200.00",
      "total_sale_notes": "550.00",
      "total": "1750.00"
    },
    "graph": {
      "labels": ["..."],
      "datasets": [...]
    }
  },
  "items": [
    {
      "id": "P001",
      "description": "Producto A",
      "total": "1500.00"
    }
  ],
  "document_count": 25
}
```

---

### 1.3 Mapeo: backend → cards del frontend

| Card del frontend        | Ruta en la respuesta       | Descripción                          |
|--------------------------|----------------------------|--------------------------------------|
| **Ventas (Periodo)**     | `balance.totals.total_payment` | Ingresos totales del periodo         |
| **Por Cobrar (Periodo)** | `document.totals.total_to_pay` | Monto pendiente de cobro (CPE)       |
| **Balance Neto (Periodo)** | `balance.totals.balance`   | Utilidad / balance del periodo       |

Si falta alguno de estos campos, el card correspondiente quedará vacío.

---

### 1.4 Valores por defecto recomendados

Para evitar errores cuando no hay datos, se recomienda devolver `"0.00"` en lugar de `null` o omitir los campos:

```php
// Ejemplo en PHP
'balance' => [
    'totals' => [
        'total_payment' => $totalPayment ?? '0.00',
        'balance' => $balance ?? '0.00',
    ],
    'graph' => [...],
],
'document' => [
    'totals' => [
        'total_payment' => $docPaid ?? '0.00',
        'total_to_pay' => $docToPay ?? '0.00',
        'total' => $docTotal ?? '0.00',
    ],
    'graph' => [...],
],
```

---

## 2. Endpoint `/api/dashboard/global-data` (GET) – opcional

Actualmente este endpoint devuelve totales históricos sin filtro. Si se quiere que los **cards históricos** también respeten el periodo seleccionado, habría que ampliar este endpoint para aceptar filtros.

### 2.1 Opción A: sin cambios (actual)

- El endpoint sigue sin parámetros.
- Los cards históricos siempre muestran totales globales.
- No se requiere ningún cambio.

### 2.2 Opción B: usar datos de `/api/dashboard/data` (recomendado)

En lugar de modificar `global-data`, basta con que `/api/dashboard/data` devuelva:

- `document_count` (int, opcional): cantidad de comprobantes (CPE) en el periodo  
- `document.totals.total`: monto total CPE del periodo  
- `sale_note.totals.total`: monto total de notas de venta del periodo  

El frontend puede usar estos valores para mostrar los cards históricos según el filtro. Si falta `document_count`, se seguirá mostrando el total histórico de `global-data` para la cantidad de comprobantes.

### 2.3 Opción C: filtros en `global-data` (alternativa)

Se podría añadir soporte de query params a `global-data`:

```
GET /api/dashboard/global-data?establishment_id=1&period=month&month_start=2024-02&month_end=2024-02&date_start=2024-02-01&date_end=2024-02-29
```

Respuesta esperada:

```json
{
  "total_cpe": 45,
  "document_total_global": "12500.50",
  "sale_note_total_global": "5300.20"
}
```

Con esto, el frontend enviaría los mismos filtros y los cards históricos mostrarían totales del periodo seleccionado.

---

## 3. Archivo `DashboardData.php`

Asegurarse de que las claves del array de request tengan valores por defecto antes de usarlas:

```php
// En lugar de:
$dateStart = $request['date_start'];
$monthStart = $request['month_start'];

// Usar:
$dateStart = $request['date_start'] ?? null;
$monthStart = $request['month_start'] ?? null;

// O valores por defecto derivados:
$dateStart = $request['date_start'] ?? now()->format('Y-m-d');
$monthStart = $request['month_start'] ?? now()->format('Y-m');
```

---

## 4. Resumen de prioridades

| Prioridad | Cambio                                         | Impacto                                      |
|-----------|-------------------------------------------------|----------------------------------------------|
| Alta      | Devolver `balance.totals.total_payment` y `balance.totals.balance` | Card "Ventas" y "Balance Neto" muestran datos |
| Alta      | Devolver `document.totals.total_to_pay`         | Card "Por Cobrar" muestra datos              |
| Media     | Valores por defecto `"0.00"` en totals          | Evita errores cuando no hay ventas           |
| Media     | Devolver `document_count` (int) en la raíz      | Card "Total Comprobantes" filtrado por periodo |
| Baja      | Usar `document.totals.total` y `sale_note.totals.total` | Cards históricos de montos según periodo     |
