<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'payment_method' => ['required', 'string', 'in:credit_card,paypal,bank_transfer,cash_on_delivery'],
            'payment_details' => ['nullable', 'array'],
            'payment_details.card_number' => ['required_if:payment_method,credit_card', 'string'],
            'payment_details.expiry_date' => ['required_if:payment_method,credit_card', 'string'],
            'payment_details.cvv' => ['required_if:payment_method,credit_card', 'string'],
        ];
    }
}
