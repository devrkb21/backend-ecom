<?php

namespace App\Http\Requests\Order;

use App\Models\LandingPage;
use App\Models\PaymentGateway;
use App\Models\ShippingMethod;
use App\Services\CheckoutAddressConfigService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    protected ?array $cachedCheckoutConfig = null;

    protected ?array $cachedEnabledFields = null;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $enabledFields = $this->enabledCheckoutFields();
        $isGuestCheckout = ! $this->user('sanctum') && ! $this->user();
        $useBillingAddress = $this->toBoolean($this->input('use_billing_address', false));

        $rules = [
            'checkout_fields' => ['nullable', 'array'],
            'use_billing_address' => ['nullable', 'boolean'],
            'items' => [$isGuestCheckout ? 'required' : 'nullable', 'array', 'min:1'],
            'items.*.product_id' => [Rule::requiredIf($isGuestCheckout), 'integer', Rule::exists('products', 'id')],
            'items.*.variant_id' => ['nullable', 'integer', Rule::exists('product_variants', 'id')],
            'items.*.quantity' => [Rule::requiredIf($isGuestCheckout), 'integer', 'min:1', 'max:100'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
            'shipping_method' => ['required', 'string', function ($attribute, $value, $fail) {
                $method = ShippingMethod::findByCode($value);
                if (! $method || ! $method->is_active) {
                    $fail('The selected shipping method is not available.');
                }
            }],
            'payment_method' => ['required', 'string', function ($attribute, $value, $fail) {
                $gateway = PaymentGateway::findByCode($value);
                if (! $gateway || ! $gateway->is_active) {
                    $fail('The selected payment method is not available.');
                }
            }],

            // Backward compatibility: still allow legacy root-level notes.
            'notes' => ['nullable', 'string', 'max:1000'],
        ];

        $landingPageSlug = $this->input('landing_page_slug');
        $showLocation = true;
        if ($landingPageSlug) {
            $lp = LandingPage::where('slug', $landingPageSlug)->first();
            if ($lp && ! $lp->show_location) {
                $showLocation = false;
            }
        }

        foreach ($enabledFields as $field) {
            $key = (string) ($field['key'] ?? '');
            if ($key === '') {
                continue;
            }

            if (! $showLocation && in_array($field['type'] ?? '', ['location_division', 'location_district', 'location_upazila', 'location_union'], true)) {
                continue;
            }

            $rules["checkout_fields.{$key}"] = $this->buildCheckoutFieldRules($field, $useBillingAddress);
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'checkout_fields.*.required' => 'This field is required.',
            'checkout_fields.*.regex' => 'Invalid format for this field.',
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
        $enabledFields = $this->enabledCheckoutFields();
        $useBillingAddress = $this->toBoolean($this->input('use_billing_address', false));
        $incomingFields = $this->input('checkout_fields');
        if (! is_array($incomingFields)) {
            $incomingFields = [];
        }

        foreach ($enabledFields as $field) {
            $key = (string) ($field['key'] ?? '');
            if ($key === '') {
                continue;
            }

            if (! array_key_exists($key, $incomingFields) && $this->has($key)) {
                $incomingFields[$key] = $this->input($key);
            }
        }

        $country = $incomingFields['shipping_country'] ?? null;

        if ($country === 'BD' || empty($country)) {
            if (array_key_exists('shipping_country', $incomingFields)) {
                $incomingFields['shipping_country'] = 'Bangladesh';
            }
        }

        $locationText = trim((string) ($incomingFields['shipping_location_text'] ?? ''));
        if ($locationText === '') {
            $locationText = trim((string) ($incomingFields['shipping_address'] ?? ''));
        }

        if ($locationText !== '' || array_key_exists('shipping_location_text', $incomingFields)) {
            $incomingFields['shipping_location_text'] = $locationText;
        }

        // Backward compatibility for legacy notes key.
        if (! array_key_exists('order_notes', $incomingFields) && $this->filled('notes')) {
            $incomingFields['order_notes'] = $this->input('notes');
        }

        $this->merge([
            'checkout_fields' => $incomingFields,
            'use_billing_address' => $useBillingAddress,
        ]);
    }

    public function validated($key = null, $default = null): mixed
    {
        $validated = parent::validated($key, $default);

        if (! is_array($validated)) {
            return $validated;
        }

        $checkoutFields = $validated['checkout_fields'] ?? [];
        if (! is_array($checkoutFields)) {
            $checkoutFields = [];
        }

        // Keep legacy shape for service/controller compatibility.
        foreach ($checkoutFields as $fieldKey => $fieldValue) {
            if (! array_key_exists($fieldKey, $validated)) {
                $validated[$fieldKey] = $fieldValue;
            }
        }

        if (! array_key_exists('notes', $validated) && array_key_exists('order_notes', $checkoutFields)) {
            $validated['notes'] = $checkoutFields['order_notes'];
        }

        return $validated;
    }

    protected function buildCheckoutFieldRules(array $field, bool $useBillingAddress): array
    {
        $rules = [];
        $section = strtolower(trim((string) ($field['section'] ?? 'shipping')));
        $isBillingField = $section === 'billing';
        $isRequired = ! empty($field['required']) && (! $isBillingField || $useBillingAddress);
        $type = strtolower(trim((string) ($field['type'] ?? 'text')));

        $rules[] = $isRequired ? 'required' : 'nullable';

        switch ($type) {
            case 'textarea':
                $rules[] = 'string';
                $rules[] = 'max:2000';
                break;

            case 'email':
                $rules[] = 'string';
                $rules[] = 'email';
                $rules[] = 'max:255';
                break;

            case 'tel':
                $rules[] = 'string';
                $rules[] = 'max:30';
                $rules[] = 'regex:/^[0-9+\-\s()]{7,30}$/';
                break;

            case 'number':
                $rules[] = 'numeric';
                break;

            case 'select':
                $rules[] = 'string';
                $options = is_array($field['options'] ?? null) ? $field['options'] : [];
                $allowed = collect($options)
                    ->map(fn ($option) => is_array($option) ? trim((string) ($option['value'] ?? '')) : '')
                    ->filter(fn ($value) => $value !== '')
                    ->values()
                    ->all();

                if (! empty($allowed)) {
                    $rules[] = Rule::in($allowed);
                }
                break;

            case 'country':
                $rules[] = 'string';
                $rules[] = 'max:100';
                break;

            case 'location_division':
                $rules[] = 'integer';
                $rules[] = Rule::exists('bd_divisions', 'id');
                break;

            case 'location_district':
                $rules[] = 'integer';
                $rules[] = Rule::exists('bd_districts', 'id');
                break;

            case 'location_upazila':
                $rules[] = 'integer';
                $rules[] = Rule::exists('bd_upazilas', 'id');
                break;

            case 'location_union':
                $rules[] = 'integer';
                $rules[] = Rule::exists('bd_unions', 'id');
                break;

            case 'location_text':
                $rules[] = 'string';
                $rules[] = 'max:255';
                break;

            default:
                $rules[] = 'string';
                $rules[] = 'max:255';
                break;
        }

        $rules = $this->applyCustomValidationRules($rules, $field);

        return array_values(array_unique($rules, SORT_REGULAR));
    }

    protected function applyCustomValidationRules(array $rules, array $field): array
    {
        $validations = is_array($field['validations'] ?? null) ? $field['validations'] : [];

        foreach ($validations as $validation) {
            $name = strtolower(trim((string) $validation));

            if ($name === '') {
                continue;
            }

            if ($name === 'email') {
                $rules[] = 'email';

                continue;
            }

            if ($name === 'phone') {
                $rules[] = 'regex:/^[0-9+\-\s()]{7,30}$/';

                continue;
            }

            if (in_array($name, ['numeric', 'url', 'alpha_num'], true)) {
                $rules[] = $name;
            }
        }

        return $rules;
    }

    protected function enabledCheckoutFields(): array
    {
        if ($this->cachedEnabledFields !== null) {
            return $this->cachedEnabledFields;
        }

        $config = $this->checkoutConfig();
        $schema = $config['checkout_fields_schema'] ?? [];

        $enabled = [];
        foreach (['billing', 'shipping', 'additional'] as $section) {
            $fields = $schema[$section] ?? [];
            if (! is_array($fields)) {
                continue;
            }

            foreach ($fields as $field) {
                if (! is_array($field) || empty($field['enabled'])) {
                    continue;
                }

                $enabled[] = $field;
            }
        }

        $this->cachedEnabledFields = $enabled;

        return $this->cachedEnabledFields;
    }

    protected function toBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $config = $this->checkoutConfig();
            $isGuestCheckout = ! $this->user('sanctum') && ! $this->user();

            if (! (bool) $config['checkout_form_enabled']) {
                $validator->errors()->add('checkout', 'Checkout is currently disabled by admin settings.');
            }

            if ($isGuestCheckout && ! (bool) ($config['enable_guest_checkout'] ?? true)) {
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
}
