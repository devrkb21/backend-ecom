<?php

namespace Database\Seeders;

use App\Models\PaymentGateway;
use Illuminate\Database\Seeder;

class PaymentGatewaySeeder extends Seeder
{
    public function run(): void
    {
        $gateways = [
            [
                'code' => 'cod',
                'name' => 'Cash on Delivery',
                'description' => 'Pay with cash when your order is delivered',
                'is_active' => true,
                'sort_order' => 1,
                'icon' => 'bi-cash-coin',
                'instructions' => 'Please keep the exact amount ready at the time of delivery. Our delivery person will collect the payment.',
                'settings' => [
                    'extra_charge' => 0, // Additional charge for COD
                    'extra_charge_type' => 'fixed', // fixed or percentage
                ],
                'supported_currencies' => ['USD', 'BDT'],
                'min_amount' => null,
                'max_amount' => 50000, // Max COD amount
            ],
            [
                'code' => 'stripe',
                'name' => 'Credit/Debit Card',
                'description' => 'Pay securely with your credit or debit card',
                'is_active' => false, // Disabled by default until configured
                'sort_order' => 2,
                'icon' => 'bi-credit-card',
                'instructions' => 'You will be redirected to a secure payment page to complete your purchase.',
                'settings' => [
                    'public_key' => '',
                    'secret_key' => '',
                    'webhook_secret' => '',
                    'mode' => 'test', // test or live
                ],
                'supported_currencies' => ['USD', 'EUR', 'GBP', 'BDT'],
                'min_amount' => 1,
                'max_amount' => null,
            ],
            [
                'code' => 'bkash',
                'name' => 'bKash',
                'description' => 'Pay with your bKash mobile wallet',
                'is_active' => false, // Disabled by default until configured
                'sort_order' => 3,
                'icon' => 'bi-phone',
                'instructions' => 'You will receive a payment request on your bKash app. Please complete the payment within 5 minutes.',
                'settings' => [
                    'app_key' => '',
                    'app_secret' => '',
                    'username' => '',
                    'password' => '',
                    'mode' => 'sandbox', // sandbox or live
                    'base_url' => 'https://tokenized.sandbox.bka.sh/v1.2.0-beta',
                ],
                'supported_currencies' => ['BDT'],
                'min_amount' => 10,
                'max_amount' => 25000, // bKash transaction limit
            ],
        ];

        foreach ($gateways as $gateway) {
            PaymentGateway::updateOrCreate(
                ['code' => $gateway['code']],
                $gateway
            );
        }
    }
}
