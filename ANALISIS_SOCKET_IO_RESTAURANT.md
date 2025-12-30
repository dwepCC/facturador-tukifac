# Análisis Profundo: Socket.IO en el Módulo de Restaurante

## Resumen Ejecutivo

El sistema de restaurante utiliza **Socket.IO** para comunicación en tiempo real entre diferentes componentes del sistema, especialmente para sincronizar el estado de mesas, pedidos, comandas y actualizaciones de usuarios entre múltiples clientes (mozo, cocina, bar, caja, administrador).

---

## 1. Arquitectura de Socket.IO

### 1.1 Configuración del Servidor

El servidor Socket.IO está configurado en el archivo de configuración:

**Ubicación:** `config/tenant.php`

```php
'socket_server' => env('SOCKET_SERVER', 'http://localhost:8070'),
```

**Valor por defecto:** `http://localhost:8070` (configurable mediante variable de entorno `SOCKET_SERVER`)

### 1.2 Configuración en el Backend

El backend Laravel proporciona la URL del servidor Socket.IO a través del controlador:

**Ubicación:** `modules/Restaurant/Http/Controllers/RestaurantConfigurationController.php`

```43:43:modules/Restaurant/Http/Controllers/RestaurantConfigurationController.php
'info' => ['ruc' => $company->number, 'userEmail' => $user->email, 'socketServer' => config('tenant.socket_server') ?? 'http://localhost:8070'],
```

Este endpoint (`/restaurant/configuration/record`) devuelve:
- `ruc`: Número de RUC de la empresa
- `userEmail`: Email del usuario autenticado
- `socketServer`: URL del servidor Socket.IO

---

## 2. Implementación en el Frontend

### 2.1 Inicialización del Cliente Socket.IO

**Ubicación:** `modules/Restaurant/Resources/assets/js/views/configuration/index.vue`

```457:467:modules/Restaurant/Resources/assets/js/views/configuration/index.vue
import { io } from 'socket.io-client'
import {deletable} from '@mixins/deletable'
import Notas from '../notes/index.vue'
import UsersForm from './partials/form.vue'

const url = 'https://milanmario.com'
const SOCKET = io(url, {
  reconnectionDelayMax: 100,
  transports: ['polling'],
  autoConnect: false,
})
```

**Características importantes:**
- **URL hardcodeada:** Actualmente usa `'https://milanmario.com'` (debería usar la URL dinámica del servidor)
- **Transporte:** Solo usa `polling` (no WebSocket puro)
- **Auto-connect:** Deshabilitado (`autoConnect: false`)
- **Reconexión:** Delay máximo de 100ms

### 2.2 Eventos Emitidos (Cliente → Servidor)

El sistema emite los siguientes eventos:

#### 2.2.1 `reset-table-envs`
**Propósito:** Notificar a todos los clientes que deben recargar las mesas y ambientes.

**Cuándo se emite:**
- Cuando se actualiza la configuración de mesas (cantidad, ambientes habilitados)

**Código:**
```668:670:modules/Restaurant/Resources/assets/js/views/configuration/index.vue
resetTablensAndEnvClients() {
  SOCKET.emit('reset-table-envs')
},
```

#### 2.2.2 `data-user-profile`
**Propósito:** Actualizar el perfil de usuario en tiempo real.

**Datos enviados:**
```javascript
{
  user_email: string,
  role_code: string  // 'ADM', 'CAJA', 'MOZO', 'KITBAR'
}
```

**Cuándo se emite:**
- Cuando se asigna o actualiza un rol a un usuario

**Código:**
```671:677:modules/Restaurant/Resources/assets/js/views/configuration/index.vue
sendUserUpdate(userEmail, roleCode) {
  const data = {
    user_email: userEmail,
    role_code: roleCode,
  }
  SOCKET.emit('data-user-profile', data)
},
```

#### 2.2.3 `data-company`
**Propósito:** Sincronizar información de la empresa.

**Datos enviados:**
```javascript
{
  ruc: string,
  user: string  // email del usuario
}
```

**Cuándo se emite:**
- Al cargar la configuración inicial
- Para identificar la empresa y usuario en el servidor Socket.IO

**Código:**
```678:684:modules/Restaurant/Resources/assets/js/views/configuration/index.vue
sendCompany() {
  const data = {
    ruc: this.info.ruc,
    user: this.info.userEmail
  }
  SOCKET.emit('data-company', data)
},
```

---

## 3. Modelos y Estructura de Datos

### 3.1 Modelo: RestaurantTable

**Ubicación:** `modules/Restaurant/Models/RestaurantTable.php`

