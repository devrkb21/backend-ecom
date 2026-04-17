<?php

namespace App\Services;

use App\Models\Setting;

class CheckoutAddressConfigService
{
    protected const DEFAULTS = [
        'checkout_form_enabled' => true,
        'enable_guest_checkout' => true,
        'enable_dropdown_location' => true,
        'enable_text_location' => true,
        'require_dropdown_location' => false,
        'require_text_location' => false,
        'show_shipping_email' => true,
        'require_shipping_email' => true,
        'show_shipping_phone' => true,
        'require_shipping_phone' => false,
        'show_shipping_zip' => true,
        'require_shipping_zip' => true,
        'show_shipping_area' => true,
        'require_shipping_area' => false,
        'show_order_notes' => true,
        'require_order_notes' => false,
    ];

    public function getRaw(): array
    {
        $values = [];

        foreach (self::DEFAULTS as $key => $default) {
            $values[$key] = $this->toBool(Setting::getValue('checkout', $key, $default));
        }

        return $values;
    }

    public function getFrontendConfig(): array
    {
        $raw = $this->getRaw();

        $locationMode = 'both';
        if ($raw['enable_dropdown_location'] && !$raw['enable_text_location']) {
            $locationMode = 'dropdown';
        } elseif (!$raw['enable_dropdown_location'] && $raw['enable_text_location']) {
            $locationMode = 'text';
        } elseif (!$raw['enable_dropdown_location'] && !$raw['enable_text_location']) {
            $locationMode = 'disabled';
        }

        return [
            'checkout_form_enabled' => $raw['checkout_form_enabled'],
            'guest_checkout_enabled' => $raw['enable_guest_checkout'],
            'location' => [
                'mode' => $locationMode,
                'enable_dropdown' => $raw['enable_dropdown_location'],
                'enable_text' => $raw['enable_text_location'],
                'require_dropdown' => $raw['require_dropdown_location'],
                'require_text' => $raw['require_text_location'],
            ],
            'fields' => [
                'shipping_email' => [
                    'enabled' => $raw['show_shipping_email'],
                    'required' => $raw['show_shipping_email'] && $raw['require_shipping_email'],
                ],
                'shipping_phone' => [
                    'enabled' => $raw['show_shipping_phone'],
                    'required' => $raw['show_shipping_phone'] && $raw['require_shipping_phone'],
                ],
                'shipping_zip' => [
                    'enabled' => $raw['show_shipping_zip'],
                    'required' => $raw['show_shipping_zip'] && $raw['require_shipping_zip'],
                ],
                'shipping_area' => [
                    'enabled' => $raw['show_shipping_area'],
                    'required' => $raw['show_shipping_area'] && $raw['require_shipping_area'],
                ],
                'order_notes' => [
                    'enabled' => $raw['show_order_notes'],
                    'required' => $raw['show_order_notes'] && $raw['require_order_notes'],
                ],
            ],
        ];
    }

    protected function toBool(mixed $value): bool
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
}
