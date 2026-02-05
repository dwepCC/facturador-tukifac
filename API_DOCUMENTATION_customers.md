# Documentación de API para Gestión de Clientes

Esta documentación detalla los endpoints disponibles para la gestión de clientes (listar, buscar y registrar) para ser implementados desde un frontend externo.

## Autenticación

Todas las peticiones requieren un token de autenticación. Primero debe obtener el token mediante el endpoint de login.

### Login
**Endpoint:** `POST /api/login`

**Parámetros:**
- `email`: Correo electrónico del usuario.
- `password`: Contraseña.

**Respuesta Exitosa:**
```json
{
    "success": true,
    "name": "Nombre Usuario",
    "email": "usuario@email.com",
    "token": "TOKENDEX_EJEMPLO_...",
    ...
}
```

**Nota:** Utilice el `token` devuelto en las cabeceras de las siguientes peticiones: `Authorization: Bearer <TOKEN>`.

---

## Gestión de Clientes

### 1. Listar Clientes (Limitado)
Obtiene un listado de los primeros **20 clientes** ordenados por nombre.
**Nota Importante:** Este endpoint tiene un límite fijo de 20 registros en el servidor y no acepta parámetros para aumentar este número.

**Endpoint:** `GET /api/document/customers`

**Cabeceras:**
- `Authorization`: Bearer {token}

**Respuesta Exitosa:**
```json
{
    "success": true,
    "data": {
        "customers": [
            {
                "id": 1,
                "description": "12345678 - JUAN PEREZ",
                "name": "JUAN PEREZ",
                "number": "12345678",
                "identity_document_type_id": "1",
                "identity_document_type_code": "1",
                "identity_document_type_description": "DNI",
                "address": "AV. SIEMPRE VIVA 123",
                "email": "juan@example.com",
                "telephone": "999999999",
                ...
            }
        ]
    }
}
```

### 2. Buscar Clientes (Listado Completo)
Permite buscar clientes por nombre o número de documento.
**Truco para listar todos:** Este endpoint **NO tiene límite de registros**. Si desea obtener todos los clientes, puede llamar a este endpoint enviando el parámetro `input` vacío.

**Endpoint:** `GET /api/document/search-customers`

**Cabeceras:**
- `Authorization`: Bearer {token}

**Parámetros (Query String):**
- `input`: (Opcional) Texto a buscar. Si se deja vacío o no se envía, devolverá todos los clientes que coincidan con el patrón (potencialmente todos).
- `document_type_id`: (Opcional) Filtrar por tipo de documento.

**Ejemplo de llamada para todos:**
`GET /api/document/search-customers?input=`

**Respuesta Exitosa:**
```json
{
    "success": true,
    "data": {
        "customers": [
            // Array de objetos cliente (sin límite de cantidad)
        ]
    }
}
```

### 3. Registrar Cliente (Person)
Registra un nuevo cliente (o proveedor) en el sistema.

**Endpoint:** `POST /api/person`

**Cabeceras:**
- `Authorization`: Bearer {token}
- `Content-Type`: application/json

**Parámetros del Cuerpo (JSON):**

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `type` | String | Sí | Debe ser `"customers"` para clientes. |
| `identity_document_type_id` | String | Sí | Código del tipo de documento (ej. "1" para DNI, "6" para RUC). |
| `number` | String | Sí | Número de documento (DNI/RUC). Debe ser único. |
| `name` | String | Sí | Nombre o Razón Social. Debe ser único. |
| `country_id` | String | Sí | Código del país (ej. "PE"). |
| `department_id` | String | Condicional | Requerido si es RUC. ID del departamento. |
| `province_id` | String | Condicional | Requerido si es RUC. ID de la provincia. |
| `district_id` | String | Condicional | Requerido si es RUC. ID del distrito. |
| `address` | String | Condicional | Dirección. Requerido si es RUC. |
| `email` | String | No | Correo electrónico. Debe ser único si se envía. |
| `telephone` | String | No | Número de teléfono. |

**Ejemplo de JSON para enviar:**
```json
{
    "type": "customers",
    "identity_document_type_id": "1",
    "number": "12345678",
    "name": "JUAN PEREZ",
    "country_id": "PE",
    "email": "juan@example.com",
    "telephone": "987654321",
    "address": "Av. Principal 123"
}
```

**Respuesta Exitosa:**
```json
{
    "success": true,
    "msg": "Cliente registrado con éxito",
    "data": {
        "id": 15,
        "description": "12345678 - JUAN PEREZ",
        "name": "JUAN PEREZ",
        "number": "12345678",
        ...
    }
}
```

## Notas Adicionales
- Los tipos de documento comunes son:
    - `1`: DNI
    - `6`: RUC
    - `0`: Otros
- Asegúrese de manejar los errores de validación (HTTP 422) que devolverá la API si faltan campos obligatorios o si el cliente ya existe.