**Estructura:**
```php
- id: integer
- status: string ('available', 'notavailable')
- products: json (array de productos)
- total: decimal(12,2)
- personas: integer
- cliente: string (nullable)
- comentarios: string (nullable)
- label: string (etiqueta/número de mesa)
- shape: string ('CUADRADO', 'CIRCULO', etc.)
- environment: string (nombre del ambiente)
- waiter: string (nullable, nombre del mozo)
- opening_date: datetime (nullable, fecha de apertura)
```

**Estados de mesa:**
- `available`: Mesa disponible (sin productos)
- `notavailable`: Mesa ocupada (con productos)

### 3.2 Modelo: RestaurantItemOrderStatus

**Ubicación:** `modules/Restaurant/Models/RestaurantItemOrderStatus.php`

**Estructura:**
```php
- id: integer
- table_id: integer (FK a RestaurantTable)
- item_id: integer
- item: json (datos del producto)
- quantity: integer
- note: string (nullable, notas del pedido)
- status: integer (1-4)
- status_description: string
```

**Estados de pedido:**
- `1`: RECIBIDO (STATUS_RECEIVED)
- `2`: EN PROCESO (STATUS_PROCESSING)
- `3`: PARA ENTREGAR (STATUS_TO_DELIVER)
- `4`: ENTREGADO (STATUS_DELIVERED)

### 3.3 Modelo: RestaurantConfiguration

**Ubicación:** `modules/Restaurant/Models/RestaurantConfiguration.php`

**Configuraciones principales:**
- Menús habilitados: `menu_pos`, `menu_order`, `menu_tables`, `menu_bar`, `menu_kitchen`
- Ambientes: `enabled_environment_1` a `enabled_environment_4`
- Cantidad de mesas por ambiente: `tables_quantity`, `tables_quantity_environment_2`, etc.
- Comandas: `enabled_send_command`, `enabled_print_command`, `enabled_printsend_command`
- Permisos: `enabled_command_waiter`, `enabled_pos_waiter`, `enabled_close_table`

---

## 4. Flujos de Trabajo con Socket.IO

### 4.1 Flujo: Actualización de Configuración de Mesas

1. **Usuario actualiza configuración** (cantidad de mesas, ambientes)
2. **Backend guarda cambios** en `RestaurantConfigurationController@setConfiguration`
3. **Backend regenera mesas** (trunca y recrea todas las mesas)
4. **Frontend emite evento:** `SOCKET.emit('reset-table-envs')`
5. **Servidor Socket.IO** recibe el evento y lo transmite a todos los clientes conectados
6. **Clientes reciben notificación** y recargan las mesas desde el API

**Endpoint API:** `GET /restaurant/tablesAndEnv`

### 4.2 Flujo: Asignación de Rol a Usuario

1. **Admin asigna rol** a un usuario
2. **Backend actualiza** `User.restaurant_role_id`
3. **Frontend emite evento:** `SOCKET.emit('data-user-profile', {user_email, role_code})`
4. **Servidor Socket.IO** actualiza permisos del usuario en tiempo real
5. **Aplicación móvil/web del mozo** recibe actualización y ajusta permisos

### 4.3 Flujo: Gestión de Mesas (Esperado)

**Nota:** El código actual muestra emisión de eventos, pero no se encontraron listeners en el frontend. Se espera que el servidor Socket.IO maneje:

- **Actualización de estado de mesa:** Cuando se abre/cierra una mesa
- **Cambio de productos:** Cuando se agregan/eliminan productos de una mesa
- **Actualización de pedidos:** Cuando cambia el estado de un pedido (cocina/bar)
- **Sincronización entre dispositivos:** Mesa actualizada en un dispositivo se refleja en otros

---

## 5. Endpoints API Relacionados

### 5.1 Mesas

- `GET /restaurant/tablesAndEnv` - Obtiene todas las mesas y configuración de ambientes
- `GET /restaurant/table/{id}` - Obtiene datos de una mesa específica
- `POST /restaurant/table/{id}` - Guarda/actualiza una mesa
- `POST /restaurant/label-table/save` - Actualiza etiqueta y forma de mesa

### 5.2 Pedidos/Comandas

- `POST /restaurant/command-item/save` - Guarda un ítem de pedido con estado
- `GET /restaurant/command-item/status/{id}` - Obtiene estados de pedidos por mesa
- `POST /restaurant/command-item/set-status/{id}` - Cambia estado de un pedido

### 5.3 Configuración

- `GET /restaurant/configuration/record` - Obtiene configuración y datos de Socket.IO
- `POST /restaurant/configuration` - Guarda configuración
- `GET /restaurant/get-users` - Lista usuarios con roles
- `GET /restaurant/get-roles` - Lista roles disponibles

---

## 6. Roles y Permisos

### 6.1 Roles Definidos

