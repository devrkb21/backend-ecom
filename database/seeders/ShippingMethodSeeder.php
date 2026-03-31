<?php

namespace Database\Seeders;

use App\Models\ShippingMethod;
use Illuminate\Database\Seeder;

class ShippingMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            [
                'code' => 'standard',
                'name' => 'Standard Shipping',
                'description' => 'Regular delivery via postal service',
                'is_active' => true,
                'sort_order' => 1,
                'base_cost' => 60,
                'cost_per_item' => 0,
                'cost_per_kg' => 0,
                'free_shipping_threshold' => 2000,
                'min_order_amount' => null,
                'max_order_amount' => null,
                'max_weight' => 30,
                'min_delivery_days' => 5,
                'max_delivery_days' => 7,
                'allowed_countries' => null,
                'excluded_countries' => null,
                'settings' => [
                    'tracking_available' => true,
                    'insurance_available' => false,
                ],
            ],
            [
                'code' => 'express',
                'name' => 'Express Shipping',
                'description' => 'Fast delivery within 2-3 business days',
                'is_active' => true,
                'sort_order' => 2,
                'base_cost' => 120,
                'cost_per_item' => 10,
                'cost_per_kg' => 5,
                'free_shipping_threshold' => 5000,
                'min_order_amount' => null,
                'max_order_amount' => null,
                'max_weight' => 25,
                'min_delivery_days' => 2,
                'max_delivery_days' => 3,
                'allowed_countries' => null,
                'excluded_countries' => null,
                'settings' => [
                    'tracking_available' => true,
                    'insurance_available' => true,
                ],
            ],
            [
                'code' => 'overnight',
                'name' => 'Overnight Shipping',
                'description' => 'Next business day delivery',
                'is_active' => true,
                'sort_order' => 3,
                'base_cost' => 250,
                'cost_per_item' => 20,
                'cost_per_kg' => 10,
                'free_shipping_threshold' => null,
                'min_order_amount' => 500,
                'max_order_amount' => 50000,
                'max_weight' => 15,
                'min_delivery_days' => 1,
                'max_delivery_days' => 1,
                'allowed_countries' => ['BD'],
                'excluded_countries' => null,
                'settings' => [
                    'tracking_available' => true,
                    'insurance_available' => true,
                    'signature_required' => true,
                ],
            ],
            [
                'code' => 'pickup',
                'name' => 'Store Pickup',
                'description' => 'Pick up from our store - Free!',
                'is_active' => true,
                'sort_order' => 4,
                'base_cost' => 0,
                'cost_per_item' => 0,
                'cost_per_kg' => 0,
                'free_shipping_threshold' => null,
                'min_order_amount' => null,
                'max_order_amount' => null,
                'max_weight' => null,
                'min_delivery_days' => 1,
                'max_delivery_days' => 2,
                'allowed_countries' => null,
                'excluded_countries' => null,
                'settings' => [
                    'pickup_locations' => [
                        [
                            'name' => 'Main Store',
                            'address' => 'Gulshan-2, Dhaka-1212',
                            'hours' => 'Sat-Thu 10am-8pm, Fri 3pm-8pm',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'international',
                'name' => 'International Shipping',
                'description' => 'Worldwide delivery',
                'is_active' => true,
                'sort_order' => 5,
                'base_cost' => 2500,
                'cost_per_item' => 100,
                'cost_per_kg' => 150,
                'free_shipping_threshold' => 15000,
                'min_order_amount' => 3000,
                'max_order_amount' => null,
                'max_weight' => 20,
                'min_delivery_days' => 7,
                'max_delivery_days' => 21,
                'allowed_countries' => null,
                'excluded_countries' => ['CU', 'KP', 'IR', 'SY'], // Sanctioned countries
                'settings' => [
                    'tracking_available' => true,
                    'insurance_available' => true,
                    'customs_declaration_required' => true,
                ],
            ],
        ];

        foreach ($methods as $method) {
            ShippingMethod::updateOrCreate(
                ['code' => $method['code']],
                $method
            );
        }
    }
}
