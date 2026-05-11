# Payload exacto: facturas (CPE) y notas de venta (API tenant)

Referencia alineada con el código actual:

- Facturas / boletas / notas CPE: `POST /api/documents` → middleware `input.request:document,api` → `DocumentTransform` → `DocumentValidation` → `DocumentInput::set` → `Facturalo::save`.
- Notas de venta: `POST /api/sale-note` → `Tenant\Api\SaleNoteController@store` → `mergeData` (sin `DocumentTransform`; el cuerpo sigue el formato interno tipo **Vue / web**, claves en **inglés** salvo lo indicado).

Base URL: dominio del tenant (mismo host que usa el panel) + prefijo `/api` según tu `routes/api.php`.

---

## 1. `POST /api/documents` — claves en español (cuerpo JSON)

El cliente debe enviar **las claves en español** listadas abajo. `DocumentTransform` las convierte a la estructura interna antes de validar y guardar.

### 1.1 Raíz del documento (campos que lee `DocumentTransform`)

| Clave JSON (API) | Obligatoriedad | Notas |
|------------------|----------------|-------|
| `serie_documento` | Sí | Debe existir en `series` para el `codigo_tipo_documento` y establecimiento (`DocumentValidation` → `Functions::validateSeries`). |
| `numero_documento` | Sí | Puede ser `"#"` para correlativo automático según flujo interno. |
| `fecha_de_emision` | Sí | Formato fecha. |
| `hora_de_emision` | Recomendado | Se mapea a `time_of_issue`. |
| `codigo_tipo_documento` | Sí | Ej.: `01` factura, `03` boleta, `07` nota crédito, `08` nota débito. |
| `codigo_tipo_moneda` | Sí | Ej.: `PEN`, `USD`. |
| `factor_tipo_de_cambio` | Opcional | Default `1`. |
| `datos_del_cliente_o_receptor` | Sí | Objeto; ver §1.2. |
| `totales` | Sí | Objeto; ver §1.3. |
| `items` | Sí | Arreglo; ver §1.4. |
| `pagos` | Obligatorio para `01` y `03` en la práctica | Si falta, `payments` queda `[]` y `Facturalo::save` no registrará pagos (riesgo operativo / validaciones de caja). Ver §1.7. |
| `codigo_tipo_operacion` | Sí si `codigo_tipo_documento` ∈ `01`,`03` | Ej. `0101`. |
| `fecha_de_vencimiento` | Opcional | Solo factura/boleta. |
| `leyendas` | Opcional | Arreglo `{ codigo, valor }`. |
| `acciones` | Opcional | `enviar_email`, `enviar_xml_firmado`, `formato_pdf`. |
| `informacion_adicional` | Opcional | |
| `dato_adicional` | Opcional | |
| `anticipos`, `guias`, `relacionados`, `cargos`, `descuentos`, `detraccion`, `retencion`, `percepcion`, `cuotas`, `hotel`, `transport` | Opcional | Misma forma que en `DocumentTransform`. |
| `documento_afectado`, `codigo_tipo_nota`, `motivo_o_sustento_de_nota` | Notas `07`/`08` | Ver §1.7. |
| `pago_anticipado`, `es_itinerante`, `codigo_consignado`, … | Opcional | Ver `DocumentTransform.php`. |

### 1.2 `datos_del_cliente_o_receptor` → `PersonTransform`

Claves exactas leídas por el transformador:

- `codigo_tipo_documento_identidad` (requerido en la práctica)
- `numero_documento`
- `apellidos_y_nombres_o_razon_social`
- `nombre_comercial` (opcional)
- `codigo_pais` (opcional)
- `ubigeo` (opcional)
- `direccion` (opcional)
- `correo_electronico` (opcional)
- `telefono` (opcional)
- `codigo_tipo_direccion` (opcional)

### 1.3 `totales` — claves leídas por `DocumentTransform`

El transform usa **siempre** la clave `totales` como objeto. Campos reconocidos:

- `total_anticipos`, `total_descuentos`, `total_cargos`, `total_exportacion`, `total_operaciones_gratuitas`, `total_operaciones_gravadas`, `total_operaciones_inafectas`, `total_operaciones_exoneradas`, `total_igv`, `total_igv_operaciones_gratuitas`, `total_base_isc`, `total_isc`, `total_base_otros_impuestos`, `total_otros_impuestos`, `total_impuestos_bolsa_plastica`, `total_impuestos`, `total_valor`, `subtotal_venta` (si no existe, usa `total_venta` para `subtotal`), `total_venta`, `total_pendiente_pago`.

