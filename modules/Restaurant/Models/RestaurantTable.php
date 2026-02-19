<?php

namespace Modules\Restaurant\Models;

use App\Models\Tenant\ModelTenant;
use App\Models\Tenant\Document;
use App\Models\Tenant\SaleNote;

class RestaurantTable extends ModelTenant
{
    public $timestamps = false;

    protected $fillable = [
        'status',
        'products',
        'total',
        'personas',
        'cliente',
        'comentarios',
        'label',
        'shape',
        'environment',
        'waiter',
        'opening_date',
        'order_status',
        'group_id',
        'is_active',
        'original_environment',
        'is_paid',
        'delivery',
        'sale_note_id',
        'document_id',
    ];


    public function getProductsAttribute($value)
    {
        return (is_null($value))?null:(object) json_decode($value);
    }

    public function setProductsAttribute($value)
    {
        $this->attributes['products'] = (is_null($value))?null:json_encode($value);
    }

    protected $guarded = [
        'id',
        'group_id',      // Solo se puede modificar mediante métodos específicos
        'is_main_table', // Solo se puede modificar mediante métodos específicos
    ];

    protected $casts = [
        'products' => 'array',
        'total' => 'float',
        'is_active' => 'boolean',
        'opening_date' => 'datetime',
        'delivery' => 'array',
        'is_paid' => 'boolean',
    ];

    public function group()
    {
        return $this->belongsTo(RestaurantTableGroup::class, 'group_id');
    }

    public function saleNote()
    {
        return $this->belongsTo(SaleNote::class, 'sale_note_id');
    }

    public function document()
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    public function itemOrderStatuses()
    {
        return $this->hasMany(RestaurantItemOrderStatus::class, 'table_id');
    }
}
