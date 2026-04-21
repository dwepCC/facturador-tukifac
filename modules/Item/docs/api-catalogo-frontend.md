# API catálogo de productos — integración frontend externo

Este documento describe cómo consumir el listado paginado de productos (`GET /api/items/catalog`) desde una aplicación frontend externa (React, Vue, Angular, etc.).

## Requisitos previos

- **Dominio del tenant**: las rutas del módulo se registran bajo el **FQDN del hostname actual** (multi-tenant). La URL base debe ser la del tenant, por ejemplo: `https://tu-empresa.tudominio.com`.
- **Autenticación**: el endpoint está protegido con `auth:api` y `locked.tenant`. Debes enviar el mismo mecanismo de autenticación que uses para el resto de la API (por ejemplo **Bearer token** si usas Passport u otro guard `api`).
- **Prefijo**: el `RouteServiceProvider` del módulo aplica el prefijo `api`. La ruta del grupo es `items`.

**URL completa del catálogo**

```text
GET https://{FQDN_DEL_TENANT}/api/items/catalog
```

Sustituye `{FQDN_DEL_TENANT}` por el host real del cliente (el que resuelve el tenant en tu instalación).

---

## Parámetros de consulta (query string)

Todos son opcionales salvo que quieras cambiar el comportamiento por defecto.

| Parámetro | Tipo | Valores / límites | Comportamiento |
|-----------|------|-------------------|----------------|
| `page` | entero | ≥ 1 | Página actual. Por defecto **1**. |
| `per_page` | entero | 1–100 | Registros por página. Por defecto **15**. |
| `search` | string | máx. 120 caracteres | Búsqueda por coincidencia (`LIKE`) en: `name`, `second_name`, `description`, `internal_id`, `item_code`, `item_code_gs1`, `barcode`, y códigos de barras de presentaciones (`item_unit_types`). |
| `category_id` | entero | ID existente en la tabla **`categories` de la base del tenant** | Filtra productos de esa categoría. Si no se envía, no se filtra por categoría. La validación usa el modelo tenant (no la BD central). |
| `apply_store` | booleano | `true` / `false` (o `1` / `0` según validación Laravel) | Si es `true`, solo ítems con **tienda** (`apply_store`). Por defecto **no** se aplica este filtro (se listan todos los que cumplan el resto). |
| `include_inactive` | booleano | idem | Si es `true`, incluye productos **inactivos**. Por defecto **solo activos**. |
| `include_services` | booleano | idem | Si es `true`, incluye **servicios** (`unit_type` tipo servicio). Por defecto se **excluyen** servicios (solo “productos” en sentido comercial). |
| `with_gallery` | booleano | idem | Si es `true`, se cargan imágenes adicionales del producto y la respuesta incluye la clave **`gallery`**. Si es `false` u omitido, la relación no se carga y **`gallery` no aparece** en el JSON (respuesta más liviana). |

### Ejemplos de URL

```text
# Primera página, 20 por página, búsqueda y categoría
GET /api/items/catalog?page=1&per_page=20&search=arroz&category_id=5

# Solo productos pensados para tienda, con galería
GET /api/items/catalog?apply_store=1&with_gallery=1

# Página 3, 50 ítems
GET /api/items/catalog?page=3&per_page=50
```

### Booleanos desde JavaScript

Laravel acepta varios formatos; lo más claro desde el frontend:

- `apply_store=true` o `apply_store=1`
- `with_gallery=false` o `with_gallery=0`

Si construyes la query con `URLSearchParams`, asegúrate de no enviar cadenas vacías para booleanos opcionales (mejor omitir la clave).

---

## Cabeceras HTTP recomendadas

```http
GET /api/items/catalog?page=1&per_page=15&search=termo HTTP/1.1
Host: {FQDN_DEL_TENANT}
Accept: application/json
Authorization: Bearer {TU_TOKEN}
```

Ajusta `Authorization` al esquema que exija tu API (`api` guard).

---

## Estructura de la respuesta

Laravel devuelve una **colección de API Resource paginada**. Estructura típica:

```json
{
  "data": [
    {
      "id": 1,
      "name": "Nombre comercial",
      "second_name": "Nombre secundario o alias",
      "description": "Descripción del producto",
      "internal_id": "INT-001",
      "item_code": "COD-REF",
      "item_code_gs1": null,
      "barcode": "7750123456789",
      "sale_unit_price": 12.5,
      "stock": 100,
      "currency_symbol": "S/",
      "unit_type": {
        "id": "NIU",
        "description": "Unidades",
        "symbol": null,
        "active": true
      },
      "category": {
        "id": 3,
        "name": "Abarrotes"
      },
      "active": true,
      "apply_store": true,
      "image_url": "https://.../storage/uploads/items/foto.jpg",
      "image_url_medium": "https://.../storage/uploads/items/foto_med.jpg",
      "image_url_small": "https://.../storage/uploads/items/foto_sm.jpg",
      "gallery": [
        { "id": 10, "image_url": "https://.../storage/uploads/items/extra1.jpg" }
      ]
    }
  ],
  "links": {
    "first": "https://.../api/items/catalog?page=1",
    "last": "https://.../api/items/catalog?page=5",
    "prev": null,
    "next": "https://.../api/items/catalog?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "path": "https://.../api/items/catalog",
    "per_page": 15,
    "to": 15,
    "total": 73
  }
}
```

### Claves por ítem (`data[]`)

