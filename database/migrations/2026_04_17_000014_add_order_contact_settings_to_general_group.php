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
                'group' => 'general',
                'key' => 'call_for_order_phone',
                'value' => '',
                'type' => 'text',
                'label' => 'Call For Order Phone',
                'description' => 'Phone number used for the call for order button on product pages.',
                'is_public' => true,
                'sort_order' => 6,
            ],
            [
                'group' => 'general',
                'key' => 'whatsapp_order_phone',
                'value' => '',
                'type' => 'text',
                'label' => 'WhatsApp Order Number',
                'description' => 'WhatsApp number used for quick order from product pages.',
                'is_public' => true,
                'sort_order' => 7,
            ],
            [
                'group' => 'general',
                'key' => 'whatsapp_order_message',
                'value' => 'Assalamu Alaikum, I want to order: {product_name}. Product URL: {product_url}. Quantity: {quantity}.',
                'type' => 'textarea',
                'label' => 'WhatsApp Order Message',
                'description' => 'Template for WhatsApp order message. Available placeholders: {product_name}, {product_url}, {quantity}, {price}, {sku}.',
                'is_public' => true,
                'sort_order' => 8,
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
            ->where('group', 'general')
            ->whereIn('key', [
                'call_for_order_phone',
                'whatsapp_order_phone',
                'whatsapp_order_message',
            ])
            ->delete();
    }
};
