# Socket.IO — Restaurante (Tukifac)

Implementación incluida:

1. **Laravel**: servicio `RestaurantSocketBroadcaster` que envía eventos al puente Node por HTTP (`POST /internal/broadcast`) con cabecera `X-Restaurant-Socket-Secret`.
2. **Node** (`socket-server/`): Socket.IO + Express; valida `api_token` contra `POST /api/restaurant/socket/auth` del tenant; une cada cliente a la sala `tenant:<sha1(fqdn)>`.
3. **Hooks** en controladores del módulo restaurante (mesas, grupos, comandas, impresión, caja API).

---

## Servidor Socket en producción (referencia actual)

El puente Socket.IO público está desplegado en **`https://ws.gestionweb.cloud`** (comprobación: [health](https://ws.gestionweb.cloud/health) → `{"ok":true,"service":"tukifac-restaurant-socket"}`).

| Uso | Variable / acción |
|-----|-------------------|
| **Laravel** (`.env` en el servidor donde corre Docker/API) | `SOCKET_SERVER=https://ws.gestionweb.cloud` y `SOCKET_BRIDGE_URL=https://ws.gestionweb.cloud` (misma base URL sin barra final). `SOCKET_BRIDGE_SECRET` debe coincidir **exactamente** con la variable `SOCKET_BRIDGE_SECRET` del proceso Node en ese host. |
| **Frontend externo** | `VITE_SOCKET_SERVER` / equivalente = `https://ws.gestionweb.cloud` (o el valor que devuelva `info.socketServer` en `GET /api/restaurant/configurations`, que lee `SOCKET_SERVER`). |

Tras cambiar `.env` en Laravel: `php artisan config:clear` (o `config:cache` en despliegue).

---

## Multi-tenant (clientes separados y base de datos propia)

Este proyecto usa **multi-tenancy por dominio** (p. ej. Hyn Multi-tenant): cada cliente (tenant) resuelve su **hostname/FQDN** y se conecta a **su propia base de datos**. Los datos de un restaurante **nunca** se mezclan en SQL con los de otro.

**Cómo encaja Socket.IO en ese modelo**

| Capa | Qué pasa |
|------|-----------|
| **Laravel** | Cada petición HTTP entra por el dominio del tenant; la app usa la BD correcta. Al emitir al puente, calcula una sala fija para ese tenant: `tenant:<sha1(fqdn)>` (FQDN en minúsculas). |
| **Node (compartido)** | Un único proceso puede atender a **todos** los clientes; no necesita una BD propia. Solo reenvía eventos a la **sala** correspondiente. El cliente A **no** recibe eventos del cliente B porque están en salas distintas. |
| **Validación del socket** | Node llama a `POST https://<FQDN-del-tenant>/api/restaurant/socket/auth` con el `api_token`. Laravel resuelve el tenant por **Host** y consulta usuarios en **esa** BD. Por eso `tenant_origin` en el handshake debe ser la **URL base del API de ese cliente**, no otro dominio. |

**Reglas prácticas para tu frontend externo**

1. **Un usuario logueado contra un tenant** solo debe usar ese mismo origen como `tenant_origin` (ej. `https://restaurant.e-cliente.com`). Así el token se valida contra la BD correcta.
2. **`socket_room`** (login o `GET /api/restaurant/configurations`) identifica la sala; debe coincidir con lo que usa Laravel para ese FQDN. No hardcodees salas.
3. Si tu SPA está en otro dominio (ej. `https://app.midesarrollo.com`) pero el API del cliente es `https://cliente.midominio.com`, igualmente envía **`tenant_origin: 'https://cliente.midominio.com'`** en `auth` del socket (origen del API tenant). La política CORS del API y del servidor Node debe permitir tu SPA; el aislamiento de datos sigue siendo por tenant en Laravel.
4. **`SOCKET_SERVER`** puede ser **único** para toda la plataforma (un solo Node); la separación por cliente es por **sala + token**, no por instalar un Node por restaurante (aunque también puedes hacerlo si lo prefieres operativamente).

---

## Variables de entorno (Laravel `.env`)

| Variable | Descripción |
|----------|-------------|
| `SOCKET_SERVER` | URL pública del servidor Socket.IO para **clientes** y para exponer en API (`info.socketServer`). Producción ejemplo: `https://ws.gestionweb.cloud`. Local: `http://localhost:8070`. Ya existía en `config/tenant.php`. |
| `RESTAURANT_SOCKET_ENABLED` | `true` para activar emisiones desde Laravel. |
| `SOCKET_BRIDGE_URL` | Base URL a la que Laravel hace `POST .../internal/broadcast`. **Mismo servidor que Laravel:** `http://127.0.0.1:8070`. **Node en otro VPS / dominio:** la URL **HTTPS pública** del socket (ej. `https://ws.gestionweb.cloud`), igual que `SOCKET_SERVER` si solo hay un endpoint. |
| `SOCKET_BRIDGE_SECRET` | Secreto compartido con Node (obligatorio si `RESTAURANT_SOCKET_ENABLED=true`). |
| `SOCKET_BRIDGE_TIMEOUT` | Timeout HTTP al puente en segundos (default `2`). Puedes subirlo si hay latencia entre Laravel y el VPS del socket. |

Si Laravel y Node están en **máquinas distintas**, el navegador y Laravel suelen usar la **misma** URL pública HTTPS del socket; solo cambia que Laravel llama por servidor saliente y el usuario por WebSocket.

---

## Cómo funciona Socket.IO en este proyecto (qué hace y por qué hace falta)

**Qué es Socket.IO (resumen)**  
Es una capa sobre **WebSocket** (con fallback a “long polling” si hace falta) que mantiene una **conexión abierta** entre el navegador y un servidor Node. Eso permite que el **servidor te empuje mensajes** al instante, sin que el front tenga que preguntar cada segundo con `setInterval` al API.

**En Tukifac hay dos caminos de datos distintos:**

1. **API REST (Laravel)**  
   El mozo abre una mesa, agrega platos, etc. → el front llama a `POST/GET` de Laravel. Laravel escribe en la **base de datos del tenant** y, si `RESTAURANT_SOCKET_ENABLED=true`, hace un **HTTP interno** al Node: `POST /internal/broadcast` con el **secreto** y el evento (ej. “mesas actualizadas”).

2. **Socket.IO (Node)**  
   Node **no** consulta la BD de negocio. Solo:
   - **Autentica** al navegador: pide a Laravel `POST /api/restaurant/socket/auth` con el `api_token` y el dominio del tenant.
   - **Mete** al cliente en la **sala** correcta (`tenant:...`).
   - **Reenvía** a esa sala lo que Laravel le mandó por `/internal/broadcast`.

**Por qué hace falta el Node**  
Laravel (PHP-FPM) no mantiene millones de conexiones WebSocket abiertas de forma idiomática; el proceso Node **sí** está hecho para eso. Por eso: **Laravel = verdad y persistencia; Node = megáfono en tiempo real.**

**Flujo mental**

```text
[ Navegador A ]──WebSocket──▶ [ Node ]◀──HTTP interno── [ Laravel ]
[ Navegador B ]──WebSocket──▶ [ Node ]     (emitir eventos)
```

- Sin Node corriendo: la app **sigue funcionando** por REST, pero **no** habrá actualizaciones en vivo (o fallará el login del socket en el front).
- Sin `RESTAURANT_SOCKET_ENABLED` o sin `SOCKET_BRIDGE_SECRET`: Laravel **no** notifica a Node; el socket puede conectarse pero no recibirá eventos de negocio.

---

## En local (Laragon / Windows)

**Orden recomendado**

1. **Levantar Laravel** como siempre (Apache/Nginx de Laragon + tu virtual host del tenant, ej. `https://cliente.test`).
2. **Configurar `.env` de Laravel** (en la raíz del proyecto, no en `socket-server`):
   - `RESTAURANT_SOCKET_ENABLED=true`
   - `SOCKET_BRIDGE_SECRET=pon_aqui_una_cadena_larga_aleatoria` (mismo valor en el paso 3)
   - `SOCKET_BRIDGE_URL=http://127.0.0.1:8070` (Laravel en la misma PC habla con Node en localhost)
   - `SOCKET_SERVER=http://localhost:8070` (el **navegador** y el front apuntan aquí en local; si usas HTTPS en el sitio, valora `https://` solo si proxy-eas el socket; en Laragon puro suele bastar `http://localhost:8070` en desarrollo)
3. **Arrancar Node** en otra terminal:

   ```bat
   cd socket-server
   npm install
   set SOCKET_BRIDGE_SECRET=Tukhtutufgppohinnwyetr
   set PORT=8070
   npm start
   ```

4. Comprueba en el navegador: `http://127.0.0.1:8070/health` → debe responder JSON `ok`.
5. **Front externo**: `VITE_SOCKET_SERVER` (o equivalente) = `http://localhost:8070`, y `tenant_origin` = la URL base con la que llamas al API (ej. `https://cliente.test` **sin** barra final, coherente con `socket-server/server.js`).

**CORS**  
En local, `CORS_ORIGIN=*` en Node (por defecto en el código) suele bastar. Si el front corre en otro puerto (ej. `http://localhost:5173`), el socket sigue pudiendo conectarse a `localhost:8070` si el cliente lo permite; si ves bloqueos, ajusta `CORS_ORIGIN` en el proceso Node o la variable de entorno documentada en `socket-server/.env.example`.

**Firewall de Windows**  
La primera vez, Windows puede preguntar por Node; permite acceso **privado** en la red local.

---

## En el VPS (producción)

**Misma idea que en local**, pero con tres cuidados:

| Tema | Qué hacer |
|------|-----------|
| **Node siempre arriba** | Usa **systemd**, **PM2**, o Docker con reinicio automático. El servicio debe exportar `PORT`, `SOCKET_BRIDGE_SECRET` (igual que Laravel) y, si aplica, `CORS_ORIGIN` con el origen de tu SPA. |
| **Dos URLs** | Si Laravel y Node **comparten VPS**: `SOCKET_BRIDGE_URL=http://127.0.0.1:8070`, `SOCKET_SERVER=https://tu-dominio-publico`. Si Node está **en otro VPS** (ej. `https://ws.gestionweb.cloud`): **`SOCKET_BRIDGE_URL` y `SOCKET_SERVER` pueden ser la misma URL HTTPS** del socket. |
| **Nginx (o Caddy)** | Proxy reverso a Node en `/socket.io/` con `Upgrade` y `Connection` para WebSocket, timeouts largos. El usuario **no** abre el puerto 8070 al mundo si todo pasa por 443. |
| **HTTPS** | En producción usa `wss://` (WebSocket seguro). La URL pública en el front debe coincidir con el certificado. |
| **Laravel y Node en el mismo VPS** | Típico: `SOCKET_BRIDGE_URL=http://127.0.0.1:8070`. Si Laravel está en otro contenedor, usa la IP/hostname **interna** de la red Docker que alcanza al contenedor Node. |

**Comprobaciones rápidas**

- Socket dedicado (referencia): `curl -s https://ws.gestionweb.cloud/health`
- Desde el servidor Laravel local hacia Node local: `curl -s http://127.0.0.1:8070/health`
- Handshake Socket.IO: el cliente debe poder abrir `wss://` al mismo host configurado en `SOCKET_SERVER`.

---

## Puesta en marcha del servidor Node

```bash
cd socket-server
npm install
set SOCKET_BRIDGE_SECRET=tu_secreto_seguro   # Windows CMD
# export SOCKET_BRIDGE_SECRET=tu_secreto_seguro   # Linux/macOS
npm start
```

Opcional: copiar `socket-server/.env.example` y cargar variables con `dotenv` (no incluido por defecto; puedes usar `node -r dotenv/config server.js` si añades `dotenv`).

Comprueba `GET http://127.0.0.1:8070/health`.

**Nginx (ejemplo)** para exponer Socket.IO en HTTPS y proxy a Node:

```nginx
location /socket.io/ {
    proxy_pass http://127.0.0.1:8070;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
    proxy_set_header Host $host;
    proxy_read_timeout 86400;
}
```

Ajusta `SOCKET_SERVER` en Laravel al origen público (`https://tu-dominio.com` si englobas solo path `/socket.io/`).

---

## Frontend externo — cómo implementarlo (paso a paso)

### 1. Dependencias y configuración

- Instala `socket.io-client` (versión mayor compatible con el servidor, p. ej. 4.x).
- Variables de entorno del frontend:
  - **`VITE_API_BASE_URL`** / **`NEXT_PUBLIC_API_URL`**: URL base del tenant con la que llamas a `/api/...` (debe ser el FQDN del cliente).
  - **`VITE_SOCKET_SERVER`**: igual que `SOCKET_SERVER` en Laravel. **Producción actual:** `https://ws.gestionweb.cloud` (WebSocket seguro `wss://` vía HTTPS).

### 2. Momento de conexión

Recomendado: **después de login exitoso**, cuando ya tengas `token` y sepas el origen del API.

- Guarda: `token`, `tenant_origin` (= origen del API del tenant), `socket_room` (respuesta login o configuración).
- Crea **una sola instancia** de socket por sesión (servicio singleton / provider global) y reconecta si cambia el usuario.

### 3. Handshake obligatorio (multi-tenant)

```text
auth.token          → mismo valor que envías como Bearer al API (api_token)
auth.tenant_origin  → URL base del tenant, ej. https://mi-cliente.dominio.com
```

Sin `tenant_origin` correcto, Node no puede validar el token contra la BD del cliente.

### 4. Estrategia de actualización de UI

| Estrategia | Cuándo usarla |
|------------|----------------|
| **Refresco por evento `restaurant.sync`** | Implementación rápida y consistente: ante `scope`, vuelves a llamar al mismo endpoint REST que ya usas para cargar la pantalla (p. ej. `tablesAndEnv`). |
| **Patch fino** | Escuchar también `restaurant.command_item.*` y actualizar solo listas o filas con los IDs del payload; menos tráfico, más código. |

Tras **reconectar** el socket (red inestable), conviene un **refresco completo** del estado visible (`tablesAndEnv` o la vista actual).

### 5. Ejemplo mínimo (`socket.io-client`)

```javascript
import { io } from 'socket.io-client';

const tenantOrigin = import.meta.env.VITE_API_BASE_URL; // debe ser el host del tenant
const socketUrl = import.meta.env.VITE_SOCKET_SERVER;

export function createRestaurantSocket(apiToken) {
  return io(socketUrl, {
    path: '/socket.io',
    transports: ['websocket', 'polling'],
    auth: {
      token: apiToken,
      tenant_origin: tenantOrigin.replace(/\/$/, ''),
    },
    reconnection: true,
    reconnectionDelayMax: 5000,
  });
}

// Refresco genérico
socket.on('restaurant.sync', async (payload) => {
  const { scope, reason, table_id } = payload;
  // Invalidar cache React Query / Pinia / Redux y repetir GET según scope
});

socket.on('restaurant.command_item.created', (p) => { /* opcional: UI granular */ });
socket.on('restaurant.command_item.status', (p) => { /* cocina / barra */ });
socket.on('restaurant.command_item.removed', (p) => { /* ... */ });
socket.on('restaurant.print_order.changed', (p) => { /* impresión servidor */ });
```

**Contrato de eventos** (`Modules\Restaurant\Services\RestaurantSocketEvents`):

- `restaurant.sync` — `scope`: `tables` | `commands` | `groups` | `cash`; `reason` describe la operación; suele incluir `table_id` o similar cuando aplica.
- `restaurant.command_item.created` | `restaurant.command_item.status` | `restaurant.command_item.removed`
- `restaurant.print_order.changed`
- Metadatos en todos: `schema_version`, `emitted_at`, `tenant_room`.

---

## Dónde implementarlo en tu frontend externo (vistas / procesos)

Usa esta tabla como checklist: en cada **vista o flujo**, suscríbete a los eventos indicados y/o reacciona a `restaurant.sync` con el `scope` adecuado refrescando los datos que esa pantalla ya obtiene por REST.

| Vista / proceso UX | Objetivo en tiempo real | Eventos / acción recomendada |
|--------------------|-------------------------|------------------------------|
| **Mapa de mesas / salón** | Ver ocupación, mozo, totales y grupos sin pulsar “actualizar” | `restaurant.sync` con `scope: 'tables'` o `'groups'` → refrescar `GET /api/restaurant/tablesAndEnv`. Opcional: si muestras detalle de una mesa, filtrar por `payload.table_id` para evitar refetch global. |
| **Apertura / cierre de mesa** | Otros mozos ven al instante el cambio | Misma vista mesas; motivos típicos `table_saved`, `table_closed`, `change_table_pedido`. |
| **Cambio de mesa del pedido** | Origen y destino coherentes para todos | `restaurant.sync` `tables` + `reason` relacionado con cambio de mesa. |
| **Agrupación / separación de mesas** | Layout de grupos sincronizado | `scope: 'groups'` (`group_created`, `table_added_to_group`, `group_disbanded`, etc.). |
| **Toma de pedido / carrito por mesa** | Saber si otro agregó ítems o canceló | `restaurant.command_item.*` + `restaurant.sync` `commands`; refrescar líneas de comanda (`command-status/items/...`) o totales de mesa. |
| **Pantalla cocina / preparación** | Pasar estados recibido → en preparación → listo | Prioridad a `restaurant.command_item.created` y `restaurant.command_item.status`; refrescar columnas por estado o por área de preparación si las usas. |
| **Pantalla barra** | Igual que cocina si comparte flujo de estados | Idem cocina. |
| **Delivery / para llevar** (si aplica) | Cierre de pedido reflejado | `restaurant.sync` `tables`, razones de borrado delivery/takeaway. |
| **Caja restaurante (sesión / arqueo)** | Supervisor u otro terminal ve movimientos | `restaurant.sync` `scope: 'cash'` tras registrar sesión (`storeRestaurant` API móvil). |
| **Impresión por servidor** (si habilitada) | Cola de trabajos de impresión | `restaurant.print_order.changed`; complementar con SSE `print-orders/stream` si ya lo usas. |
| **Configuración de mesas / ambientes** (admin) | Menos crítico para mozos; útil si varios admins editan a la vez | `restaurant.sync` `tables` con razones `table_toggle_active`, `table_environment_changed`, etc. |

**Prioridad si vas por fases**

1. **Fase 1**: Solo `restaurant.sync` + refetch de `tablesAndEnv` en la vista de **mesas** (mayor impacto para mozos en paralelo).
2. **Fase 2**: Vista **cocina/barra** con `restaurant.command_item.*` o refetch de comandos por mesa.
3. **Fase 3**: **Caja** y **impresión** según los roles que uses en el frontend externo.

**Resumen multi-tenant**: cada cliente tiene su API en su FQDN y su BD; el socket compartido solo reparte eventos dentro de la sala `socket_room` devuelta por ese mismo tenant.

---

## Seguridad

- No expongas `SOCKET_BRIDGE_SECRET` al navegador.
- Usa WSS en producción.
- Limita rate limit en `/api/restaurant/socket/auth` si hace falta (middleware futuro).

---

## Desactivar

`RESTAURANT_SOCKET_ENABLED=false` — Laravel deja de llamar al puente; el servidor Node puede seguir activo para clientes sin nuevos pushes hasta que lo reinicies.
