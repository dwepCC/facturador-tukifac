# API Compras (Registro) - Frontend Externo

Esta documentación describe el **registro de compras** vía API para que un frontend externo pueda crear compras y su detalle (items).

Las rutas están definidas en [modules/Purchase/Routes/api.php](file:///c:/xampp/htdocs/facturador-tukifac/modules/Purchase/Routes/api.php) y la lógica principal en:
- [PurchaseController@store](file:///c:/xampp/htdocs/facturador-tukifac/modules/Purchase/Http/Controllers/Api/PurchaseController.php#L135-L165)
- Validación request: [PurchaseRequest](file:///c:/xampp/htdocs/facturador-tukifac/app/Http/Requests/Tenant/PurchaseRequest.php)

## Requisitos

- Base URL: `https://{tenant}.app.tukifac.pe`
- Header: `Authorization: Bearer {TOKEN}`
- Header: `Content-Type: application/json`
- Middleware: `auth:api` y `locked.tenant`

## Endpoint principal: crear compra

**POST** `/api/purchases`

### Qué hace
- Valida datos mínimos (supplier, series/number, date_of_issue, items).
- Completa automáticamente `user_id`, `establishment_id`, `external_id`, `supplier`(JSON), `soap_type_id`, `group_id`, `state_type_id` en backend.
- Crea el registro en `purchases` y los ítems en `purchase_items`.
- Genera PDF A4 y devuelve links de impresión.

### Payload (claves exactas)

Campos de cabecera (Purchase):
- `supplier_id` (required, number)
- `document_type_id` (required, string) Ej: `"01"`, `"03"`, `"GU75"`, `"NE76"`
- `series` (required, string) (en DB es `char(4)`)
- `number` (required, number)
- `date_of_issue` (required, string `YYYY-MM-DD`)
- `time_of_issue` (required por DB, string `HH:MM:SS`)
- `currency_type_id` (required por DB, string) Ej: `"PEN"`, `"USD"`
- `exchange_rate_sale` (required por DB, number) Ej: `1`
- `total` (required por DB, number)

Totales recomendados (tienen default 0 en DB, pero deberían enviarse coherentes con el detalle):
- `total_taxed`, `total_unaffected`, `total_exonerated`, `total_igv`, `total_taxes`, `total_value`
- `total_discount`, `total_charge`, `total_free`, `total_exportation`
- `total_prepayment`
- `total_base_isc`, `total_isc`, `total_base_other_taxes`, `total_other_taxes`

Otros opcionales:
- `date_of_due` (string `YYYY-MM-DD`)
- `payment_condition_id` (string)
- `observation` (string)

Detalle (items):
- `items` (required, array, mínimo 1)
  - Cada item debe incluir como mínimo:
    - `item_id` (required, number)
    - `item` (required, object) (se guarda como JSON en `purchase_items.item`)
    - `quantity` (required, number)
    - `unit_value` (required, number)
    - `price_type_id` (required, string) Ej: `"01"`
    - `unit_price` (required, number)
    - `affectation_igv_type_id` (required, string) Ej: `"10"`
    - `total_base_igv` (required, number)
    - `percentage_igv` (required, number)
    - `total_igv` (required, number)
    - `total_taxes` (required, number)
    - `total_value` (required, number)
    - `total` (required, number)
  - Opcionales comunes:
    - `warehouse_id` (number)
    - `system_isc_type_id` (string|null)
    - `total_base_isc`, `percentage_isc`, `total_isc`
    - `total_base_other_taxes`, `percentage_other_taxes`, `total_other_taxes`
    - `attributes` (array|null), `discounts` (array|null), `charges` (array|null)

### Ejemplo completo (copiar/pegar)
```json
{
  "document_type_id": "01",
  "series": "F001",
  "number": 22,
  "date_of_issue": "2026-04-28",
  "time_of_issue": "10:00:00",
  "supplier_id": 15,
  "currency_type_id": "PEN",
  "exchange_rate_sale": 1,
  "total_taxed": 100,
  "total_igv": 18,
  "total_taxes": 18,
  "total_value": 100,
  "total": 118,
  "items": [
    {
      "item_id": 1,
      "item": {
        "id": 1,
        "description": "Producto de prueba",
        "item_type_id": "01",
        "internal_id": "COD001",
        "item_code": "COD001",
        "currency_type_id": "PEN",
        "unit_type_id": "NIU",
        "presentation": [],
        "amount_plastic_bag_taxes": 0,
        "is_set": false
      },
      "quantity": 1,
      "unit_value": 100,
      "price_type_id": "01",
      "unit_price": 118,
      "affectation_igv_type_id": "10",
      "total_base_igv": 100,
      "percentage_igv": 18,
      "total_igv": 18,
      "total_taxes": 18,
      "total_value": 100,
      "total": 118,
      "attributes": [],
      "discounts": [],
      "charges": []
    }
  ]
}
```

### Response (ejemplo)
```json
{
  "success": true,
  "data": {
    "id": 123,
    "number_full": "F001-22",
    "external_id": "xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx",
    "filename": "F001-22-123-20260428",
    "print_a4": "https://{tenant}.app.tukifac.pe/purchases/print/{external_id}/a4"
  }
}
```

## Nota importante sobre pagos

El endpoint `POST /api/purchases` (Purchase API) **no registra pagos** de la compra. Solo crea la compra y sus items.

## Endpoints útiles para armar el formulario (tablas/búsquedas)

Todos bajo `/api/purchases`:
- **GET** `/api/purchases/tables` → tipos de documento permitidos para compras
- **GET** `/api/purchases/suppliers` → tabla básica de proveedores
- **GET** `/api/purchases/search-suppliers?document_type_id=01&input=...` → búsqueda de proveedores
- **GET** `/api/purchases/item-tables` → items + afectaciones IGV
- **GET** `/api/purchases/table/{table}` → tablas auxiliares (según implementación)
- **GET** `/api/purchases/records?input=...` → listado simple de compras (no paginado)