### 1.4 `items[]` — claves por línea (`DocumentTransform::items`)

En el JSON de entrada cada elemento debe incluir al menos lo que el transform usa sin `valueKeyInArray` (obligatorio en código):

- **`descripcion`** — obligatorio (acceso directo `$row['descripcion']`).
- **`unidad_de_medida`** — obligatorio (se pasa a `strtoupper`).

Resto mapeado en `DocumentTransform` (muchas con default vía `Functions::valueKeyInArray`):

| Clave API | Interno |
|-----------|---------|
| `codigo_interno` | `internal_id` (vacío si no se envía) |
| `nombre`, `nombre_secundario` | opcionales |
| `codigo_tipo_item` | default `01` |
| `codigo_producto_sunat`, `codigo_producto_gsl` | opcionales |
| `cantidad`, `valor_unitario`, `codigo_tipo_precio`, `precio_unitario` | línea |
| `codigo_tipo_afectacion_igv`, totales IGV/ISC/otros | línea |
| `total_impuestos`, `total_valor_item`, `total_cargos`, `total_descuentos`, `total_item` | línea |
| `datos_adicionales` | arreglo `{ codigo, descripcion, valor?, fecha_inicio?, fecha_fin?, duracion? }` |
| `descuentos`, `cargos` | a nivel ítem (misma forma que documento) |
| `informacion_adicional`, `lots`, `actualizar_descripcion` (default **true** en transform), `nombre_producto_pdf`, `nombre_producto_xml`, `dato_adicional`, `esFusionado` | opcionales |

### 1.5 Producto manual (`VARIOUS_ITEM`) vía `POST /api/documents`

**Comportamiento actual del backend:** Tras `DocumentTransform`, `App\CoreFacturalo\Requests\Api\Validation\DocumentValidation` detecta si el ítem resuelto tiene **`barcode === 'VARIOUS_ITEM'`** o descripción maestra **`REPLACE DESCRIPTION`**. En ese caso **antes** de borrar campos de línea copia a **`$row['item']`** la **`descripcion`** y la **`unidad_de_medida`** (como `unit_type_id`), para que `DocumentInput` arme el XML/PDF igual que la vista web.

No hace falta enviar un objeto `item` anidado en JSON: basta **`codigo_interno`** apuntando al comodín (mismo valor que `internal_id` en BD), **`descripcion`** con el texto de venta y **`unidad_de_medida`** (ej. `NIU`).

**Catálogo:** `Functions::item()` puede actualizar la descripción del maestro si **`actualizar_descripcion`** es **true** (default del transform). Para no sobrescribir el comodín en catálogo, envía **`"actualizar_descripcion": false`** en la línea.

---

### 1.6 Ejemplo **exacto** — Boleta `03` gravada (`POST /api/documents`)

Cabecera **`Authorization: Bearer {token}`**, **`Content-Type: application/json`**. El cuerpo debe usar **solo las claves en español** que consume `DocumentTransform` (no mezclar `series_id`, `customer_id` en inglés para este endpoint: el establecimiento lo toma el usuario autenticado; el cliente se arma desde `datos_del_cliente_o_receptor`).

`datos_del_cliente_o_receptor`: usar **`ubigeo`**, no `codigo_ubigeo` (así lo lee `PersonTransform`).

