<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\Setting;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $defaultSchema = [
            'billing' => [
                [
                    'id' => 'billing_first_name',
                    'section' => 'billing',
                    'key' => 'billing_first_name',
                    'type' => 'text',
                    'label' => 'First name',
                    'placeholder' => 'First name',
                    'required' => true,
                    'enabled' => true,
                    'validations' => [],
                    'options' => [],
                    'sort_order' => 1,
                ],
                [
                    'id' => 'billing_last_name',
                    'section' => 'billing',
                    'key' => 'billing_last_name',
                    'type' => 'text',
                    'label' => 'Last name',
                    'placeholder' => 'Last name',
                    'required' => true,
                    'enabled' => true,
                    'validations' => [],
                    'options' => [],
                    'sort_order' => 2,
                ],
                [
                    'id' => 'billing_email',
                    'section' => 'billing',
                    'key' => 'billing_email',
                    'type' => 'email',
                    'label' => 'Email address',
                    'placeholder' => 'Email address',
                    'required' => true,
                    'enabled' => true,
                    'validations' => ['email'],
                    'options' => [],
                    'sort_order' => 3,
                ],
                [
                    'id' => 'billing_phone',
                    'section' => 'billing',
                    'key' => 'billing_phone',
                    'type' => 'tel',
                    'label' => 'Phone',
                    'placeholder' => 'Phone',
                    'required' => false,
                    'enabled' => true,
                    'validations' => ['phone'],
                    'options' => [],
                    'sort_order' => 4,
                ],
            ],
            'shipping' => [
                [
                    'id' => 'shipping_name',
                    'section' => 'shipping',
                    'key' => 'shipping_name',
                    'type' => 'text',
                    'label' => 'Full Name',
                    'placeholder' => 'Full Name',
                    'required' => true,
                    'enabled' => true,
                    'validations' => [],
                    'options' => [],
                    'sort_order' => 1,
                ],
                [
                    'id' => 'shipping_address',
                    'section' => 'shipping',
                    'key' => 'shipping_address',
                    'type' => 'text',
                    'label' => 'Address',
                    'placeholder' => 'Address',
                    'required' => true,
                    'enabled' => true,
                    'validations' => [],
                    'options' => [],
                    'sort_order' => 2,
                ],
                [
                    'id' => 'shipping_location_text',
                    'section' => 'shipping',
                    'key' => 'shipping_location_text',
                    'type' => 'location_text',
                    'label' => 'Location Text',
                    'placeholder' => 'Area, upazila, district',
                    'required' => false,
                    'enabled' => true,
                    'validations' => [],
                    'options' => [],
                    'sort_order' => 3,
                ],
                [
                    'id' => 'shipping_division_id',
                    'section' => 'shipping',
                    'key' => 'shipping_division_id',
                    'type' => 'location_division',
                    'label' => 'Division',
                    'placeholder' => 'Select Division',
                    'required' => false,
                    'enabled' => true,
                    'validations' => [],
                    'options' => [],
                    'sort_order' => 4,
                ],
                [
                    'id' => 'shipping_district_id',
                    'section' => 'shipping',
                    'key' => 'shipping_district_id',
                    'type' => 'location_district',
                    'label' => 'District',
                    'placeholder' => 'Select District',
                    'required' => false,
                    'enabled' => true,
                    'validations' => [],
                    'options' => [],
                    'sort_order' => 5,
                ],
                [
                    'id' => 'shipping_upazila_id',
                    'section' => 'shipping',
                    'key' => 'shipping_upazila_id',
                    'type' => 'location_upazila',
                    'label' => 'Upazila',
                    'placeholder' => 'Select Upazila',
                    'required' => false,
                    'enabled' => true,
                    'validations' => [],
                    'options' => [],
                    'sort_order' => 6,
                ],
                [
                    'id' => 'shipping_union_id',
                    'section' => 'shipping',
                    'key' => 'shipping_union_id',
                    'type' => 'location_union',
                    'label' => 'Union',
                    'placeholder' => 'Select Union',
                    'required' => false,
                    'enabled' => true,
                    'validations' => [],
                    'options' => [],
                    'sort_order' => 7,
                ],
                [
                    'id' => 'shipping_city',
                    'section' => 'shipping',
                    'key' => 'shipping_city',
                    'type' => 'text',
                    'label' => 'City',
                    'placeholder' => 'City',
                    'required' => false,
                    'enabled' => true,
                    'validations' => [],
                    'options' => [],
                    'sort_order' => 8,
                ],
                [
                    'id' => 'shipping_zip',
                    'section' => 'shipping',
                    'key' => 'shipping_zip',
                    'type' => 'text',
                    'label' => 'ZIP / Postcode',
                    'placeholder' => 'ZIP / Postcode',
                    'required' => false,
                    'enabled' => true,
                    'validations' => [],
                    'options' => [],
                    'sort_order' => 9,
                ],
                [
                    'id' => 'shipping_country',
                    'section' => 'shipping',
                    'key' => 'shipping_country',
                    'type' => 'country',
                    'label' => 'Country',
                    'placeholder' => 'Country',
                    'required' => true,
                    'enabled' => true,
                    'validations' => [],
                    'options' => [],
                    'sort_order' => 10,
                ],
            ],
            'additional' => [
                [
                    'id' => 'order_notes',
                    'section' => 'additional',
                    'key' => 'order_notes',
                    'type' => 'textarea',
                    'label' => 'Order Notes',
                    'placeholder' => 'Special instructions for delivery...',
                    'required' => false,
                    'enabled' => true,
                    'validations' => [],
                    'options' => [],
                    'sort_order' => 1,
                ],
            ],
        ];

        DB::table('settings')->updateOrInsert(
            ['group' => 'checkout', 'key' => 'checkout_fields_schema'],
            [
                'type' => 'json',
                'label' => 'Checkout Fields Schema',
                'value' => json_encode($defaultSchema),
                'description' => 'Schema for customizable checkout fields.',
                'is_public' => true,
                'sort_order' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        Setting::clearCache('checkout', 'checkout_fields_schema');
    }

    public function down(): void
    {
        DB::table('settings')
            ->where('group', 'checkout')
            ->where('key', 'checkout_fields_schema')
            ->delete();

        Setting::clearCache('checkout', 'checkout_fields_schema');
    }
};
