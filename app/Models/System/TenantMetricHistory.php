<?php

namespace App\Models\System;

use Hyn\Tenancy\Traits\UsesSystemConnection;
use Illuminate\Database\Eloquent\Model;

class TenantMetricHistory extends Model
{
    use UsesSystemConnection;

    protected $table = 'tenant_metric_history';

    protected $fillable = [
        'client_id',
        'metric_type',
        'event_type',
        'value',
        'reference_type',
        'reference_id',
        'event_date',
        'metadata',
    ];

    protected $casts = [
        'event_date' => 'datetime',
        'metadata' => 'array',
    ];
}