```json
{
  "serie_documento": "B002",
  "numero_documento": "#",
  "fecha_de_emision": "2026-05-10",
  "hora_de_emision": "22:59:01",
  "codigo_tipo_documento": "03",
  "codigo_tipo_moneda": "PEN",
  "factor_tipo_de_cambio": 1,
  "codigo_tipo_operacion": "0101",
  "fecha_de_vencimiento": "2026-05-10",
  "platform": "tukichef",
  "codigo_condicion_de_pago": "01",

  "datos_del_cliente_o_receptor": {
    "codigo_tipo_documento_identidad": "1",
    "numero_documento": "99999999",
    "apellidos_y_nombres_o_razon_social": "CLIENTE VARIOS",
    "nombre_comercial": "",
    "codigo_pais": "PE",
    "ubigeo": "040101",
    "direccion": "DIRECCION CLIENTE",
    "correo_electronico": "correo@ejemplo.com",
    "telefono": "999999999",
    "codigo_tipo_direccion": null
  },

  "totales": {
    "total_anticipos": 0,
    "total_descuentos": 0,
    "total_cargos": 0,
    "total_exportacion": 0,
    "total_operaciones_gratuitas": 0,
    "total_operaciones_gravadas": 84.75,
    "total_operaciones_inafectas": 0,
    "total_operaciones_exoneradas": 0,
    "total_igv": 15.25,
    "total_igv_operaciones_gratuitas": 0,
    "total_base_isc": 0,
    "total_isc": 0,
    "total_base_otros_impuestos": 0,
    "total_otros_impuestos": 0,
    "total_impuestos_bolsa_plastica": 0,
    "total_impuestos": 15.25,
    "total_valor": 84.75,
    "subtotal_venta": 100,
    "total_venta": 100,
    "total_pendiente_pago": 0
  },

  "items": [
    {
      "codigo_interno": "SKU-DEMO-001",
      "descripcion": "PRODUCTO EJEMPLO",
      "nombre": null,
      "nombre_secundario": null,
      "codigo_tipo_item": "01",
      "codigo_producto_sunat": "10000000",
      "codigo_producto_gsl": null,
      "unidad_de_medida": "NIU",
      "cantidad": 1,
      "valor_unitario": 84.75,
      "codigo_tipo_precio": "01",
      "precio_unitario": 100,
      "codigo_tipo_afectacion_igv": "10",
      "total_base_igv": 84.75,
      "porcentaje_igv": 18,
      "total_igv": 15.25,
      "codigo_tipo_sistema_isc": null,
      "total_base_isc": 0,
      "porcentaje_isc": 0,
      "total_isc": 0,
      "total_base_otros_impuestos": 0,
      "porcentaje_otros_impuestos": 0,
      "total_otros_impuestos": 0,
      "total_impuestos_bolsa_plastica": 0,
      "total_impuestos": 15.25,
      "total_valor_item": 84.75,
      "total_cargos": 0,
      "total_descuentos": 0,
      "total_item": 100,
      "datos_adicionales": [],
      "descuentos": [],
      "cargos": [],
      "informacion_adicional": null,
      "lots": [],
      "actualizar_descripcion": false,
      "nombre_producto_pdf": null,
      "nombre_producto_xml": null,
      "dato_adicional": null,
      "esFusionado": false
    }
  ],

  "pagos": [
    {
      "codigo_metodo_pago": "01",
      "codigo_destino_pago": "cash",
      "referencia": null,
      "monto": 100,
      "pago_recibido": null
    }
  ],

  "leyendas": [
    { "codigo": "1000", "valor": "CIEN CON 00/100 SOLES" }
  ],

  "acciones": {
    "enviar_email": false,
    "enviar_xml_firmado": true,
    "formato_pdf": "a4"
  }
}
```

Sustituye `serie_documento`, totales e ítem por los válidos en tu tenant. Los importes del ejemplo son gravados IGV 18 % sobre valor 84.75 + IGV 15.25 = 100.

### 1.7 `pagos[]` — factura/boleta (`01`, `03`)

Solo se transforman si `codigo_tipo_documento` es `01` o `03`. Cada fila:

| Clave API | Interno |
|-----------|---------|
| `codigo_metodo_pago` | `payment_method_type_id` |
| `codigo_destino_pago` | `payment_destination_id` |
| `referencia` | opcional |
| `monto` | `payment` (default `0`) |
| `pago_recibido` | opcional |

`date_of_payment` en interno se rellena con `fecha_de_emision` del documento.

### 1.8 Notas crédito/débito (`07`, `08`)

Además de lo común:

- `codigo_tipo_nota`, `motivo_o_sustento_de_nota` (opcionales en transform pero necesarios fiscalmente).
- `documento_afectado` con **una** de:
  - `external_id` del documento afectado, o
  - `numero_documento`, `serie_documento`, `codigo_tipo_documento` (cuando no hay `external_id`).

### 1.9 Ejemplo mínimo válido — Factura `01` gravada PEN (con `pagos`)

Los importes deben ser coherentes con tu política de precios (valor + IGV). Ajusta `codigo_metodo_pago`, `codigo_destino_pago` y series a tu catálogo.

