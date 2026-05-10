<?php

namespace Modules\Restaurant\Services;

/**
 * Nombres de eventos Socket.IO emitidos vía puente HTTP (Laravel → Node → clientes).
 * Contrato estable para frontend externo: escuchar estos nombres en socket.on(...)
 */
final class RestaurantSocketEvents
{
    /** Refresco genérico: el cliente debe volver a llamar GET /restaurant/tablesAndEnv u otros según scope */
    public const SYNC = 'restaurant.sync';

    public const COMMAND_ITEM_CREATED = 'restaurant.command_item.created';

    public const COMMAND_ITEM_STATUS = 'restaurant.command_item.status';

    public const COMMAND_ITEM_REMOVED = 'restaurant.command_item.removed';

    public const GROUP_CHANGED = 'restaurant.group.changed';

    public const TABLE_MOVED = 'restaurant.table.moved';

    public const CASH_SESSION = 'restaurant.cash.session';

    public const PRINT_ORDER_CHANGED = 'restaurant.print_order.changed';

    public const SCHEMA_VERSION = 1;
}
