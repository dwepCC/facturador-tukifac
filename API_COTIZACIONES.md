# API Cotizaciones (Registro) - Frontend Externo

Esta documentación describe el registro de **cotizaciones** usando el endpoint:

- **POST** `/api/quotations` → `Tenant\\Api\\QuotationController@store`

Ruta definida en [routes/api.php](file:///c:/xampp/htdocs/facturador-tukifac/routes/api.php#L76-L81) y lógica en:
- [QuotationController@store](file:///c:/xampp/htdocs/facturador-tukifac/app/Http/Controllers/Tenant/Api/QuotationController.php#L42-L73)
- `mergeData` (normaliza y agrega datos internos): [QuotationController::mergeData](file:///c:/xampp/htdocs/facturador-tukifac/app/Http/Controllers/Tenant/QuotationController.php#L405-L422)
- Detalle (items): [QuotationItem::$fillable](file:///c:/xampp/htdocs/facturador-tukifac/app/Models/Tenant/QuotationItem.php#L17-L56)
- Cabecera (quotation): [Quotation::$fillable](file:///c:/xampp/htdocs/facturador-tukifac/app/Models/Tenant/Quotation.php#L20-L77)
- Pagos: [savePayments](file:///c:/xampp/htdocs/facturador-tukifac/app/Http/Controllers/Tenant/QuotationController.php#L905-L916)

## Requisitos

- Base URL: `https://{tenant}.app.tukifac.pe`
- Headers:
  - `Authorization: Bearer {TOKEN}`
  - `Content-Type: application/json`
- Middleware: `auth:api` y `locked.tenant`

## 1) Crear cotización

**POST** `/api/quotations`

### Qué hace

- Si `establishment_id` viene vacío/nulo, el backend lo reemplaza por `auth()->user()->establishment_id`.
- El backend completa automáticamente:
  - `user_id` (usuario autenticado)
  - `external_id` (uuid)
  - `customer` (JSON) usando `customer_id` (y opcionalmente `customer_address_id`)
  - `establishment` (JSON) usando `establishment_id`
  - `soap_type_id` (de la compañía)
  - `state_type_id` = `"01"`
  - `terms_condition` (términos configurados)
- Crea:
  - cabecera en `quotations`
  - detalle en `quotation_items` (por `items[]`)
  - pagos en `quotation_payments` (por `payments[]`)
- Genera PDF (A4) y devuelve links.

### Payload (claves exactas)

Campos comunes de cabecera:
- `prefix` (string) ejemplo: `"COT"`
- `establishment_id` (number, opcional; si no se envía, se usa el del usuario)
- `date_of_issue` (string `YYYY-MM-DD`)
- `time_of_issue` (string `HH:mm:ss`)
- `customer_id` (number)
- `customer_address_id` (number|null, opcional)
- `currency_type_id` (string) ejemplo: `"PEN"` o `"USD"`
- `exchange_rate_sale` (number)
- `purchase_order` (string|null, opcional)
- `description` (string|null, opcional)
- `additional_information` (string|null, opcional)
- `shipping_address` (string|null, opcional)
- `account_number` (string|null, opcional)
- `contact` (string|null, opcional)
- `phone` (string|null, opcional)
- `terms_condition` (string|null, opcional; el backend también setea uno por defecto)

Totales (deben venir calculados desde tu frontend, mismo criterio que el formulario web):
- `total_prepayment` (number)
- `total_charge` (number)
- `total_discount` (number)
- `total_exportation` (number)
- `total_free` (number)
- `total_taxed` (number)
- `total_unaffected` (number)
- `total_exonerated` (number)
- `total_igv` (number)
- `total_igv_free` (number)
- `total_base_isc` (number)
- `total_isc` (number)
- `total_base_other_taxes` (number)
- `total_other_taxes` (number)
- `total_taxes` (number)
- `total_value` (number)
- `subtotal` (number)
- `total` (number)

Otros:
- `operation_type_id` (string|null, opcional)
- `date_of_due` (string|null)
- `delivery_date` (string|null)
- `payment_method_type_id` (string|null) (en form web suele venir `"10"`)
- `sale_opportunity_id` (number|null)
- `actions` (object, opcional) ejemplo: `{ "format_pdf": "a4" }`

Detalle:
- `items` (array, requerido)
  - Cada item debe respetar los nombres usados por `quotation_items`:
    - `item_id` (number)
    - `item` (object) (se guarda como JSON)
    - `quantity` (number)
    - `unit_value` (number)
    - `price_type_id` (string)
    - `unit_price` (number)
    - `affectation_igv_type_id` (string)
    - `total_base_igv` (number)
    - `percentage_igv` (number)
    - `total_igv` (number)
    - `system_isc_type_id` (string|null)
    - `total_base_isc` (number)
    - `percentage_isc` (number)
    - `total_isc` (number)
    - `total_base_other_taxes` (number)
    - `percentage_other_taxes` (number)
    - `total_other_taxes` (number)
    - `total_taxes` (number)
    - `total_value` (number)
    - `total_charge` (number)
    - `total_discount` (number)
    - `total` (number)
    - `attributes` (array|null)
    - `charges` (array|null)
    - `discounts` (array|null)
    - `additional_information` (string|null, opcional)
    - `warehouse_id` (number|null, opcional)

Pagos:
- `payments` (array, requerido por el flujo del controller; si no manejas pagos, envía `[]`)
  - Cada pago se guarda como `quotation_payments` y acepta (según el uso de pagos en el sistema):
    - `date_of_payment` (string `YYYY-MM-DD`)
    - `payment_method_type_id` (string)
    - `reference` (string|null)
    - `payment` (number)
    - `payment_destination_id` (string|null) (ej: `"cash"` o un destino)
    - Campos de tarjeta (si aplica): `has_card`, `card_brand_id`

### Ejemplo mínimo recomendado
```json
{
  "prefix": "COT",
  "establishment_id": 1,
  "date_of_issue": "2026-04-29",
  "time_of_issue": "10:00:00",
  "customer_id": 1,
  "currency_type_id": "PEN",
  "exchange_rate_sale": 1,
  "total_prepayment": 0,
  "total_charge": 0,
  "total_discount": 0,
  "total_exportation": 0,
  "total_free": 0,
  "total_taxed": 100,
  "total_unaffected": 0,
  "total_exonerated": 0,
  "total_igv": 18,
  "total_igv_free": 0,
  "total_base_isc": 0,
  "total_isc": 0,
  "total_base_other_taxes": 0,
  "total_other_taxes": 0,
  "total_taxes": 18,
  "total_value": 100,
  "subtotal": 118,
  "total": 118,
  "items": [
    {
      "item_id": 1,
      "item": {
        "id": 1,
        "description": "Producto de prueba",
        "currency_type_id": "PEN",
        "unit_type_id": "NIU"
      },
      "quantity": 1,
      "unit_value": 100,
      "price_type_id": "01",
      "unit_price": 118,
      "affectation_igv_type_id": "10",
      "total_base_igv": 100,
      "percentage_igv": 18,
      "total_igv": 18,
      "system_isc_type_id": null,
      "total_base_isc": 0,
      "percentage_isc": 0,
      "total_isc": 0,
      "total_base_other_taxes": 0,
      "percentage_other_taxes": 0,
      "total_other_taxes": 0,
      "total_taxes": 18,
      "total_value": 100,
      "total_charge": 0,
      "total_discount": 0,
      "total": 118,
      "attributes": [],
      "charges": [],
      "discounts": []
    }
  ],
  "payments": []
}
```

### Response (ejemplo)
```json
{
  "success": true,
  "data": {
    "number_full": "COT-123",
    "external_id": "xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx",
    "filename": "COT-123-20260429",
    "print_a4": "https://{tenant}.app.tukifac.pe/quotations/print/{external_id}/a4",
    "print_ticket": "https://{tenant}.app.tukifac.pe/quotations/print/{external_id}/ticket"
  }
}
```