```json
{
  "serie_documento": "F001",
  "numero_documento": "#",
  "fecha_de_emision": "2026-05-10",
  "hora_de_emision": "12:00:00",
  "codigo_tipo_documento": "01",
  "codigo_tipo_moneda": "PEN",
  "factor_tipo_de_cambio": 1,
  "codigo_tipo_operacion": "0101",
  "fecha_de_vencimiento": "2026-05-10",

  "datos_del_cliente_o_receptor": {
    "codigo_tipo_documento_identidad": "6",
    "numero_documento": "20123456789",
    "apellidos_y_nombres_o_razon_social": "CLIENTE SAC",
    "codigo_pais": "PE",
    "ubigeo": "150101",
    "direccion": "AV. EJEMPLO 123",
    "correo_electronico": "factura@cliente.com",
    "telefono": "999999999"
  },

  "totales": {
    "total_anticipos": 0,
    "total_descuentos": 0,
    "total_cargos": 0,
    "total_exportacion": 0,
    "total_operaciones_gratuitas": 0,
    "total_operaciones_gravadas": 84.75,
    "total_operaciones_inafectas": 0,
    "total_operaciones_exoneradas": 0,
    "total_igv": 15.25,
    "total_igv_operaciones_gratuitas": 0,
    "total_base_isc": 0,
    "total_isc": 0,
    "total_base_otros_impuestos": 0,
    "total_otros_impuestos": 0,
    "total_impuestos_bolsa_plastica": 0,
    "total_impuestos": 15.25,
    "total_valor": 84.75,
    "subtotal_venta": 100,
    "total_venta": 100,
    "total_pendiente_pago": 0
  },

  "items": [
    {
      "codigo_interno": "MANUAL-001",
      "descripcion": "Servicio / producto manual API",
      "codigo_tipo_item": "01",
      "codigo_producto_sunat": "90111601",
      "unidad_de_medida": "NIU",
      "cantidad": 1,
      "valor_unitario": 84.75,
      "codigo_tipo_precio": "01",
      "precio_unitario": 100,
      "codigo_tipo_afectacion_igv": "10",
      "total_base_igv": 84.75,
      "porcentaje_igv": 18,
      "total_igv": 15.25,
      "total_impuestos": 15.25,
      "total_valor_item": 84.75,
      "total_item": 100,
      "actualizar_descripcion": true
    }
  ],

  "pagos": [
    {
      "codigo_metodo_pago": "01",
      "codigo_destino_pago": "cash",
      "monto": 100,
      "referencia": null,
      "pago_recibido": null
    }
  ],

  "leyendas": [
    { "codigo": "1000", "valor": "CIEN CON 00/100 SOLES" }
  ],

  "acciones": {
    "enviar_email": false,
    "enviar_xml_firmado": true,
    "formato_pdf": "a4"
  }
}
```

---

## 2. `POST /api/sale-note` — claves en inglés (cuerpo JSON)

No pasa por `DocumentTransform`. El controlador API usa el mismo shape general que envía el formulario web: **snake_case en inglés** para cabecera, ítems y pagos.

### 2.1 Cabecera usada por `mergeData` / `updateOrCreate`

Campos habituales (deben existir en el request los que tu flujo use; los críticos para el modelo y series):

| Clave | Uso |
|-------|-----|
| `id` | `null` o ID de la nota a actualizar. |
| `series_id` | **Requerido** — PK en tabla `series` (API resuelve `series`/`number`). |
| `number` | `null` en alta para correlativo. |
| `date_of_issue`, `time_of_issue` | Fecha/hora emisión. |
| `customer_id` | Cliente existente **salvo** que uses `force_create_if_not_exist` (ver abajo). |
| `establishment_id` | Si viene vacío, en `store` se usa el del usuario. |
| `currency_type_id`, `exchange_rate_sale` | Moneda y tipo de cambio. |
| `type_period`, `quantity_period` | Suscripción / periodicidad (puede ser `null` / `0`). |
| Totales `total_*`, `total` | Deben coincidir con la suma de líneas según reglas de negocio. |
| `payments` | Arreglo de pagos (misma lógica que controlador web si aplica). |
| `force_create_if_not_exist` | `bool` — ver §2.3. |

### 2.2 Ítem cuando `force_create_if_not_exist` es **false**

Debes enviar líneas ya compatibles con `SaleNoteItem` + objeto anidado `item` (se persiste JSON). Ejemplo de claves alineadas con `SaleNoteItem::$fillable` y uso en `store`:

- `id` — ID del **sale_note_item** (null en línea nueva).
- `item_id` — ID del producto en `items`.
- `item` — objeto con al menos `id`, `description`, `unit_type_id`, `has_igv` (como en el front).
- `quantity`, `unit_value`, `unit_price`, `price_type_id`, `affectation_igv_type_id`, bases y totales de impuestos, `total`, `attributes`, `charges`, `discounts`, etc.

### 2.3 Ítem cuando `force_create_if_not_exist` es **true**

