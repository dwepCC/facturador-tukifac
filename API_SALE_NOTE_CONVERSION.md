# Documentación de API - Conversión de Nota de Venta a CPE

Este documento detalla el endpoint para convertir una Nota de Venta existente en un Comprobante de Pago Electrónico (Factura o Boleta).

## Base URL
`/api`

## Autenticación
Header requerido:
`Authorization: Bearer {token}`

---

## Generar CPE desde Nota de Venta
Permite generar una Factura o Boleta a partir de una Nota de Venta previamente registrada. El sistema copiará automáticamente los ítems, totales y cliente de la nota de venta.

**Endpoint:** `POST /sale-note/{id}/generate-cpe`

**Parámetros de URL:**
- `id` (int): ID de la Nota de Venta a convertir.

**Body (JSON):**
```json
{
    "codigo_tipo_documento": "01",
    "serie_documento": "F001",
    "numero_documento": "#",
    "fecha_de_emision": "2023-10-27",
    "hora_de_emision": "10:30:00",
    "fecha_de_vencimiento": "2023-10-27",
    "codigo_condicion_de_pago": "01"
}
```

**Detalle de Campos del Body:**
- `codigo_tipo_documento` (string): Tipo de comprobante a generar.
    - `"01"`: Factura
    - `"03"`: Boleta
- `serie_documento` (string): Serie del nuevo comprobante (ej. "F001", "B001").
- `numero_documento` (string): Número del comprobante. Enviar `"#"` para autogenerar el correlativo.
- `fecha_de_emision` (string): Fecha de emisión en formato `YYYY-MM-DD`.
- `hora_de_emision` (string): Hora de emisión en formato `HH:mm:ss`.
- `fecha_de_vencimiento` (string): Fecha de vencimiento en formato `YYYY-MM-DD`.
- `codigo_condicion_de_pago` (string): ID de la condición de pago (ej. "01" para Contado).

**Respuesta Exitosa (200 OK):**
```json
{
    "success": true,
    "data": {
        "number": "F001-00000123",
        "filename": "20100000000-01-F001-00000123",
        "external_id": "550e8400-e29b-41d4-a716-446655440000",
        "state_type_id": "01",
        "state_type_description": "Registrado",
        "number_to_letter": "CIEN CON 00/100 SOLES",
        "hash": "rF7...",
        "qr": "..."
    },
    "links": {
        "xml": "http://dominio.com/downloads/xml/...",
        "pdf": "http://dominio.com/downloads/pdf/...",
        "cdr": "http://dominio.com/downloads/cdr/..."
    },
    "response": {
        "code": "0",
        "description": "La Factura número F001-00000123, ha sido aceptada"
    }
}
```

**Posibles Errores:**
- **404 Not Found**: Si la nota de venta no existe.
- **500 Internal Server Error**: Si la nota de venta no existe (mensaje específico: "La nota de venta asociada no existe.").
