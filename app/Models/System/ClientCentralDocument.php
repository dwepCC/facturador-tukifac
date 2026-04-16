<?php

namespace App\Models\System;

use Hyn\Tenancy\Traits\UsesSystemConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientCentralDocument extends Model
{
    use UsesSystemConnection;

    protected $table = 'client_central_documents';

    protected $fillable = [
        'client_id',
        'tenant_document_id',
        'date_of_issue',
        'document_type_id',
        'state_type_id',
        'regularize_shipping',
        'send_to_pse',
        'currency_type_id',
        'exchange_rate_sale',
        'total',
    ];

    protected $casts = [
        'date_of_issue' => 'date',
        'regularize_shipping' => 'boolean',
        'send_to_pse' => 'boolean',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }
}
