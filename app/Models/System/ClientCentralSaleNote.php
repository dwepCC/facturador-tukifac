<?php

namespace App\Models\System;

use Hyn\Tenancy\Traits\UsesSystemConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientCentralSaleNote extends Model
{
    use UsesSystemConnection;

    protected $table = 'client_central_sale_notes';

    protected $fillable = [
        'client_id',
        'tenant_sale_note_id',
        'date_of_issue',
        'state_type_id',
        'changed',
        'currency_type_id',
        'exchange_rate_sale',
        'total',
    ];

    protected $casts = [
        'date_of_issue' => 'date',
        'changed' => 'boolean',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }
}
