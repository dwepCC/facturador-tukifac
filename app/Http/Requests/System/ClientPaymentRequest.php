<?php

namespace App\Http\Requests\System;

use Illuminate\Foundation\Http\FormRequest;

class ClientPaymentRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'id' => ['nullable', 'integer', 'exists:client_payments,id'],
            'date_of_payment' => ['required', 'date'],
            'payment_method_type_id' => ['required', 'integer'],
            'card_brand_id' => ['nullable', 'integer'],
            'reference' => ['nullable', 'string', 'max:255'],
            'payment' => ['required', 'numeric'],
            'ending_billing_cycle' => ['nullable', 'date'],
        ];
    }
}
