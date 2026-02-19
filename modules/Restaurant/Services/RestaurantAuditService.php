<?php

namespace Modules\Restaurant\Services;

use Modules\Restaurant\Models\RestaurantAuditLog;
use Illuminate\Http\Request;

class RestaurantAuditService
{
    public static function log(string $action, ?string $entityType = null, ?int $entityId = null, array $payload = []): void
    {
        try {
            RestaurantAuditLog::create([
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'user_id' => auth()->id(),
                'payload' => $payload,
                'ip' => request()?->ip(),
            ]);
        } catch (\Throwable $e) {
            \Log::warning('RestaurantAuditService::log failed: ' . $e->getMessage());
        }
    }

    public static function logTableClosed(int $tableId, ?int $saleNoteId, ?int $documentId, int $ordersDeleted): void
    {
        self::log('table_closed', 'restaurant_tables', $tableId, [
            'sale_note_id' => $saleNoteId,
            'document_id' => $documentId,
            'orders_deleted_count' => $ordersDeleted,
        ]);
    }

    public static function logOrdersDeleted(int $tableId, array $orderIds): void
    {
        self::log('orders_deleted', 'restaurant_tables', $tableId, [
            'order_ids' => $orderIds,
        ]);
    }

    public static function logTableConfigRegenerated(): void
    {
        self::log('table_config_regenerated', null, null, []);
    }

    public static function logItemOrderCreated(int $orderId, int $tableId, int $itemId, int $quantity): void
    {
        self::log('item_order_created', 'restaurant_item_order_statuses', $orderId, [
            'table_id' => $tableId,
            'item_id' => $itemId,
            'quantity' => $quantity,
        ]);
    }

    /**
     * Registra la eliminación de un ítem de la comanda (con razón y verificación de PIN).
     * No se almacena la contraseña/PIN, solo que fue verificado.
     */
    public static function logComandaItemRemoved(
        int $orderId,
        int $tableId,
        int $itemId,
        int $quantity,
        string $reason
    ): void {
        self::log('comanda_item_removed', 'restaurant_item_order_statuses', $orderId, [
            'table_id' => $tableId,
            'item_id' => $itemId,
            'quantity' => $quantity,
            'reason' => $reason,
        ]);
    }
}
