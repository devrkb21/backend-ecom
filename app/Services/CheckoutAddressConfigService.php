<?php

namespace App\Services;

use App\Models\Setting;

class CheckoutAddressConfigService
{
    protected const DEFAULTS = [
        'checkout_form_enabled' => true,
        'enable_guest_checkout' => true,
        'tax_enabled' => false,
        'tax_percentage' => 0.0,
    ];

    protected const SECTION_LABELS = [
        'billing' => 'Billing Fields',
        'shipping' => 'Shipping Fields',
        'additional' => 'Additional Fields',
    ];

    protected const ALLOWED_TYPES = [
        'text',
        'textarea',
        'email',
        'tel',
        'number',
        'select',
        'country',
        'location_text',
        'location_division',
        'location_district',
        'location_upazila',
        'location_union',
    ];

    protected const DEFAULT_FIELD_SCHEMA = [
        'billing' => [
            [
                'id' => 'billing_first_name',
                'key' => 'billing_first_name',
                'type' => 'text',
                'label' => 'First name',
                'placeholder' => 'First name',
                'required' => true,
                'enabled' => true,
                'validations' => [],
            ],
            [
                'id' => 'billing_last_name',
                'key' => 'billing_last_name',
                'type' => 'text',
                'label' => 'Last name',
                'placeholder' => 'Last name',
                'required' => true,
                'enabled' => true,
                'validations' => [],
            ],
            [
                'id' => 'billing_country',
                'key' => 'billing_country',
                'type' => 'country',
                'label' => 'Country / Region',
                'placeholder' => 'Country / Region',
                'required' => true,
                'enabled' => true,
                'validations' => [],
            ],
            [
                'id' => 'billing_address_1',
                'key' => 'billing_address_1',
                'type' => 'text',
                'label' => 'Street address',
                'placeholder' => 'House number and street name',
                'required' => true,
                'enabled' => true,
                'validations' => [],
            ],
            [
                'id' => 'billing_address_2',
                'key' => 'billing_address_2',
                'type' => 'text',
                'label' => 'Apartment, suite, unit, etc.',
                'placeholder' => 'Apartment, suite, unit, etc. (optional)',
                'required' => false,
                'enabled' => true,
                'validations' => [],
            ],
            [
                'id' => 'billing_city',
                'key' => 'billing_city',
                'type' => 'text',
                'label' => 'Town / City',
                'placeholder' => 'Town / City',
                'required' => true,
                'enabled' => true,
                'validations' => [],
            ],
            [
                'id' => 'billing_state',
                'key' => 'billing_state',
                'type' => 'text',
                'label' => 'State / County',
                'placeholder' => 'State / County',
                'required' => true,
                'enabled' => true,
                'validations' => [],
            ],
            [
                'id' => 'billing_postcode',
                'key' => 'billing_postcode',
                'type' => 'text',
                'label' => 'Postcode / ZIP',
                'placeholder' => 'Postcode / ZIP',
                'required' => false,
                'enabled' => true,
                'validations' => [],
            ],
            [
                'id' => 'billing_email',
                'key' => 'billing_email',
                'type' => 'email',
                'label' => 'Email address',
                'placeholder' => 'Email address',
                'required' => true,
                'enabled' => true,
                'validations' => ['email'],
            ],
            [
                'id' => 'billing_phone',
                'key' => 'billing_phone',
                'type' => 'tel',
                'label' => 'Phone',
                'placeholder' => 'Phone',
                'required' => false,
                'enabled' => true,
                'validations' => ['phone'],
            ],
        ],
        'shipping' => [
            [
                'id' => 'shipping_name',
                'key' => 'shipping_name',
                'type' => 'text',
                'label' => 'Full Name',
                'placeholder' => 'Full Name',
                'required' => true,
                'enabled' => true,
                'validations' => [],
            ],
            [
                'id' => 'shipping_address',
                'key' => 'shipping_address',
                'type' => 'text',
                'label' => 'Address',
                'placeholder' => 'Address',
                'required' => true,
                'enabled' => true,
                'validations' => [],
            ],
            [
                'id' => 'shipping_location_text',
                'key' => 'shipping_location_text',
                'type' => 'location_text',
                'label' => 'Location Text',
                'placeholder' => 'Area, upazila, district',
                'required' => false,
                'enabled' => true,
                'validations' => [],
            ],
            [
                'id' => 'shipping_division_id',
                'key' => 'shipping_division_id',
                'type' => 'location_division',
                'label' => 'Division',
                'placeholder' => 'Select Division',
                'required' => false,
                'enabled' => true,
                'validations' => [],
            ],
            [
                'id' => 'shipping_district_id',
                'key' => 'shipping_district_id',
                'type' => 'location_district',
                'label' => 'District',
                'placeholder' => 'Select District',
                'required' => false,
                'enabled' => true,
                'validations' => [],
            ],
            [
                'id' => 'shipping_upazila_id',
                'key' => 'shipping_upazila_id',
                'type' => 'location_upazila',
                'label' => 'Upazila',
                'placeholder' => 'Select Upazila',
                'required' => false,
                'enabled' => true,
                'validations' => [],
            ],
            [
                'id' => 'shipping_union_id',
                'key' => 'shipping_union_id',
                'type' => 'location_union',
                'label' => 'Union',
                'placeholder' => 'Select Union',
                'required' => false,
                'enabled' => true,
                'validations' => [],
            ],
            [
                'id' => 'shipping_city',
                'key' => 'shipping_city',
                'type' => 'text',
                'label' => 'City',
                'placeholder' => 'City',
                'required' => false,
                'enabled' => true,
                'validations' => [],
            ],
            [
                'id' => 'shipping_state',
                'key' => 'shipping_state',
                'type' => 'text',
                'label' => 'State',
                'placeholder' => 'State',
                'required' => false,
                'enabled' => true,
                'validations' => [],
            ],
            [
                'id' => 'shipping_zip',
                'key' => 'shipping_zip',
                'type' => 'text',
                'label' => 'ZIP / Postcode',
                'placeholder' => 'ZIP / Postcode',
                'required' => false,
                'enabled' => true,
                'validations' => [],
            ],
            [
                'id' => 'shipping_country',
                'key' => 'shipping_country',
                'type' => 'country',
                'label' => 'Country',
                'placeholder' => 'Country',
                'required' => true,
                'enabled' => true,
                'validations' => [],
            ],
        ],
        'additional' => [
            [
                'id' => 'order_notes',
                'key' => 'order_notes',
                'type' => 'textarea',
                'label' => 'Order Notes',
                'placeholder' => 'Special instructions for delivery...',
                'required' => false,
                'enabled' => true,
                'validations' => [],
            ],
        ],
    ];

