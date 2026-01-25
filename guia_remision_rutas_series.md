# Rutas y Series para Guías de Remisión (Complemento)

Este documento complementa a `guia_remision_api.md` detallando los endpoints necesarios y cómo obtener las series para guías de remisión (09 y 31) desde un frontend externo.

## 1. Endpoints de Registro

Para registrar las guías, se utilizan las siguientes rutas. Es importante notar que la **Guía Transportista (31)** depende de un módulo adicional.

### A. Guía de Remisión Remitente (Tipo 09)
*   **Método:** `POST`
*   **URL:** `/api/dispatches`
*   **Controlador:** `Tenant\Api\DispatchController@store`
*   **Ubicación de la ruta:** `routes/api.php`

### B. Guía de Remisión Transportista (Tipo 31)
*   **Método:** `POST`
*   **URL:** `/api/dispatch-carrier`
*   **Controlador:** `Api\DispatchCarrierController@store`
*   **Ubicación de la ruta:** `modules/Dispatch/Routes/api.php`
    *   **Nota Importante:** El archivo `c:\laragon\www\local.tukifac\modules\Dispatch\Routes\api.php` **ES ESTRICTAMENTE NECESARIO** para que funcione este endpoint. Este archivo define el grupo de rutas `dispatch-carrier`. Si se elimina o deshabilita, no podrás registrar guías transportistas por API.

## 2. Obtención de Series

Para enviar el campo `serie_documento` (ej. T001, V001) en el JSON de creación, primero debes consultar qué series están disponibles para el usuario/establecimiento actual.

### Opción A: Listar Solo Series de Guías (Recomendado)
*   **Método:** `GET`
*   **URL:** `/api/document/series-dispatch`
*   **Descripción:** Retorna específicamente las series para Guías de Remisión Remitente (09) y Transportista (31).

### Opción B: Listar Series Generales
*   **Método:** `GET`
*   **URL:** `/api/document/series`
*   **Descripción:** Retorna series para múltiples documentos (01, 03, 09, 31). Tendrás que filtrar el resultado por `document_type_id`.

### Ejemplo de Respuesta JSON
```json
[
    {
        "id": 15,
        "document_type_id": "09",
        "number": "T001",
        "contingency": false
    },
    {
        "id": 16,
        "document_type_id": "31",
        "number": "V001",
        "contingency": false
    }
]
```

## 3. Resumen de Archivos Clave

| Archivo | Propósito | ¿Es necesario? |
| :--- | :--- | :--- |
| `routes/api.php` | Rutas generales (incluye Guía Remitente `09` y obtención de series). | **SÍ** |
| `modules/Dispatch/Routes/api.php` | Rutas específicas para Guía Transportista `31`. | **SÍ** (solo para Transportista) |
| `app/Http/Controllers/Tenant/Api/MobileController.php` | Controlador que provee el método `getSeriesDispatch` para listar las series. | **SÍ** |
