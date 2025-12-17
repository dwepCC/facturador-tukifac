<?php

namespace App\Http\Resources\Tenant;

use Illuminate\Http\Resources\Json\ResourceCollection;

class SaleNoteLightCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return $this->collection->transform(function ($row) {
            return [
                'id' => $row->id,
                'external_id' => $row->external_id,
                'date_of_issue' => $row->date_of_issue ? $row->date_of_issue->format('Y-m-d') : null,
                'full_number' => $row->series.'-'.$row->number,
                'number_full' => $row->number_full,
                'seller_name' => optional($row->user)->name,
                'customer_name' => optional($row->person)->name,
                'customer_number' => optional($row->person)->number,
                'currency_type_id' => $row->currency_type_id,
                'total' => number_format($row->total, 2),
                'state_type_id' => $row->state_type_id,
                'state_type_description' => optional($row->state_type)->description,
                'paid' => (bool) $row->paid,
                'total_canceled' => (bool) $row->total_canceled,
                'print_a4' => url('')."/sale-notes/print/{$row->external_id}/a4",
            ];
        });
    }
}