    public function getRaw(): array
    {
        $values = [
            'checkout_form_enabled' => $this->toBool(Setting::getValue('checkout', 'checkout_form_enabled', self::DEFAULTS['checkout_form_enabled'])),
            'enable_guest_checkout' => $this->toBool(Setting::getValue('checkout', 'enable_guest_checkout', self::DEFAULTS['enable_guest_checkout'])),
            'tax_enabled' => $this->toBool(Setting::getValue('checkout', 'tax_enabled', self::DEFAULTS['tax_enabled'])),
            'tax_percentage' => $this->toPercentage(Setting::getValue('checkout', 'tax_percentage', self::DEFAULTS['tax_percentage'])),
        ];

        $schemaSetting = Setting::query()
            ->where('group', 'checkout')
            ->where('key', 'checkout_fields_schema')
            ->first();

        $storedSchema = $schemaSetting
            ? Setting::getValue('checkout', 'checkout_fields_schema', [])
            : self::DEFAULT_FIELD_SCHEMA;

        if (!is_array($storedSchema)) {
            $storedSchema = [];
        }

        $values['checkout_fields_schema'] = $this->normalizeFieldSchema($storedSchema);

        return $values;
    }

    public function getFieldTypeOptions(): array
    {
        return [
            ['value' => 'text', 'label' => 'Text'],
            ['value' => 'textarea', 'label' => 'Textarea'],
            ['value' => 'email', 'label' => 'Email'],
            ['value' => 'tel', 'label' => 'Phone'],
            ['value' => 'number', 'label' => 'Number'],
            ['value' => 'select', 'label' => 'Dropdown Select'],
            ['value' => 'country', 'label' => 'Country'],
            ['value' => 'location_text', 'label' => 'Location Text'],
            ['value' => 'location_division', 'label' => 'Location Division'],
            ['value' => 'location_district', 'label' => 'Location District'],
            ['value' => 'location_upazila', 'label' => 'Location Upazila'],
            ['value' => 'location_union', 'label' => 'Location Union'],
        ];
    }

    public function getDefaultFieldSchema(): array
    {
        return $this->normalizeFieldSchema(self::DEFAULT_FIELD_SCHEMA);
    }

    public function normalizeFieldSchema(array $schema): array
    {
        $normalized = [
            'billing' => [],
            'shipping' => [],
            'additional' => [],
        ];

        $hasSectionKeys = isset($schema['billing']) || isset($schema['shipping']) || isset($schema['additional']);

        if ($hasSectionKeys) {
            foreach ($normalized as $section => $_) {
                $items = $schema[$section] ?? [];
                if (!is_array($items)) {
                    $items = [];
                }

                $order = 1;
                foreach ($items as $item) {
                    if (!is_array($item)) {
                        continue;
                    }

                    $field = $this->normalizeSingleField($item, $section, $order);
                    if ($field === null) {
                        continue;
                    }

                    $normalized[$section][] = $field;
                    $order++;
                }
            }

            return $normalized;
        }

        // Support legacy flattened format.
        if (array_is_list($schema)) {
            $orderMap = ['billing' => 1, 'shipping' => 1, 'additional' => 1];

            foreach ($schema as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $section = $this->normalizeSection((string) ($item['section'] ?? 'shipping'));
                $field = $this->normalizeSingleField($item, $section, $orderMap[$section]);
                if ($field === null) {
                    continue;
                }

                $normalized[$section][] = $field;
                $orderMap[$section]++;
            }
        }

        return $normalized;
    }

