<?php

namespace Modules\Restaurant\Models;

use App\Models\Tenant\ModelTenant;
use App\Models\Tenant\Item;
use App\Models\Tenant\User;

class RestaurantItemOrderStatus extends ModelTenant
{
    const STATUS_RECEIVED = 1;
    const STATUS_PROCESSING = 2;
    const STATUS_TO_DELIVER = 3;
    const STATUS_DELIVERED = 4;

    protected $fillable = [
        'table_id',
        'item_id',
        'item',
        'quantity',
        'note',
        'status',
        'status_description',
        'customer_name',
        'user_id',
    ];

    public function table()
    {
        return $this->belongsTo(RestaurantTable::class, 'table_id');
    }

    public function itemModel()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