- **ADM (Administrador):** Acceso a todos los menús
- **CAJA (Cajero):** Acceso a POS, Mesas, Pedidos
- **MOZO (Mesero):** Acceso a Mesas (puede habilitarse Comanda y POS)
- **KITBAR (Cocina/Bar):** Acceso a Comandas

### 6.2 Permisos Adicionales

- `enabled_command_waiter`: Permite a mozos acceder a comandas
- `enabled_pos_waiter`: Permite a mozos acceder a POS
- `enabled_close_table`: Permite a cajeros cerrar mesas

---

## 7. Implementación Recomendada para el Frontend

### 7.1 Inicialización Correcta del Socket

```javascript
import { io } from 'socket.io-client'

// Obtener URL del servidor desde la configuración
async function initSocket() {
  const response = await this.$http.get('/restaurant/configuration/record')
  const socketServer = response.data.info.socketServer
  
  const socket = io(socketServer, {
    reconnectionDelayMax: 1000,
    transports: ['polling', 'websocket'], // Permitir ambos
    autoConnect: true,
    auth: {
      ruc: response.data.info.ruc,
      userEmail: response.data.info.userEmail
    }
  })
  
  return socket
}
```

### 7.2 Eventos a Escuchar (Listeners)

```javascript
// Escuchar actualización de mesas
socket.on('tables-updated', (data) => {
  // Recargar mesas desde API
  this.loadTables()
})

// Escuchar actualización de pedidos
socket.on('order-status-changed', (data) => {
  // Actualizar estado del pedido
  this.updateOrderStatus(data.orderId, data.status)
})

// Escuchar actualización de mesa específica
socket.on('table-updated', (data) => {
  // Actualizar mesa específica
  this.updateTable(data.tableId, data.tableData)
})

// Escuchar reset de ambientes
socket.on('reset-table-envs', () => {
  // Recargar configuración de mesas
  this.loadTablesAndEnvs()
})

// Escuchar actualización de usuario
socket.on('user-profile-updated', (data) => {
  // Actualizar permisos del usuario
  this.updateUserPermissions(data.userEmail, data.roleCode)
})
```

### 7.3 Eventos a Emitir

```javascript
// Cuando se actualiza una mesa
socket.emit('table-update', {
  tableId: table.id,
  tableData: {
    status: table.status,
    products: table.products,
    total: table.total,
    // ... otros campos
  }
})

// Cuando se cambia estado de pedido
socket.emit('order-status-change', {
  orderId: order.id,
  status: order.status,
  tableId: order.table_id
})

// Cuando se abre/cierra una mesa
socket.emit('table-status-change', {
  tableId: table.id,
  status: table.status,
  openingDate: table.opening_date
})
```

---

## 8. Consideraciones Importantes

### 8.1 Problemas Identificados

1. **URL hardcodeada:** El código actual usa `'https://milanmario.com'` en lugar de la URL dinámica
2. **Auto-connect deshabilitado:** El socket no se conecta automáticamente
3. **Falta de listeners:** No se encontraron listeners para eventos del servidor
4. **Transporte limitado:** Solo usa `polling`, no aprovecha WebSocket

### 8.2 Mejoras Recomendadas

1. **Usar URL dinámica** del servidor Socket.IO desde la configuración
2. **Implementar listeners** para recibir actualizaciones en tiempo real
3. **Habilitar WebSocket** como transporte principal con fallback a polling
4. **Manejar reconexión** automática con mejor lógica
5. **Autenticación** mediante RUC y email del usuario
6. **Rooms/Namespaces** para separar por empresa (RUC)

---

## 9. Estructura del Servidor Socket.IO (Esperada)

Aunque no se encontró el código del servidor Socket.IO, basado en los eventos emitidos, se espera que maneje:

### 9.1 Namespaces/Rooms

- **Por RUC:** Cada empresa tiene su propio namespace/room
- **Por rol:** Diferentes rooms para mozo, cocina, bar, caja

### 9.2 Eventos del Servidor

- `tables-updated`: Cuando se actualizan las mesas
- `table-updated`: Cuando se actualiza una mesa específica
- `order-status-changed`: Cuando cambia el estado de un pedido
- `reset-table-envs`: Cuando se resetean los ambientes
- `user-profile-updated`: Cuando se actualiza un perfil de usuario

---

## 10. Conclusión

El sistema utiliza Socket.IO para sincronización en tiempo real, pero la implementación actual está incompleta. Se recomienda:

1. **Completar la implementación** del cliente Socket.IO en el frontend
2. **Implementar listeners** para recibir actualizaciones
3. **Usar URL dinámica** del servidor
4. **Mejorar la gestión de conexión** y reconexión
5. **Documentar** los eventos del servidor Socket.IO

Este análisis proporciona la base para implementar correctamente la comunicación en tiempo real desde el frontend.