    public function getEnabledFields(): array
    {
        $raw = $this->getRaw();
        $schema = $raw['checkout_fields_schema'] ?? [];
        $enabled = [];

        foreach (['billing', 'shipping', 'additional'] as $section) {
            $fields = $schema[$section] ?? [];
            if (!is_array($fields)) {
                continue;
            }

            foreach ($fields as $field) {
                if (!is_array($field) || empty($field['enabled'])) {
                    continue;
                }

                $enabled[] = $field;
            }
        }

        return $enabled;
    }

    public function getFrontendConfig(): array
    {
        $raw = $this->getRaw();

        $fieldSections = [];
        $flatFields = [];

        foreach (['billing', 'shipping', 'additional'] as $section) {
            $fields = $raw['checkout_fields_schema'][$section] ?? [];

            $enabledFields = array_values(array_filter(
                is_array($fields) ? $fields : [],
                fn ($field) => is_array($field) && !empty($field['enabled'])
            ));

            $fieldSections[] = [
                'section' => $section,
                'label' => self::SECTION_LABELS[$section] ?? ucfirst($section),
                'fields' => $enabledFields,
            ];

            foreach ($enabledFields as $field) {
                $flatFields[] = $field;
            }
        }

        return [
            'checkout_form_enabled' => $raw['checkout_form_enabled'],
            'guest_checkout_enabled' => $raw['enable_guest_checkout'],
            'tax_enabled' => $raw['tax_enabled'],
            'tax_percentage' => $raw['tax_percentage'],
            'field_sections' => $fieldSections,
            'fields' => $flatFields,
        ];
    }

    protected function normalizeSingleField(array $field, string $section, int $fallbackOrder): ?array
    {
        $key = $this->sanitizeFieldKey((string) ($field['key'] ?? ''));
        if ($key === '') {
            return null;
        }

        $type = strtolower(trim((string) ($field['type'] ?? 'text')));
        if (!in_array($type, self::ALLOWED_TYPES, true)) {
            $type = 'text';
        }

        $validations = [];
        if (isset($field['validations']) && is_array($field['validations'])) {
            $validations = array_values(array_filter(array_map(function ($item) {
                return strtolower(trim((string) $item));
            }, $field['validations'])));
        }

        $options = [];
        if ($type === 'select') {
            $rawOptions = $field['options'] ?? [];
            if (!is_array($rawOptions)) {
                $rawOptions = [];
            }

            foreach ($rawOptions as $option) {
                if (!is_array($option)) {
                    continue;
                }

                $label = trim((string) ($option['label'] ?? ''));
                $value = trim((string) ($option['value'] ?? ''));
                if ($label === '' || $value === '') {
                    continue;
                }

                $options[] = [
                    'label' => $label,
                    'value' => $value,
                ];
            }
        }

        return [
            'id' => trim((string) ($field['id'] ?? $key)) !== '' ? trim((string) ($field['id'] ?? $key)) : $key,
            'section' => $section,
            'key' => $key,
            'type' => $type,
            'label' => trim((string) ($field['label'] ?? $key)),
            'placeholder' => trim((string) ($field['placeholder'] ?? '')),
            'required' => $this->toBool($field['required'] ?? false),
            'enabled' => $this->toBool($field['enabled'] ?? true),
            'validations' => $validations,
            'options' => $options,
            'sort_order' => max(1, (int) ($field['sort_order'] ?? $fallbackOrder)),
        ];
    }

    protected function sanitizeFieldKey(string $value): string
    {
        $value = strtolower(trim($value));
        if ($value === '') {
            return '';
        }

        $value = preg_replace('/[^a-z0-9_]+/', '_', $value) ?? '';
        $value = preg_replace('/_+/', '_', $value) ?? '';

        return trim($value, '_');
    }

    protected function normalizeSection(string $section): string
    {
        $normalized = strtolower(trim($section));

        if (!in_array($normalized, ['billing', 'shipping', 'additional'], true)) {
            return 'shipping';
        }

        return $normalized;
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

    protected function toPercentage(mixed $value): float
    {
        $numeric = is_numeric($value) ? (float) $value : 0.0;

        return max(0, min(100, $numeric));
    }
}
