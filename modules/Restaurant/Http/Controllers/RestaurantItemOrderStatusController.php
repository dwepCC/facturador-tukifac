<?php

namespace Modules\Restaurant\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Exception;
use Illuminate\Support\Facades\DB;
use App\Models\Tenant\Item;
use Modules\Restaurant\Models\RestaurantItemOrderStatus;
use Modules\Restaurant\Models\RestaurantTable;
use Modules\Restaurant\Services\RestaurantStockService;
use Modules\Restaurant\Services\RestaurantAuditService;
use Modules\Restaurant\Services\RestaurantSocketEvents;
use Modules\Restaurant\Http\Controllers\Concerns\BroadcastsRestaurantSocket;
use Illuminate\Support\Collection;

class RestaurantItemOrderStatusController extends Controller
{
    use BroadcastsRestaurantSocket;

    const STATUS_RECEIVED = 1;
    const STATUS_PROCESSING = 2;
    const STATUS_TO_DELIVER = 3;
    const STATUS_DELIVERED = 4;

    const MAX_QUANTITY_PER_ITEM = 999;

    public function saveItemOrder(Request $request)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1|max:' . self::MAX_QUANTITY_PER_ITEM,
            'table_id' => 'nullable|integer',
            'item_id' => 'required|integer',
            'item' => 'required|array',
        ], [
            'quantity.min' => 'La cantidad debe ser al menos 1.',
            'quantity.max' => 'La cantidad no puede superar ' . self::MAX_QUANTITY_PER_ITEM . '.',
        ]);

        $itemData = $request->item;
        $stockService = app(RestaurantStockService::class);

        try {
            $result = DB::connection('tenant')->transaction(function () use ($request, $itemData, $stockService) {
                try {
                    if (isset($itemData['has_sets']) && $itemData['has_sets']) {
                        foreach ($itemData['items_sets'] as $itemSet) {
                            $item_model = Item::find($itemSet['id']);
                            if (!$item_model) continue;
                            $item_supplies = $item_model->restaurantItemSupplies;
                            foreach ($item_supplies as $item_supply) {
                                $supply_quantity = $item_supply->quantity;
                                $order_quantity = $request->quantity * $itemSet['pivot']['quantity'];
                                $total_to_discount = $supply_quantity * $order_quantity;
                                $supply = $item_supply->supply;
                                $supply->stock -= $total_to_discount;
                                $supply->save();
                            }
                            $stockService->calculateAndUpdateStock($itemSet['id']);
                        }
                    } elseif (isset($itemData['has_supplies']) && $itemData['has_supplies']) {
                        $item_model = Item::find($request->item_id);
                        if ($item_model) {
                            foreach ($item_model->restaurantItemSupplies as $item_supply) {
                                $total_to_discount = $item_supply->quantity * $request->quantity;
                                $supply = $item_supply->supply;
                                $supply->stock -= $total_to_discount;
                                $supply->save();
                            }
                        }
                    }
                } catch (Exception $e) {
                    \Log::error("Error descontando supplies: " . $e->getMessage());
                    throw new \RuntimeException('Error al descontar insumos.');
                }

                $stockService->calculateAndUpdateStock($request->item_id);

                try {
                    if (isset($itemData['has_sets']) && $itemData['has_sets']) {
                        foreach ($itemData['items_sets'] as $itemSet) {
                            $componentQuantity = $itemSet['pivot']['quantity'] * $request->quantity;
                            $stockService->reserveQuantity($itemSet['id'], $componentQuantity);
                        }
                    } else {
                        $stockService->reserveQuantity($request->item_id, $request->quantity);
                    }
                    if (isset($itemData['modifiersApplied']) && is_array($itemData['modifiersApplied'])) {
                        foreach ($itemData['modifiersApplied'] as $group) {
                            if (isset($group['items']) && is_array($group['items'])) {
                                foreach ($group['items'] as $modifierItem) {
                                    if (isset($modifierItem['type'], $modifierItem['item_id'])
                                        && $modifierItem['type'] === 'item' && $modifierItem['item_id']) {
                                        $stockService->reserveQuantity($modifierItem['item_id'], $request->quantity);
                                    }
                                }
                            }
                        }
                    }
                } catch (Exception $e) {
                    \Log::error("Error reservando stock: " . $e->getMessage());
                    throw new \RuntimeException('Error al reservar stock.');
                }

                // Crear la orden
                $orderStatus = new RestaurantItemOrderStatus();
                $orderStatus->table_id = $request->table_id;
                $orderStatus->item_id = $request->item_id;
                $orderStatus->item = json_encode($itemData);
                $orderStatus->quantity = $request->quantity;
                $orderStatus->note = $request->note;
                $orderStatus->status = $request->status ?? self::STATUS_RECEIVED;
                $orderStatus->status_description = $request->status_description;
                $orderStatus->customer_name = $request->customer_name;
                $orderStatus->user_id = auth()->id();
                $orderStatus->save();

                RestaurantAuditService::logItemOrderCreated(
                    $orderStatus->id,
                    (int) $request->table_id,
                    (int) $request->item_id,
                    (int) $request->quantity
                );

                return [
                    'success' => true,
                    'message' => 'Producto agregado con éxito.',
                    'order_status_id' => $orderStatus->id,
                ];
            });

            if (! empty($result['success'])) {
                $this->restaurantSocketEmit(RestaurantSocketEvents::COMMAND_ITEM_CREATED, [
                    'table_id' => $request->table_id ? (int) $request->table_id : null,
                    'item_id' => (int) $request->item_id,
                    'order_status_id' => $result['order_status_id'] ?? null,
                    'quantity' => (int) $request->quantity,
                ]);
                $this->restaurantSocketSync('commands', 'command_item_created', [
                    'table_id' => $request->table_id ? (int) $request->table_id : null,
                ]);
            }

            return $result;
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Pedidos por estado + lista plana `items`.
     * - `{id}` **> 0**: solo esa mesa.
     * - `{id}` **0**: **todas** las líneas de comanda (mesas + pedidos rápidos `table_id` null + cualquier otro dato en BD).
     * Query **only_quick=1** (solo con id **0**): únicamente pedido rápido = `whereNull(table_id)` (sin mesa). No es lo mismo que id 0 sin este flag.
     * Query flat=1 o grouped=0 → solo clave `items` en `data`.
     */
    public function getStatusItems(Request $request, $id)
    {
        $tableId = (int) $id;
        $flatOnly = $this->wantsFlatOnlyGroupedPayload($request);
        $onlyQuick = $request->boolean('only_quick') && $tableId === 0;

        $transformedRows = $this->fetchOrderModelsForTable($tableId, $onlyQuick)
            ->map(fn ($order) => $this->transformOrderData($order));

        $received = $transformedRows->filter(fn (array $r) => (int) $r['status'] === self::STATUS_RECEIVED)->values();
        $processing = $transformedRows->filter(fn (array $r) => (int) $r['status'] === self::STATUS_PROCESSING)->values();
        $toDeliver = $transformedRows->filter(fn (array $r) => (int) $r['status'] === self::STATUS_TO_DELIVER)->values();
        $delivered = $transformedRows->filter(fn (array $r) => (int) $r['status'] === self::STATUS_DELIVERED)->values();

        $allItemsRaw = $received->concat($processing)->concat($toDeliver)->concat($delivered);
        $items = $this->mergeRawIntoItemsCollection($allItemsRaw);

        $meta = [
            'table_filter' => $tableId > 0 ? 'mesa_' . $tableId : 'todas',
            'only_quick' => $onlyQuick,
        ];

        if ($flatOnly) {
            return [
                'success' => true,
                'data' => [
                    'items' => $items->values()->all(),
                ],
                'message' => 'Listado de productos (modo plano).',
                'id' => $tableId,
                'meta' => $meta,
            ];
        }

        $data = [
            'productsStatusReceived' => $this->stripRawItemFromRows($received)->values()->all(),
            'productsStatusProcessing' => $this->stripRawItemFromRows($processing)->values()->all(),
            'productsStatusToDeliver' => $this->stripRawItemFromRows($toDeliver)->values()->all(),
            'productsStatusDelivered' => $this->stripRawItemFromRows($delivered)->values()->all(),
            'items' => $items->values()->all(),
        ];

        return [
            'success' => true,
            'data' => $data,
            'message' => 'Listado de productos por estados.',
            'id' => $tableId,
            'meta' => $meta,
        ];
    }

    /**
     * flat=1 / true o grouped=0 → solo clave `items` en la respuesta (menos trabajo y payload).
     */
    private function wantsFlatOnlyGroupedPayload(Request $request): bool
    {
        if ($request->boolean('flat')) {
            return true;
        }

        $grouped = $request->query('grouped');

        return $grouped === '0' || $grouped === 0;
    }

    /**
     * Estados 1–3 completos + estado 4 limitado a 20 (mismo criterio que el antiguo getItemsByStatus).
     *
     * - $tableId > 0: solo esa mesa (excluye pedidos rápidos con table_id null).
     * - $tableId <= 0 y !$onlyQuick: todas las líneas (mesas + rápidos null + demás).
     * - $tableId <= 0 y $onlyQuick: **solo** pedido rápido (`table_id` **NULL**; no es “todo”).
     */
    private function fetchOrderModelsForTable(int $tableId, bool $onlyQuick = false): Collection
    {
        $with = ['table', 'itemModel.preparationArea'];

        $applyTableScope = function ($query) use ($tableId, $onlyQuick) {
            if ($tableId > 0) {
                return $query->where('table_id', $tableId);
            }
            if ($onlyQuick) {
                return $query->whereNull('table_id');
            }

            return $query;
        };

        $openQuery = RestaurantItemOrderStatus::query()
            ->whereIn('status', [
                self::STATUS_RECEIVED,
                self::STATUS_PROCESSING,
                self::STATUS_TO_DELIVER,
            ])
            ->with($with)
            ->orderBy('updated_at');

        $openQuery = $applyTableScope($openQuery);

        $open = $openQuery->get();

        $deliveredQuery = RestaurantItemOrderStatus::query()
            ->where('status', self::STATUS_DELIVERED)
            ->with($with)
            ->orderBy('updated_at', 'desc')
            ->limit(20);

        $deliveredQuery = $applyTableScope($deliveredQuery);

        $delivered = $deliveredQuery->get();

        return $open->concat($delivered);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $allItemsRaw
     */
    private function mergeRawIntoItemsCollection(Collection $allItemsRaw): Collection
    {
        return $allItemsRaw->map(function (array $row) {
            $raw = $row['raw_item'] ?? [];
            unset($row['raw_item']);

            return array_merge(
                $raw,
                [
                    'order_id' => $row['id'],
                    'item_id' => $row['item_id'],
                    'quantity' => $row['quantity'],
                    'note' => $row['note'],
                    'status' => $row['status'],
                    'status_description' => $row['status_description'],
                    'customer_name' => $row['customer_name'],
                    'mesa_id' => $row['mesa_id'],
                    'mesa' => $row['mesa'],
                    'environment_id' => $row['environment_id'],
                    'environment' => $row['environment'],
                    'preparation_area_id' => $row['preparation_area_id'],
                    'preparation_area_name' => $row['preparation_area_name'],
                    'created_at' => $row['created_at'],
                    'updated_at' => $row['updated_at'],
                ]
            );
        });
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return Collection<int, array<string, mixed>>
     */
    private function stripRawItemFromRows(Collection $rows): Collection
    {
        return $rows->map(function (array $row) {
            unset($row['raw_item']);

            return $row;
        });
    }

    public function isProductsCommandStatusServer($tableId)
    {
        // Contar cuántos productos NO están en estado 4
        $notCompleted = RestaurantItemOrderStatus::where('table_id', $tableId)
            ->where('status', '!=', 4)
            ->count();

        // Si hay alguno que no esté en 4 => false
        return $notCompleted === 0;
    }

    /**
     * Mesas que tienen al menos una línea en comanda (cualquier estado).
     * Al cobrar/generar venta las líneas se eliminan: si la mesa no tiene filas en restaurant_item_order_statuses, no aparece aquí.
     * Pedido rápido = solo `table_id` **NULL** (sin mesa).
     */
    public function tablesWithPendingCommands()
    {
        $quickCount = RestaurantItemOrderStatus::query()
            ->whereNull('table_id')
            ->count();

        $aggregates = RestaurantItemOrderStatus::query()
            ->selectRaw('table_id, COUNT(*) as command_items_count')
            ->whereNotNull('table_id')
            ->where('table_id', '>', 0)
            ->groupBy('table_id')
            ->orderBy('table_id')
            ->get();

        $tableModels = $aggregates->isEmpty()
            ? collect()
            : RestaurantTable::whereIn('id', $aggregates->pluck('table_id')->all())->get()->keyBy('id');

        $tables = $aggregates->map(function ($row) use ($tableModels) {
            $t = $tableModels->get($row->table_id);

            return [
                'table_id' => (int) $row->table_id,
                'label' => $t->label ?? '',
                'environment' => $t->environment ?? null,
                'command_items_count' => (int) $row->command_items_count,
            ];
        })->values();

        return [
            'success' => true,
            'data' => [
                'tables' => $tables,
                'quick_orders_without_table' => [
                    'command_items_count' => $quickCount,
                    'description' => 'Solo pedidos rápidos (table_id NULL). Listado: GET .../command-status/items/0?only_quick=1. Todo el mundo: GET .../items/0 sin only_quick.',
                ],
            ],
            'meta' => [
                'rule' => 'Cualquier estado; solo existen filas no borradas por cobro.',
            ],
        ];
    }

    private function transformOrderData($order)
    {
        $itemData = json_decode($order->item, true) ?: [];
        $table = $order->table;

        return [
            'id' => $order->id,
            'item_id' => $order->item_id,
            'name' => $itemData['name'] ?? null,
            'quantity' => $order->quantity,
            'note' => $order->note ?? null,
            'modifiers_applied' => $itemData['modifiersApplied'] ?? [],
            'status' => $order->status,
            'status_description' => $order->status_description,
            'customer_name' => $order->customer_name,
            'mesa_id' => $order->table_id,
            'mesa' => $table?->label ?? null,
            'environment_id' => $table?->environment_id ?? null,
            'environment' => $table?->environment ?? null,
            'preparation_area_id' => $order->itemModel?->preparation_area_id ?? null,
            'preparation_area_name' => $order->itemModel?->preparationArea?->name ?? null,
            'created_at' => $order->created_at?->toISOString(),
            'updated_at' => $order->updated_at?->toISOString(),
            'raw_item' => $itemData,
        ];
    }

    public function setStatusItem($id)
    {
        $order = RestaurantItemOrderStatus::where('id', $id)->first();

        if (!$order) {
            return [
                'success' => false,
                'message' => 'Orden no encontrada'
            ];
        }

        // Solo incrementar el estado (supplies ya fueron descontados en saveItemOrder)
        if ($order->status < 4) {
            $order->status += 1;
        }
        $order->save();

        // Pedidos rápidos (sin mesa): eliminar registro cuando llega a status 4 (entregado).
        // Los pedidos con mesa se eliminan al generar la venta desde saveTable.
        if ($order->status == self::STATUS_DELIVERED && $order->table_id === null) {
            $this->releaseStockAndDeleteOrder($order);
        }

        $this->restaurantSocketEmit(RestaurantSocketEvents::COMMAND_ITEM_STATUS, [
            'order_status_id' => (int) $order->id,
            'table_id' => $order->table_id ? (int) $order->table_id : null,
            'status' => (int) $order->status,
        ]);
        $this->restaurantSocketSync('commands', 'command_status_changed', [
            'table_id' => $order->table_id ? (int) $order->table_id : null,
        ]);

        return [
            'success' => true,
            'message' => 'Estado cambiado con éxito'
        ];
    }

    /**
     * Libera stock reservado y elimina el registro (para pedidos rápidos sin mesa).
     */
    private function releaseStockAndDeleteOrder(RestaurantItemOrderStatus $order)
    {
        $stockService = app(RestaurantStockService::class);
        $itemData = json_decode($order->item, true);

        try {
            if (is_array($itemData)) {
                if (isset($itemData['has_sets']) && $itemData['has_sets']) {
                    foreach ($itemData['items_sets'] as $itemSet) {
                        $componentQuantity = ($itemSet['pivot']['quantity'] ?? 1) * $order->quantity;
                        $stockService->releaseQuantity($itemSet['id'], $componentQuantity);
                    }
                }
                $stockService->releaseQuantity($order->item_id, $order->quantity);

                if (isset($itemData['modifiersApplied']) && is_array($itemData['modifiersApplied'])) {
                    foreach ($itemData['modifiersApplied'] as $group) {
                        if (isset($group['items']) && is_array($group['items'])) {
                            foreach ($group['items'] as $modifierItem) {
                                if (isset($modifierItem['type'], $modifierItem['item_id'])
                                    && $modifierItem['type'] === 'item' && $modifierItem['item_id']) {
                                    $stockService->releaseQuantity($modifierItem['item_id'], $order->quantity);
                                }
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            \Log::error("Error liberando stock en pedido rápido: " . $e->getMessage());
        }

        $order->delete();
    }

    /**
     * Elimina un ítem de la comanda (un registro de RestaurantItemOrderStatus).
     * Requiere PIN del usuario y razón. Libera stock y registra en auditoría.
     * POST /restaurant/command-item/remove/{id}
     * Body: { "pin": "1234", "reason": "Cliente canceló el plato" }
     */
    public function removeComandaItem(Request $request, $id)
    {
        $request->validate([
            'pin' => 'required|string|size:4',
            'reason' => 'required|string|min:3|max:500',
        ], [
            'pin.required' => 'Debe ingresar el PIN de anulación de 4 dígitos.',
            'pin.size' => 'El PIN debe tener 4 dígitos.',
            'reason.required' => 'Debe indicar el motivo por el que se quita el producto de la comanda.',
            'reason.min' => 'El motivo debe tener al menos 3 caracteres.',
        ]);

        $config = \Modules\Restaurant\Models\RestaurantConfiguration::first();
        $comandaRemovalPin = $config->comanda_removal_pin ?? null;

        if (empty($comandaRemovalPin)) {
            return [
                'success' => false,
                'message' => 'El administrador no ha configurado el PIN para anular comandas. Configurelo en Restaurante > Configuración > Otros permisos.',
                'code' => 'PIN_NOT_SET',
            ];
        }
        if ($comandaRemovalPin !== $request->pin) {
            return [
                'success' => false,
                'message' => 'PIN de anulación incorrecto.',
                'code' => 'PIN_INVALID',
            ];
        }

        $order = RestaurantItemOrderStatus::where('id', $id)->first();
        if (!$order) {
            return [
                'success' => false,
                'message' => 'El ítem de la comanda no existe o ya fue eliminado.',
            ];
        }

        $tableId = (int) $order->table_id;
        $itemId = (int) $order->item_id;
        $quantity = (int) $order->quantity;

        try {
            \DB::connection('tenant')->transaction(function () use ($order, $id, $tableId, $itemId, $quantity, $request) {
                $stockService = app(RestaurantStockService::class);
                $itemData = json_decode($order->item, true);

                if (is_array($itemData)) {
                    if (isset($itemData['has_sets']) && $itemData['has_sets']) {
                        foreach ($itemData['items_sets'] as $itemSet) {
                            $componentQuantity = ($itemSet['pivot']['quantity'] ?? 1) * $order->quantity;
                            $stockService->releaseQuantity($itemSet['id'], $componentQuantity);
                        }
                    }
                    $stockService->releaseQuantity($order->item_id, $order->quantity);
                    if (isset($itemData['modifiersApplied']) && is_array($itemData['modifiersApplied'])) {
                        foreach ($itemData['modifiersApplied'] as $group) {
                            if (isset($group['items']) && is_array($group['items'])) {
                                foreach ($group['items'] as $modifierItem) {
                                    if (isset($modifierItem['type'], $modifierItem['item_id'])
                                        && $modifierItem['type'] === 'item' && $modifierItem['item_id']) {
                                        $stockService->releaseQuantity($modifierItem['item_id'], $order->quantity);
                                    }
                                }
                            }
                        }
                    }
                }

                RestaurantAuditService::logComandaItemRemoved(
                    (int) $order->id,
                    $tableId,
                    $itemId,
                    $quantity,
                    $request->reason
                );

                $order->delete();
            });
        } catch (\Throwable $e) {
            \Log::error('Error eliminando ítem de comanda: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'No se pudo eliminar el ítem. Intente de nuevo.',
            ];
        }

        $this->restaurantSocketEmit(RestaurantSocketEvents::COMMAND_ITEM_REMOVED, [
            'table_id' => $tableId,
            'item_id' => $itemId,
            'order_status_id' => (int) $id,
        ]);
        $this->restaurantSocketSync('commands', 'command_item_removed', ['table_id' => $tableId]);

        return [
            'success' => true,
            'message' => 'Producto quitado de la comanda correctamente.',
        ];
    }

    public function cancellations(Request $request)
    {
        $start = $request->query('start_date');
        $end = $request->query('end_date');
        $perPage = (int)($request->query('per_page', 15));
        $action = $request->query('action');
        $query = \Modules\Restaurant\Models\RestaurantAuditLog::query()
            ->with('user')
            ->orderBy('created_at', 'desc');
        if ($action) {
            $query->where('action', $action);
        } else {
            $query->where('action', \Modules\Restaurant\Models\RestaurantAuditLog::actions()['comanda_item_removed']);
        }
        if ($start && $end) {
            $startDate = \Carbon\Carbon::parse($start)->startOfDay();
            $endDate = \Carbon\Carbon::parse($end)->endOfDay();
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }
        $paginator = $query->paginate($perPage);
        $items = collect($paginator->items())->map(function ($log) {
            $payload = $log->payload ?? [];
            $itemName = null;
            if (isset($payload['item_id'])) {
                $item = \App\Models\Tenant\Item::find($payload['item_id']);
                if ($item) {
                    $itemName = $item->description;
                }
            }
            return [
                'id' => $log->id,
                'action' => $log->action,
                'entity_type' => $log->entity_type,
                'entity_id' => $log->entity_id,
                'user_id' => $log->user_id,
                'user_name' => optional($log->user)->name,
                'ip' => $log->ip,
                'payload' => $payload,
                'item_name' => $itemName,
                'created_at' => optional($log->created_at)->toISOString(),
                'updated_at' => optional($log->updated_at)->toISOString(),
            ];
        });
        return [
            'success' => true,
            'data' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ];
    }

}
