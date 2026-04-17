<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $settings = [
            [
                'group' => 'checkout',
                'key' => 'checkout_form_enabled',
                'value' => '1',
                'type' => 'boolean',
                'label' => 'Enable Checkout Form',
                'description' => 'Disable to block checkout submissions from frontend.',
                'is_public' => true,
                'sort_order' => 1,
            ],
            [
                'group' => 'checkout',
                'key' => 'enable_dropdown_location',
                'value' => '1',
                'type' => 'boolean',
                'label' => 'Enable Division/District/Upazila Fields',
                'description' => 'Show dropdown based location fields in checkout.',
                'is_public' => true,
                'sort_order' => 2,
            ],
            [
                'group' => 'checkout',
                'key' => 'enable_text_location',
                'value' => '1',
                'type' => 'boolean',
                'label' => 'Enable Text Location Input',
                'description' => 'Allow customers to type location text like area, upazila, district.',
                'is_public' => true,
                'sort_order' => 3,
            ],
            [
                'group' => 'checkout',
                'key' => 'require_dropdown_location',
                'value' => '0',
                'type' => 'boolean',
                'label' => 'Require Dropdown Location',
                'description' => 'If enabled, division, district and upazila must be selected.',
                'is_public' => true,
                'sort_order' => 4,
            ],
            [
                'group' => 'checkout',
                'key' => 'require_text_location',
                'value' => '0',
                'type' => 'boolean',
                'label' => 'Require Text Location Input',
                'description' => 'If enabled, shipping location text becomes mandatory.',
                'is_public' => true,
                'sort_order' => 5,
            ],
            [
                'group' => 'checkout',
                'key' => 'show_shipping_email',
                'value' => '1',
                'type' => 'boolean',
                'label' => 'Show Shipping Email Field',
                'description' => 'Show email input in checkout address section.',
                'is_public' => true,
                'sort_order' => 6,
            ],
            [
                'group' => 'checkout',
                'key' => 'require_shipping_email',
                'value' => '1',
                'type' => 'boolean',
                'label' => 'Require Shipping Email',
                'description' => 'If enabled, shipping email must be provided.',
                'is_public' => true,
                'sort_order' => 7,
            ],
            [
                'group' => 'checkout',
                'key' => 'show_shipping_phone',
                'value' => '1',
                'type' => 'boolean',
                'label' => 'Show Shipping Phone Field',
                'description' => 'Show phone input in checkout address section.',
                'is_public' => true,
                'sort_order' => 8,
            ],
            [
                'group' => 'checkout',
                'key' => 'require_shipping_phone',
                'value' => '0',
                'type' => 'boolean',
                'label' => 'Require Shipping Phone',
                'description' => 'If enabled, shipping phone must be provided.',
                'is_public' => true,
                'sort_order' => 9,
            ],
            [
                'group' => 'checkout',
                'key' => 'show_shipping_zip',
                'value' => '1',
                'type' => 'boolean',
                'label' => 'Show ZIP/Postal Field',
                'description' => 'Show ZIP or postal code field in checkout.',
                'is_public' => true,
                'sort_order' => 10,
            ],
            [
                'group' => 'checkout',
                'key' => 'require_shipping_zip',
                'value' => '1',
                'type' => 'boolean',
                'label' => 'Require ZIP/Postal Field',
                'description' => 'If enabled, ZIP or postal code must be provided.',
                'is_public' => true,
                'sort_order' => 11,
            ],
            [
                'group' => 'checkout',
                'key' => 'show_shipping_area',
                'value' => '1',
                'type' => 'boolean',
                'label' => 'Show Area Field',
                'description' => 'Show area or neighborhood field in checkout.',
                'is_public' => true,
                'sort_order' => 12,
            ],
            [
                'group' => 'checkout',
                'key' => 'require_shipping_area',
                'value' => '0',
                'type' => 'boolean',
                'label' => 'Require Area Field',
                'description' => 'If enabled, area must be provided.',
                'is_public' => true,
                'sort_order' => 13,
            ],
            [
                'group' => 'checkout',
                'key' => 'show_order_notes',
                'value' => '1',
                'type' => 'boolean',
                'label' => 'Show Order Notes Field',
                'description' => 'Show notes textarea in checkout.',
                'is_public' => true,
                'sort_order' => 14,
            ],
            [
                'group' => 'checkout',
                'key' => 'require_order_notes',
                'value' => '0',
                'type' => 'boolean',
                'label' => 'Require Order Notes Field',
                'description' => 'If enabled, order notes become required.',
                'is_public' => true,
                'sort_order' => 15,
            ],
        ];

        foreach ($settings as $setting) {
            DB::table('settings')->updateOrInsert(
                [
                    'group' => $setting['group'],
                    'key' => $setting['key'],
                ],
                array_merge($setting, [
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );
        }
    }

    public function down(): void
    {
        DB::table('settings')
            ->where('group', 'checkout')
            ->whereIn('key', [
                'checkout_form_enabled',
                'enable_dropdown_location',
                'enable_text_location',
                'require_dropdown_location',
                'require_text_location',
                'show_shipping_email',
                'require_shipping_email',
                'show_shipping_phone',
                'require_shipping_phone',
                'show_shipping_zip',
                'require_shipping_zip',
                'show_shipping_area',
                'require_shipping_area',
                'show_order_notes',
                'require_order_notes',
            ])
            ->delete();
    }
};
