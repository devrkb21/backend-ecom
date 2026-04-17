<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->updateOrInsert(
            ['group' => 'checkout', 'key' => 'enable_guest_checkout'],
            [
                'type' => 'boolean',
                'label' => 'Enable Guest Checkout',
                'value' => '1',
                'description' => 'When disabled, customers must login before adding items to cart or placing orders.',
                'is_public' => true,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('settings')
            ->where('group', 'checkout')
            ->where('key', 'enable_guest_checkout')
            ->delete();
    }
};