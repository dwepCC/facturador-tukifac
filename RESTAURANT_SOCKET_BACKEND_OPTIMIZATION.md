# Optimización del backend (Laravel) — Socket.IO restaurante

Recomendaciones alineadas con [RESTAURANT_SOCKET_IO.md](./RESTAURANT_SOCKET_IO.md) y con el flujo del frontend (refresco de `GET /api/restaurant/tablesAndEnv` tras eventos en tiempo real).

---

## 1. Latencia: emitir en el mismo ciclo que persiste

**Objetivo:** el `POST` al puente Node (`/internal/broadcast`) debe ocurrir **en el mismo request HTTP** en el que se guarda la mesa (abrir, cerrar, cambio de estado, etc.), no al final de un `Job` en cola con retraso.

| Situación | Efecto |
|-----------|--------|
| Broadcast en cola (`ShouldQueue`, worker lento) | Retraso de varios segundos en otras terminales; el front no puede corregirlo. |
| Broadcast justo después de `DB::commit()` | Otras terminales reciben el evento al instante (salvo red). |

**Recomendación:** en acciones del **mapa de mesas**, invocar el servicio de emisión (p. ej. `RestaurantSocketBroadcaster`) **después** de confirmar el guardado en base de datos, de forma que no dependa de colas diferidas.

---

## 2. Contrato de eventos coherente con el cliente

El frontend reacciona principalmente a:

- `restaurant.sync` con `scope`: `tables` | `commands` | `groups` | `cash`
- `table_id` (o `tableId`) cuando aplique
- Eventos `restaurant.command_item.*` para detalle de comanda

**Para ocupación de mesas en tiempo real**

- Tras guardar/cerrar/cambiar mesa, emitir `restaurant.sync` con:
  - **`scope: 'tables'`** (o `'groups'` si afecta agrupación)
  - **`table_id`** de la mesa afectada cuando sea posible
  - **`reason`** estable: p. ej. `table_opened`, `table_closed`, `table_saved`, `change_table_pedido`
- Incluir **`emitted_at`** (ISO 8601) en el payload para correlacionar con logs y medir retraso real (Laravel vs red).

No es obligatorio enviar el objeto mesa completo por socket si el patrón del cliente es **evento + refetch REST** (`tablesAndEnv`).

---

## 3. Variables de entorno y operación

| Variable | Notas |
|----------|--------|
| `RESTAURANT_SOCKET_ENABLED=true` | Si está en `false`, Laravel **no** llama al puente; los clientes pueden estar conectados sin recibir negocio. |
| `SOCKET_BRIDGE_URL` | Debe ser **alcanzable desde PHP** (misma máquina `http://127.0.0.1:8070` o URL HTTPS pública del socket, según despliegue). |
| `SOCKET_BRIDGE_SECRET` | Debe coincidir **exactamente** con el proceso Node. |
| `SOCKET_BRIDGE_TIMEOUT` | Subir con moderación solo si hay **timeouts reales** en logs entre Laravel y el puente (red/TLS); evitar subir “por si acaso” sin medir. |

Tras cambiar `.env`: `php artisan config:clear` (o el flujo de `config:cache` en producción).

---

## 4. Broadcast inmediato vs agrupación

- El puente solo reenvía JSON a la sala del tenant; **no retrases** emisiones varios segundos para “fusionar” eventos: eso reproduce lag perceptible.
- Si en **un mismo request** ocurren varias mutaciones, tiene sentido **una sola emisión al final** del controlador (menos HTTP al puente), siempre **antes** de responder al cliente.

---

## 5. Respuesta HTTP al POS vs velocidad del puente

Orden recomendado:

1. Persistir en BD  
2. **Commit**  
3. Llamar al puente (broadcast)  
4. Responder JSON al cliente del POS  

Si el `POST` al Node es lento:

- Mantén timeout acotado y **registra fallos** (la mesa ya está guardada; otros terminales podrían no enterarse hasta el siguiente evento o refetch manual).
- Evitar **`dispatch()->afterResponse()`** hacia colas lentas como **único** mecanismo de tiempo real para el mapa de mesas.

---

## 6. Rendimiento del camino Laravel → Node

- Cuerpo del mensaje **pequeño**: `scope`, ids, `reason`, `emitted_at`, `tenant_room` / metadatos del contrato; el detalle lo resuelve el REST.
- Verificar DNS, proxy saliente y firewall desde el servidor Laravel hacia `SOCKET_BRIDGE_URL`.

---

## 7. Endpoint `POST /api/restaurant/socket/auth`

- Debe ser **rápido**: validación de token y tenant sin trabajo pesado.
- Cada reconexión de clientes pasa por aquí; consultas costosas o N+1 degradan reconexiones.

---

## 8. Observabilidad

- Logs al emitir: tenant/sala, `scope`, `reason`, `table_id`, duración del HTTP al puente, código HTTP de respuesta.
- Permite contrastar `emitted_at` del payload con el timestamp del `save` en API y aislar cuellos de botella.

---

## Resumen

| Prioridad | Acción |
|-----------|--------|
| Alta | Emitir `restaurant.sync` con `scope: 'tables'` **en el mismo flujo** que persiste la mesa (sin cola lenta). |
| Alta | `.env` de socket activo y puente **alcanzable** desde Laravel. |
| Media | Payload mínimo + `reason` / `emitted_at` / `table_id` para depuración y contrato estable. |
| Media | Auth de socket liviano. |
| Baja | Ajustar `SOCKET_BRIDGE_TIMEOUT` solo con evidencia en logs. |

---

## Referencias en este repo

- Arquitectura y variables: [RESTAURANT_SOCKET_IO.md](./RESTAURANT_SOCKET_IO.md)
- Cliente: refresco sin caché obsoleta en sync de mesas (`fetchTablesAndEnvFresh` / `refreshTablesEnvOnly` en el frontend).
