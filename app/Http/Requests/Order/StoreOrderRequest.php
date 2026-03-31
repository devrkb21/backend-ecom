<?php

namespace App\Http\Requests\Order;

use App\Models\PaymentGateway;
use App\Models\ShippingMethod;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shipping_name' => ['required', 'string', 'max:255'],
            'shipping_email' => ['required', 'email', 'max:255'],
            'shipping_phone' => ['nullable', 'string', 'max:20'],
            'shipping_address' => ['required', 'string', 'max:500'],
            'shipping_city' => ['required', 'string', 'max:100'],
            'shipping_state' => ['nullable', 'string', 'max:100'],
            'shipping_zip' => ['required', 'string', 'max:20'],
            'shipping_country' => ['required', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'shipping_method' => ['required', 'string', function ($attribute, $value, $fail) {
                $method = ShippingMethod::findByCode($value);
                if (!$method || !$method->is_active) {
                    $fail('The selected shipping method is not available.');
                }
            }],
            'payment_method' => ['required', 'string', function ($attribute, $value, $fail) {
                $gateway = PaymentGateway::findByCode($value);
                if (!$gateway || !$gateway->is_active) {
                    $fail('The selected payment method is not available.');
                }
            }],
        ];
    }

    public function messages(): array
    {
        return [
            'shipping_method.required' => 'Please select a shipping method.',
            'payment_method.required' => 'Please select a payment method.',
        ];
    }
}
