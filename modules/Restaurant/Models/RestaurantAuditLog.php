<?php

namespace Modules\Restaurant\Models;

use App\Models\Tenant\ModelTenant;
use App\Models\Tenant\User;

class RestaurantAuditLog extends ModelTenant
{
    protected $table = 'restaurant_audit_logs';

    protected $fillable = [
        'action',
        'entity_type',
        'entity_id',
        'user_id',
        'payload',
        'ip',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function actions(): array
    {
        return [
            'table_closed' => 'table_closed',
            'orders_deleted' => 'orders_deleted',
            'table_config_regenerated' => 'table_config_regenerated',
            'item_order_created' => 'item_order_created',
            'status_updated' => 'status_updated',
            'comanda_item_removed' => 'comanda_item_removed',
        ];
    }
}
