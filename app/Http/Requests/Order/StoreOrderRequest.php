<?php

namespace App\Http\Requests\Order;

use App\Models\PaymentGateway;
use App\Models\ShippingMethod;
use App\Services\CheckoutAddressConfigService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    protected ?array $cachedCheckoutConfig = null;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $config = $this->checkoutConfig();
        $isGuestCheckout = !$this->user('sanctum') && !$this->user();
        $dropdownEnabled = (bool) ($config['enable_dropdown_location'] ?? false);
        $locationTextRules = ['required', 'string', 'max:255'];

        $shippingDivisionRules = !$dropdownEnabled
            ? ['exclude']
            : ['nullable', 'integer', Rule::exists('bd_divisions', 'id')];

        $shippingDistrictRules = !$dropdownEnabled
            ? ['exclude']
            : ['nullable', 'integer', Rule::exists('bd_districts', 'id')];

        $shippingUpazilaRules = !$dropdownEnabled
            ? ['exclude']
            : ['nullable', 'integer', Rule::exists('bd_upazilas', 'id')];

        $shippingUnionRules = !$dropdownEnabled
            ? ['exclude']
            : ['nullable', 'integer', Rule::exists('bd_unions', 'id')];

        $shippingEmailRules = $this->fieldRules(
            (bool) $config['show_shipping_email'],
            (bool) $config['require_shipping_email'],
            ['email', 'max:255']
        );

        $shippingPhoneRules = $this->fieldRules(
            (bool) $config['show_shipping_phone'],
            (bool) $config['require_shipping_phone'],
            ['string', 'max:20', 'regex:/^[0-9+\-\s()]{7,20}$/']
        );

        $shippingCityRules = ['nullable', 'string', 'max:100'];

        return [
            'shipping_name' => ['required', 'string', 'max:255'],
            'shipping_email' => $shippingEmailRules,
            'shipping_phone' => $shippingPhoneRules,
            'shipping_address' => ['required', 'string', 'max:500'],
            'shipping_area' => $this->fieldRules(
                (bool) $config['show_shipping_area'],
                (bool) $config['require_shipping_area'],
                ['string', 'max:255']
            ),
            'shipping_location_text' => $locationTextRules,
            'shipping_division_id' => $shippingDivisionRules,
            'shipping_district_id' => $shippingDistrictRules,
            'shipping_upazila_id' => $shippingUpazilaRules,
            'shipping_union_id' => $shippingUnionRules,
            'division_id' => ['exclude'],
            'district_id' => ['exclude'],
            'upazila_id' => ['exclude'],
            'shipping_city' => $shippingCityRules,
            'shipping_state' => ['nullable', 'string', 'max:100'],
            'shipping_zip' => $this->fieldRules(
                (bool) $config['show_shipping_zip'],
                (bool) $config['require_shipping_zip'],
                ['string', 'max:20']
            ),
            'shipping_country' => ['nullable', 'string', Rule::in(['Bangladesh', 'BD'])],
            'items' => [$isGuestCheckout ? 'required' : 'nullable', 'array', 'min:1'],
            'items.*.product_id' => [Rule::requiredIf($isGuestCheckout), 'integer', Rule::exists('products', 'id')],
            'items.*.quantity' => [Rule::requiredIf($isGuestCheckout), 'integer', 'min:1', 'max:100'],
            'notes' => $this->fieldRules(
                (bool) $config['show_order_notes'],
                (bool) $config['require_order_notes'],
                ['string', 'max:1000']
            ),
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
            'shipping_email.required' => 'Shipping email is required.',
            'shipping_phone.required' => 'Shipping phone is required.',
            'shipping_phone.regex' => 'Shipping phone format is invalid.',
            'shipping_location_text.required' => 'Please provide your location text (for example: area, upazila, district).',
            'items.required' => 'At least one item is required for guest checkout.',
            'items.array' => 'Order items must be a valid list.',
            'items.min' => 'At least one item is required for guest checkout.',
            'items.*.product_id.required' => 'Each guest checkout item must include a product.',
            'items.*.quantity.required' => 'Each guest checkout item must include quantity.',
            'shipping_method.required' => 'Please select a shipping method.',
            'payment_method.required' => 'Please select a payment method.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $country = $this->input('shipping_country');

        if ($country === 'BD' || empty($country)) {
            $this->merge(['shipping_country' => 'Bangladesh']);
        }

        $locationText = trim((string) $this->input('shipping_location_text', ''));
        if ($locationText === '') {
            $locationText = trim((string) $this->input('shipping_address', ''));
        }

        $this->merge(['shipping_location_text' => $locationText]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $config = $this->checkoutConfig();
            $isGuestCheckout = !$this->user('sanctum') && !$this->user();

            if (!(bool) $config['checkout_form_enabled']) {
                $validator->errors()->add('checkout', 'Checkout is currently disabled by admin settings.');
            }

            if ($isGuestCheckout && !(bool) ($config['enable_guest_checkout'] ?? true)) {
                $validator->errors()->add('checkout', 'Guest checkout is currently disabled. Please login to continue.');
            }
        });
    }

    protected function checkoutConfig(): array
    {
        if ($this->cachedCheckoutConfig !== null) {
            return $this->cachedCheckoutConfig;
        }

        /** @var CheckoutAddressConfigService $service */
        $service = app(CheckoutAddressConfigService::class);
        $this->cachedCheckoutConfig = $service->getRaw();

        return $this->cachedCheckoutConfig;
    }

    protected function fieldRules(bool $isEnabled, bool $isRequired, array $baseRules): array
    {
        if (!$isEnabled) {
            return ['exclude'];
        }

        if ($isRequired) {
            return array_merge(['required'], $baseRules);
        }

        return array_merge(['nullable'], $baseRules);
    }
}