| Clave | Tipo | Descripción |
|-------|------|-------------|
| `id` | número | Identificador del ítem. |
| `name` | string \| null | Nombre. |
| `second_name` | string \| null | Segundo nombre / alias. |
| `description` | string \| null | Descripción (texto limpio según el modelo). |
| `internal_id` | string \| null | Código interno. |
| `item_code` | string \| null | Código de ítem. |
| `item_code_gs1` | string \| null | Código GS1 si aplica. |
| `barcode` | string \| null | Código de barras principal del ítem. |
| `sale_unit_price` | number \| null | Precio de venta unitario (número decimal). |
| `stock` | number \| null | Stock global del ítem en el modelo (según tu negocio puede no ser el único almacén). |
| `currency_symbol` | string \| null | Símbolo de moneda (relación `currency_type`). |
| `unit_type` | objeto \| omitido | Unidad SUNAT (`cat_unit_types`): `id`, `description` (texto legible, p. ej. «Unidades»), `symbol`, `active`. No existe columna `name` en catálogo. Puede no venir si el ítem no tiene unidad cargada. |
| `category` | objeto \| omitido | `id` y `name` de la categoría. Puede no venir si el producto no tiene categoría. |
| `active` | boolean | Si el producto está activo. |
| `apply_store` | boolean | Si aplica para tienda / canal externo. |
| `image_url` | string | URL absoluta de imagen principal (o placeholder si no hay). |
| `image_url_medium` | string | Variante mediana. |
| `image_url_small` | string | Variante pequeña (ideal para listas / thumbs). |
| `gallery` | array | **Solo si** pediste `with_gallery=true`. Lista de `{ id, image_url }`. Si no pides galería, esta clave **no** se incluye. |

### Paginación (`meta` y `links`)

- Usa **`meta.current_page`**, **`meta.last_page`**, **`meta.per_page`**, **`meta.total`** para pintar “Página X de Y” y el tamaño de página.
- Puedes usar **`links.next`** y **`links.prev`** como URL lista para el siguiente/anterior request, o recomponer tú la query con `page` + mismos filtros (recomendado para mantener `search`, `category_id`, etc.).

---

## Cómo implementarlo en el frontend

### 1. Estado mínimo recomendado

Guarda en el estado de tu SPA (o store):

- `page`, `per_page`
- `search` (texto del buscador)
- `categoryId` (nullable; id seleccionado o `null` = “todas”)
- flags opcionales: `applyStoreOnly`, `withGallery`, etc., según tu UI

### 2. Construir la petición

Cada vez que cambien filtros o búsqueda:

1. Resetea `page` a **1** (salvo que el usuario solo cambie de página).
2. Arma los query params a partir del estado.
3. Llama al `GET` con el token en cabecera.

Ejemplo con `fetch`:

```javascript
async function fetchCatalog({ baseUrl, token, page, perPage, search, categoryId, applyStore, withGallery }) {
  const params = new URLSearchParams();
  params.set('page', String(page));
  params.set('per_page', String(perPage));
  if (search?.trim()) params.set('search', search.trim());
  if (categoryId != null) params.set('category_id', String(categoryId));
  if (applyStore) params.set('apply_store', '1');
  if (withGallery) params.set('with_gallery', '1');

  const res = await fetch(`${baseUrl}/api/items/catalog?${params}`, {
    headers: {
      Accept: 'application/json',
      Authorization: `Bearer ${token}`,
    },
  });
  if (!res.ok) throw new Error(await res.text());
  return res.json();
}
```

`baseUrl` debe ser el origen del tenant (sin barra final), por ejemplo `https://cliente.midominio.com`.

### 3. UI de búsqueda

- **Campo de texto** enlazado a `search`.
- **Debouncing** (300–500 ms): no dispares la API en cada tecla; espera a que el usuario deje de escribir para reducir carga.
- Al enviar búsqueda o limpiar: vuelve a `page = 1` y vuelve a pedir el catálogo.

### 4. UI de filtro por categoría

- **Select / lista** de categorías: el endpoint de catálogo **no** devuelve el listado de categorías; necesitas los `id` válidos. Opciones habituales:
  - Mantener un endpoint o recurso de “categorías” en tu backend expuesto al mismo frontend, o
  - Cargar categorías desde tu propio BFF que ya tenga acceso al tenant.
- Valor especial **“Todas”**: no envíes `category_id` (o no lo incluyas en la query).

### 5. UI de paginación

- Botones “Anterior / Siguiente” usando `meta.current_page` y `meta.last_page`.
- Selector de `per_page` (15, 30, 50…) que al cambiar reinicie `page` a 1.

### 6. Imágenes en listas y detalle

- En **grids o listas**: usa preferentemente **`image_url_small`** o **`image_url_medium`** para menos ancho de banda.
- En **detalle / zoom**: `image_url`.
- Si necesitas carrusel de fotos adicionales: pide **`with_gallery=1`** (solo en vista detalle o cuando haga falta; no hace falta en cada scroll de listado infinito).

### 7. Manejo de errores

- **401 / 403**: token inválido o tenant bloqueado (`locked.tenant`).
- **422**: parámetros inválidos (por ejemplo `category_id` inexistente, `per_page` > 100). Laravel suele devolver `errors` con los campos; muéstralos o corrige la query.

---

## Resumen rápido

| Necesidad | Qué enviar |
|-----------|------------|
| Listado paginado | `page`, `per_page` |
| Buscar por nombre/códigos/barcode | `search` |
| Filtrar categoría | `category_id` |
| Solo catálogo tienda | `apply_store=true` |
| Incluir inactivos | `include_inactive=true` |
| Incluir servicios | `include_services=true` |
| Fotos extra | `with_gallery=true` |

Con esto puedes enlazar buscador, filtros y paginación del frontend externo con la API de catálogo de forma predecible y alineada con las claves reales de la respuesta.
