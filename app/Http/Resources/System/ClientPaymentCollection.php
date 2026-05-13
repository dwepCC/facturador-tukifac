<?php

namespace App\Http\Resources\System;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ClientPaymentCollection extends ResourceCollection
{
    /**
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function toArray($request)
    {
        return $this->collection->transform(function ($row) {
            return [
                'id' => $row->id,
                'payment_order_id' => $row->payment_order_id,
                'date_of_payment' => $row->date_of_payment->format('d/m/Y'),
                'date_of_payment_iso' => $row->date_of_payment->format('Y-m-d'),
                'payment_method_type_id' => $row->payment_method_type_id,
                'payment_method_type_description' => $row->payment_method_type->description,
                'card_brand' => $row->card_brand,
                'card_brand_id' => $row->card_brand_id,
                'reference' => $row->reference,
                'payment' => $row->payment,
                'state' => $row->state,
                'state_description' => ($row->state) ? 'Pagado' : (($row->date_of_payment >= date('Y-m-d')) ? 'Pendiente' : 'Vencido'),
            ];
        });
    }
}