`mergeData`:

1. Opcionalmente crea/ajusta **persona** usando `datos_del_cliente_o_receptor` con claves: `codigo_tipo_documento_identidad`, `numero_documento`, `apellidos_y_nombres_o_razon_social`, `codigo_pais`, `ubigeo`, `direccion`, `correo_electronico`, `telefono`.
2. Por cada ítem:
   - Si viene `full_item`: limpia campos relacionales, busca `Item::where($item_in)->first()` o crea `new Item($item_in)`.
   - Si no: toma el array del ítem, añade `sale_unit_price` desde `unit_price`, tipos de afectación, busca por `internal_id` o crea `new Item($item_in)`.
3. En ambos casos rellena en la línea: `id` (producto), `item_id`, `barcode`, y `item` con `barcode`, `id`, `item_id`, `is_set`, `unit_type_id`, `description` del maestro.

Ejemplo mínimo de línea **manual** autocreada por `internal_id` (ajusta campos obligatorios del modelo `Item` en tu tenant si la migración exige más columnas):

```json
{
  "internal_id": "NV-MANUAL-001",
  "description": "Producto creado desde NV API",
  "unit_type_id": "NIU",
  "currency_type_id": "PEN",
  "unit_price": 100,
  "unit_value": 84.75,
  "quantity": 1,
  "affectation_igv_type_id": "10",
  "total_base_igv": 84.75,
  "percentage_igv": 18,
  "total_igv": 15.25,
  "total_taxes": 15.25,
  "total_value": 84.75,
  "total": 100,
  "price_type_id": "01",
  "item": {
    "description": "Producto creado desde NV API",
    "unit_type_id": "NIU",
    "has_igv": true
  }
}
```

### 2.4 Ejemplo de cuerpo completo NV con autocreación

```json
{
  "id": null,
  "series_id": 1,
  "number": null,
  "date_of_issue": "2026-05-10",
  "time_of_issue": "12:00:00",
  "customer_id": 0,
  "datos_del_cliente_o_receptor": {
    "codigo_tipo_documento_identidad": "6",
    "numero_documento": "20123456789",
    "apellidos_y_nombres_o_razon_social": "CLIENTE SAC",
    "codigo_pais": "PE",
    "ubigeo": "150101",
    "direccion": "AV. EJEMPLO 123",
    "correo_electronico": "nv@cliente.com",
    "telefono": "999999999"
  },
  "establishment_id": 1,
  "currency_type_id": "PEN",
  "exchange_rate_sale": 1,
  "type_period": null,
  "quantity_period": 0,
  "total_taxed": 84.75,
  "total_igv": 15.25,
  "total_taxes": 15.25,
  "total_value": 84.75,
  "total": 100,
  "items": [
    {
      "internal_id": "NV-MANUAL-001",
      "description": "Producto manual",
      "unit_type_id": "NIU",
      "currency_type_id": "PEN",
      "unit_price": 100,
      "unit_value": 84.75,
      "quantity": 1,
      "affectation_igv_type_id": "10",
      "total_base_igv": 84.75,
      "percentage_igv": 18,
      "total_igv": 15.25,
      "total_taxes": 15.25,
      "total_value": 84.75,
      "total": 100,
      "price_type_id": "01",
      "item": {
        "description": "Producto manual",
        "unit_type_id": "NIU",
        "has_igv": true
      }
    }
  ],
  "payments": [],
  "force_create_if_not_exist": true
}
```

---

## 3. Archivos de código de referencia

- Transform + claves españolas documento: `app/CoreFacturalo/Requests/Api/Transform/DocumentTransform.php`
- Cliente / leyendas: `.../Transform/Common/PersonTransform.php`, `LegendTransform.php`, `ActionTransform.php`
- Validación + `item_id`: `app/CoreFacturalo/Requests/Api/Validation/DocumentValidation.php`, `.../Validation/Functions.php` (`item`, `validateSeries`)
- Entrada final documento: `app/CoreFacturalo/Requests/Inputs/DocumentInput.php`
- NV API: `app/Http/Controllers/Tenant/Api/SaleNoteController.php` (`store`, `mergeData`)
- Detalle NV: `app/Models/Tenant/SaleNoteItem.php` (`$fillable`)

Documentación relacionada en el repo: `PAYLOADS_API_DOCUMENTS_Y_SALE_NOTE.md` (ejemplos ampliados); este archivo concentra la **lista de claves exactas** y el contraste **español (documentos)** vs **inglés (NV)**.
