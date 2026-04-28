# API Caja (Restaurante)

Este documento describe los endpoints de **gestión de caja** usados por frontends externos (restaurante/app) para:
- aperturar/verificar caja
- listar cajas abiertas
- vincular ventas (Factura/Boleta y Nota de venta) a una caja
- cerrar caja

Base: rutas definidas en [routes/api.php](file:///c:/xampp/htdocs/facturador-tukifac/routes/api.php) y lógica en:
- [Tenant\\Api\\CashController](file:///c:/xampp/htdocs/facturador-tukifac/app/Http/Controllers/Tenant/Api/CashController.php)
- [Tenant\\CashController](file:///c:/xampp/htdocs/facturador-tukifac/app/Http/Controllers/Tenant/CashController.php)

## Requisitos generales

- Autenticación: `Authorization: Bearer <TOKEN>`
- Middleware: `auth:api` y `locked.tenant` (aplica a estos endpoints)
- Content-Type (POST): `application/json`

## 1) Verificar si el usuario tiene caja abierta

**GET** `/api/cash/opening_cash`

### Payload
No lleva body.

### Response (ejemplo)
```json
{
  "success": true,
  "message": "Verificar si existe caja abierta",
  "data": {
    "cash_id": 88,
    "description": "REF 2026-04-28 (demo1)"
  }
}
```

Notas:
- `success=true` significa que existe caja abierta para el usuario autenticado (busca `state=true`).
- `cash_id` puede ser `null` si no hay caja abierta.

## 2) Verificar una caja abierta por id

**GET** `/api/cash/opening_cash_check/{cash_id}`

### Path params
- `cash_id` (number)

### Payload
No lleva body.

### Response
Mismo formato que `opening_cash`.

## 3) Listar cajas abiertas (disponibles) para seleccionar

**GET** `/api/cash/available-restaurant`

Este endpoint lista cajas con `state=true`.

### Payload
No lleva body.

### Response (ejemplo)
```json
{
  "success": true,
  "message": "Cajas disponibles",
  "data": [
    {
      "id": 88,
      "user_id": 5,
      "description": " 2026-04-28 (demo1)"
    }
  ]
}
```

Campos devueltos por cada caja:
- `id` (number): id de la caja
- `user_id` (number): id del usuario dueño de la caja
- `description` (string): descripción armada por backend

## 4) Aperturar caja (caja viva)

**POST** `/api/cash/open`

Este endpoint crea/actualiza una caja. En uso normal de “apertura”, se envía sin `id` para crear una nueva.

### Payload (claves exactas)
- `beginning_balance` (required, numeric, min 0)
- `id` (optional) si se desea actualizar una caja existente
- `user_id` (optional) si se envía `0`, el backend lo reemplaza por el usuario autenticado
- `state` (opcional pero recomendado): `1` para aperturar (el modelo usa `state` como indicador de aperturada)
- `reference_number` (optional): referencia de caja
- `apply_restaurant` (optional): `1` si quieres marcar que la caja corresponde a restaurante

### Response (ejemplo)
```json
{
  "success": true,
  "message": "Caja aperturada con éxito",
  "data": {
    "cash_id": 101
  }
}
```

## 5) Registrar caja “Restaurante” por lote (crea y deja calculada)

**POST** `/api/cash/restaurant`

Este endpoint crea una caja y asocia documentos/notas por `external_id`, acumulando el total. Se usa como “caja creada con documentos”, no como caja viva para ir asociando venta a venta.

### Payload (claves exactas)
- `beginningBalance` (number)
- `dateOpening` (string) formato `"Y-m-d"`
- `timeOpening` (string) formato `"H:m:s"`
- `referenceNumber` (string | null)
- `internalsId` (array) donde cada elemento es:
  - `external_id` (string)
  - `type` (string) valores esperados:
    - `"NOTA"` para nota de venta
    - cualquier otro valor para Factura/Boleta (document)

### Response
```json
{
  "success": true,
  "message": "Caja creada con éxito"
}
```

## 6) Vincular una venta a la caja abierta (Factura/Boleta o Nota de venta)

**POST** `/api/cash/cash_document`

El backend busca la caja abierta del usuario autenticado (`state=true`) y crea/actualiza el vínculo en `cash_documents`. También crea los registros de pagos en `cash_document_payments`.

### Payload (claves exactas)
Para Factura/Boleta:
- `document_id` (number)
- `sale_note_id` (null)
- `quotation_id` (optional)

Para Nota de venta:
- `document_id` (null)
- `sale_note_id` (number)
- `quotation_id` (optional)

Ejemplo (documento):
```json
{
  "document_id": 123,
  "sale_note_id": null,
  "quotation_id": null
}
```

### Response
```json
{
  "success": true,
  "message": "Venta con éxito"
}
```

## 7) Listar registros de caja

**GET** `/api/cash/records`

### Query params (según implementación)
- `column` (string)
- `value` (string)

Notas:
- Si `column == "user"`, filtra por nombre de usuario.
- Ordena por `date_opening` desc y `time_opening` desc.

## 8) Cerrar caja

**GET** `/api/cash/close/{cash}`

### Importante
- El backend **NO usa** el `{cash}` enviado en la URL.
- Cierra la caja que cumpla: `user_id = auth()->user()->id` y `state = 1`.
- No requiere body.

### Response
```json
{
  "success": true,
  "message": "Caja cerrada con éxito"
}
```

Si no existe caja abierta para el usuario:
```json
{
  "success": false,
  "message": "Caja no encontrada"
}
```
