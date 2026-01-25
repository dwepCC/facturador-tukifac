# Payload para Registro de Compras (API)

Este documento detalla la estructura JSON necesaria para registrar una Compra a través de la API.

**Endpoint:** `POST /api/purchases`  
*(Nota: Verificar la ruta exacta en `routes/api.php`, típicamente es `POST /api/purchases` o similar si está habilitado para el tenant)*

Según el controlador `Modules\Purchase\Http\Controllers\Api\PurchaseController`, el sistema espera los siguientes datos:

## Estructura del JSON

```json
{
    "series": "F001",
    "number": "22",
    "date_of_issue": "2023-10-26",
    "time_of_issue": "10:00:00",
    "supplier_id": 15,
    "currency_type_id": "PEN",
    "document_type_id": "01",
    "exchange_rate_sale": 1,
    "total_prepayment": 0,
    "total_discount": 0,
    "total_charge": 0,
    "total_exportation": 0,
    "total_free": 0,
    "total_taxed": 100,
    "total_unaffected": 0,
    "total_exonerated": 0,
    "total_igv": 18,
    "total_base_isc": 0,
    "total_isc": 0,
    "total_base_other_taxes": 0,
    "total_other_taxes": 0,
    "total_taxes": 18,
    "total_value": 100,
    "total": 118,
    "total_perception": 0,
    "observation": "Observación de prueba",
    "items": [
        {
            "item_id": 1,
            "item": {
                "id": 1,
                "description": "Producto de prueba",
                "item_type_id": "01",
                "internal_id": "COD001",
                "item_code": "COD001",
                "item_code_gsl": null,
                "currency_type_id": "PEN",
                "unit_type_id": "NIU",
                "presentation": [],
                "amount_plastic_bag_taxes": 0,
                "is_set": false,
                "lots_enabled": false
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
            "total": 118,
            "attributes": [],
            "discounts": [],
            "charges": [],
            "warehouse_id": 1
        }
    ]
}
```

> **Nota Importante:** El endpoint actual (`Api\PurchaseController@store`) **NO procesa pagos** (`payments`). La compra se registrará como pendiente de pago.

## Descripción de Campos Principales

### Cabecera (Purchase)
*   `document_type_id`: Tipo de documento (01: Factura, 03: Boleta, GU75: Guía, etc.).
*   `series`: Serie del documento físico/electrónico del proveedor.
*   `number`: Número del documento.
*   `date_of_issue`: Fecha de emisión (YYYY-MM-DD).
*   `time_of_issue`: Hora de emisión (HH:MM:SS).
*   `supplier_id`: ID del proveedor registrado en el sistema (Tabla `persons`).
*   `currency_type_id`: Moneda (PEN, USD).
*   `total_taxed`: Total Operaciones Gravadas (Base imponible).
*   `total_igv`: Total IGV.
*   `total`: Importe Total.

### Items (Detalle)
*   `item_id`: ID del producto en el sistema.
*   `quantity`: Cantidad.
*   `unit_value`: Valor unitario (sin impuestos).
*   `unit_price`: Precio unitario (con impuestos).
*   `affectation_igv_type_id`: Tipo de afectación al IGV (ej. 10 para Gravado - Operación Onerosa).
*   `warehouse_id`: (Opcional) ID del almacén destino.

### Pagos (Payments) - Opcional
Si se desea registrar el pago junto con la compra:
*   `date_of_payment`: Fecha de pago.
*   `payment_method_type_id`: Método de pago (01: Efectivo, etc.).
*   `payment`: Monto pagado.

---
**Nota:** El sistema completará automáticamente campos como `user_id`, `establishment_id`, `soap_type_id` y `state_type_id` basándose en el usuario autenticado y la configuración del tenant.
