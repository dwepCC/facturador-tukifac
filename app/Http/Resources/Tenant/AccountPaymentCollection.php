<?php

namespace App\Http\Resources\Tenant;

use Illuminate\Http\Resources\Json\ResourceCollection;

class AccountPaymentCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function toArray($request)
    {
        return $this->collection->transform(function($row, $key) {
            $isPaid = (bool) $row->state;
            $hasVoucher = !empty($row->reference_payment);

            $stateCode = 'debt';
            $stateDescription = 'Deuda';
            $stateBadgeClass = 'badge-danger';

            if ($isPaid) {
                $stateCode = 'paid';
                $stateDescription = 'Pagado';
                $stateBadgeClass = 'badge-success';
            } elseif ($hasVoucher) {
                $stateCode = 'pending';
                $stateDescription = 'Pendiente';
                $stateBadgeClass = 'badge-warning';
            }

            return [
                'id' => $row->id,
                'date_of_payment' => $row->date_of_payment->format('d/m/Y'),
                'date_of_payment_real' => ($row->date_of_payment_real) ? $row->date_of_payment_real->format('d/m/Y') : "",
                'comentario' => $row->reference,
                'payment' => $row->payment, 
                'state' => $isPaid,
                'reference_payment' => $row->reference_payment,
                'receipt_pdf' => $row->receipt_pdf,
                'has_receipt_pdf' => !empty($row->receipt_pdf),
                'state_code' => $stateCode,
                'state_badge_class' => $stateBadgeClass,
                'state_description' => $stateDescription,
            ];
        });
    }
}
