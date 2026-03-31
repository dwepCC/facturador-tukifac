<?php

namespace App\Models\System;

use Hyn\Tenancy\Traits\UsesSystemConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class ClientDocumentPackage extends Model
{
    use UsesSystemConnection;

    protected $fillable = [
        'client_id',
        'units_total',
        'price',
        'units_consumed',
        'include_sale_notes',
        'cycle_start_at',
        'cycle_end_at',
        'status',
    ];

    protected $casts = [
        'client_id' => 'int',
        'units_total' => 'int',
        'price' => 'decimal:2',
        'units_consumed' => 'int',
        'include_sale_notes' => 'boolean',
        'cycle_start_at' => 'date',
        'cycle_end_at' => 'date',
    ];

    public function scopeActiveForCycle(Builder $query, int $clientId, string $cycleStartAt, string $cycleEndAt): Builder
    {
        return $query
            ->where('client_id', $clientId)
            ->where('status', 'active')
            ->whereDate('cycle_start_at', $cycleStartAt)
            ->whereDate('cycle_end_at', $cycleEndAt);
    }

    public function getRemainingUnitsAttribute(): int
    {
        $remaining = $this->units_total - $this->units_consumed;
        return $remaining > 0 ? $remaining : 0;
    }
}
