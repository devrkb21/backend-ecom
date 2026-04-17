<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('settings')->updateOrInsert(
            ['group' => 'general', 'key' => 'open_side_cart_on_add'],
            [
                'type' => 'boolean',
                'label' => 'Open Side Cart After Add To Cart',
                'value' => '1',
                'description' => 'When enabled, the side cart drawer opens immediately after a product is added to cart.',
                'is_public' => true,
                'sort_order' => 9,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('settings')
            ->where('group', 'general')
            ->where('key', 'open_side_cart_on_add')
            ->delete();
    }
};
