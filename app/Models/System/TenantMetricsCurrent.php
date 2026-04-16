<?php

namespace App\Models\System;

use Hyn\Tenancy\Traits\UsesSystemConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantMetricsCurrent extends Model
{
    use UsesSystemConnection;

    protected $table = 'tenant_metrics_current';

    protected $primaryKey = 'client_id';

    public $incrementing = false;

    protected $fillable = [
        'client_id',
        'total_users',
        'total_establishments',
        'soap_type_id',
        'total_documents',
        'total_documents_pse',
        'current_month_documents',
        'total_sales_notes',
        'pending_regularize_shipping',
        'pending_not_sent',
        'pending_to_be_canceled',
        'pending_rejected',
        'pending_observed',
        'monthly_sales_total_cached',
        'metrics_last_synced_at',
    ];

    protected $casts = [
        'metrics_last_synced_at' => 'datetime',
        'monthly_sales_total_cached' => 'decimal:2',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }
}
