<?php

namespace Database\Seeders;

use App\Models\LoyaltyTier;
use Illuminate\Database\Seeder;

class LoyaltyTierSeeder extends Seeder
{
    public function run(): void
    {
        $tiers = [
            [
                'name' => 'Bronze',
                'slug' => 'bronze',
                'min_points' => 0,
                'points_multiplier' => 1.00,
                'birthday_bonus' => 50,
                'free_shipping' => false,
                'exclusive_discount' => 0,
                'benefits' => 'Earn 1 point per ৳1 spent, Birthday bonus points',
            ],
            [
                'name' => 'Silver',
                'slug' => 'silver',
                'min_points' => 500,
                'points_multiplier' => 1.25,
                'birthday_bonus' => 100,
                'free_shipping' => false,
                'exclusive_discount' => 5,
                'benefits' => 'Earn 1.25x points, 5% member discount, Birthday bonus',
            ],
            [
                'name' => 'Gold',
                'slug' => 'gold',
                'min_points' => 2000,
                'points_multiplier' => 1.50,
                'birthday_bonus' => 200,
                'free_shipping' => true,
                'exclusive_discount' => 10,
                'benefits' => 'Earn 1.5x points, 10% member discount, Free shipping, Birthday bonus',
            ],
            [
                'name' => 'Platinum',
                'slug' => 'platinum',
                'min_points' => 5000,
                'points_multiplier' => 2.00,
                'birthday_bonus' => 500,
                'free_shipping' => true,
                'exclusive_discount' => 15,
                'benefits' => 'Earn 2x points, 15% member discount, Free shipping, Priority support, Birthday bonus',
            ],
        ];

        foreach ($tiers as $tier) {
            LoyaltyTier::updateOrCreate(
                ['slug' => $tier['slug']],
                $tier
            );
        }
    }
}
