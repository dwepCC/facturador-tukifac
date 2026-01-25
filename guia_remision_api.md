# Payloads para Guías de Remisión (API)

Este documento detalla los payloads JSON necesarios para registrar Guías de Remisión Remitente (09) y Guías de Remisión Transportista (31) a través de la API.

## 1. Guía de Remisión Remitente (Tipo 09)
Esta guía la emite el propietario de la mercadería.

**Endpoint:** `POST /api/dispatches`

```json
{
    "serie_documento": "T001",
    "numero_documento": "#", 
    "fecha_de_emision": "2023-10-26",
    "hora_de_emision": "10:00:00",
    "codigo_tipo_documento": "09",
    "datos_del_emisor": {
        "codigo_pais": "PE",
        "ubigeo": "150101",
        "direccion": "Av. Principal 123",
        "correo_electronico": "empresa@test.com",
        "telefono": "123456789",
        "codigo_del_domicilio_fiscal": "0000"
    },
    "datos_del_cliente_o_receptor": {
        "codigo_tipo_documento_identidad": "6",
        "numero_documento": "20600000001",
        "apellidos_y_nombres_o_razon_social": "CLIENTE DESTINATARIO SAC",
        "codigo_pais": "PE",
        "ubigeo": "150101",
        "direccion": "Av. Destino 456",
        "correo_electronico": "cliente@test.com",
        "telefono": "987654321"
    },
    "observaciones": "Guía de prueba remitente",
    "codigo_modo_transporte": "02", 
    "codigo_motivo_traslado": "01", 
    "descripcion_motivo_traslado": "Venta",
    "fecha_de_traslado": "2023-10-27",
    "unidad_peso_total": "KGM",
    "peso_total": 50.00,
    "numero_de_bultos": 5,
    "direccion_partida": {
        "ubigeo": "150101",
        "direccion": "Av. Almacén Partida 123",
        "codigo_del_domicilio_fiscal": "0000"
    },
    "direccion_llegada": {
        "ubigeo": "150102",
        "direccion": "Av. Almacén Llegada 456",
        "codigo_del_domicilio_fiscal": "0000"
    },
    "chofer": {
        "codigo_tipo_documento_identidad": "1",
        "numero_documento": "40000001",
        "nombres": "JUAN PEREZ (CHOFER)",
        "numero_licencia": "H123456",
        "telefono": "999888777"
    },
    "vehiculo": {
        "numero_de_placa": "ABC-123",
        "modelo": "HILUX",
        "marca": "TOYOTA"
    },
    "items": [
        {
            "codigo_interno": "PROD001",
            "descripcion": "PRODUCTO DE PRUEBA",
            "codigo_producto_sunat": "10000000",
            "unidad_de_medida": "NIU",
            "cantidad": 10
        }
    ]
}
```

**Nota sobre transporte:**
*   Si `codigo_modo_transporte` es **"02" (Privado)**: Envía `chofer` y `vehiculo`.
*   Si `codigo_modo_transporte` es **"01" (Público)**: Envía `transportista` (datos de la empresa de transporte) y no envíes chofer/vehículo.

---

## 2. Guía de Remisión Transportista (Tipo 31)
Esta guía la emite la empresa de transporte.

```json
{
    "serie_documento": "V001",
    "numero_documento": "#",
    "fecha_de_emision": "2023-10-26",
    "hora_de_emision": "10:00:00",
    "codigo_tipo_documento": "31",
    "datos_del_emisor": {
        "codigo_pais": "PE",
        "ubigeo": "150101",
        "direccion": "Av. Transportista 123",
        "correo_electronico": "transporte@test.com",
        "telefono": "123456789",
        "codigo_del_domicilio_fiscal": "0000"
    },
    "observaciones": "Guía transportista prueba",
    "fecha_de_traslado": "2023-10-27",
    "unidad_peso_total": "KGM",
    "peso_total": 100.00,
    "numero_de_bultos": 10,
    "datos_remitente": {
        "codigo_tipo_documento_identidad": "6",
        "numero_documento": "20100000001",
        "apellidos_y_nombres_o_razon_social": "EMPRESA REMITENTE SAC"
    },
    "datos_destinatario": {
        "codigo_tipo_documento_identidad": "6",
        "numero_documento": "20200000002",
        "apellidos_y_nombres_o_razon_social": "EMPRESA DESTINATARIA SAC"
    },
    "direcciones_proveedores": {
        "remitente": {
            "ubigeo": "150101",
            "direccion": "Av. Origen Carga 123"
        },
        "destinatario": {
            "ubigeo": "150102",
            "direccion": "Av. Destino Carga 456"
        }
    },
    "direccion_partida": {
        "ubigeo": "150101",
        "direccion": "Av. Origen Carga 123",
        "codigo_del_domicilio_fiscal": "0000"
    },
    "direccion_llegada": {
        "ubigeo": "150102",
        "direccion": "Av. Destino Carga 456",
        "codigo_del_domicilio_fiscal": "0000"
    },
    "chofer": {
        "codigo_tipo_documento_identidad": "1",
        "numero_documento": "40000001",
        "nombres": "JUAN PEREZ (CHOFER)",
        "numero_licencia": "H123456",
        "telefono": "999888777"
    },
    "vehiculo": {
        "numero_de_placa": "ABC-123",
        "modelo": "VOLVO",
        "marca": "FH16",
        "certificado_habilitacion_vehicular": "TUC123456"
    },
    "pagador_flete": {
        "indicador_pagador_flete": "Remitente", 
        "codigo_tipo_documento_identidad": "6",
        "numero": "20100000001",
        "nombres": "EMPRESA REMITENTE SAC"
    },
    "items": [
        {
            "codigo_interno": "ITEM001",
            "descripcion": "CAJAS DE PRODUCTOS",
            "unidad_de_medida": "NIU",
            "cantidad": 10
        }
    ]
}
```

## Diferencias Clave

1.  **Tipo de Documento**: `09` (Remitente) vs `31` (Transportista).
2.  **Actores**:
    *   **Remitente (09)**: Usa `datos_del_cliente_o_receptor` para el destinatario.
    *   **Transportista (31)**: Usa `datos_remitente` (quien envía la carga) y `datos_destinatario` (quien recibe). Además, requiere `direcciones_proveedores` para detallar las direcciones de estos actores.
3.  **Pagador**: La guía transportista permite especificar quién paga el flete en `pagador_flete`.
