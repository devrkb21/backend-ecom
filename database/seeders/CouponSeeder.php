<?php

namespace Database\Seeders;

use App\Models\Coupon;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class CouponSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $coupons = [
            [
                'code' => 'WELCOME10',
                'name' => 'Welcome Discount',
                'description' => '10% off for new customers',
                'type' => 'percentage',
                'value' => 10,
                'minimum_order_amount' => 500,
                'maximum_discount' => 200,
                'usage_limit' => 1000,
                'usage_limit_per_user' => 1,
                'is_active' => true,
                'expires_at' => Carbon::now()->addMonths(3),
            ],
            [
                'code' => 'SAVE50',
                'name' => '৳50 Off',
                'description' => 'Flat ৳50 discount on orders',
                'type' => 'fixed',
                'value' => 50,
                'minimum_order_amount' => 300,
                'usage_limit' => 500,
                'is_active' => true,
                'expires_at' => Carbon::now()->addMonths(1),
            ],
            [
                'code' => 'FREESHIP',
                'name' => 'Free Shipping',
                'description' => 'Free shipping on all orders',
                'type' => 'fixed',
                'value' => 0,
                'free_shipping' => true,
                'minimum_order_amount' => 1000,
                'is_active' => true,
            ],
            [
                'code' => 'MEGA20',
                'name' => 'Mega Sale 20% Off',
                'description' => '20% off during mega sale',
                'type' => 'percentage',
                'value' => 20,
                'minimum_order_amount' => 1000,
                'maximum_discount' => 500,
                'usage_limit' => 200,
                'is_active' => true,
                'starts_at' => Carbon::now(),
                'expires_at' => Carbon::now()->addWeeks(2),
            ],
            [
                'code' => 'FLASH100',
                'name' => 'Flash Sale ৳100 Off',
                'description' => '৳100 off - limited time',
                'type' => 'fixed',
                'value' => 100,
                'minimum_order_amount' => 500,
                'usage_limit' => 50,
                'is_active' => true,
                'expires_at' => Carbon::now()->addDays(3),
            ],
            [
                'code' => 'VIP25',
                'name' => 'VIP 25% Discount',
                'description' => 'Exclusive 25% discount for VIP customers',
                'type' => 'percentage',
                'value' => 25,
                'maximum_discount' => 1000,
                'usage_limit_per_user' => 3,
                'is_active' => true,
            ],
            [
                'code' => 'EXPIRED2024',
                'name' => 'Expired Coupon',
                'description' => 'This coupon has expired',
                'type' => 'percentage',
                'value' => 15,
                'is_active' => true,
                'expires_at' => Carbon::now()->subDays(5),
            ],
            [
                'code' => 'INACTIVE',
                'name' => 'Inactive Coupon',
                'description' => 'This coupon is disabled',
                'type' => 'fixed',
                'value' => 200,
                'is_active' => false,
            ],
        ];

        foreach ($coupons as $couponData) {
            Coupon::create($couponData);
        }
    }
}
