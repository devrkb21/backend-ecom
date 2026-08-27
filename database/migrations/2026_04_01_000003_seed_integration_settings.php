<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $defaults = [
            ['group' => 'integration', 'key' => 'gtm_enabled', 'type' => 'boolean', 'label' => 'Enable Google Tag Manager', 'description' => 'Toggle GTM script usage.', 'value' => '0', 'is_public' => true, 'sort_order' => 1],
            ['group' => 'integration', 'key' => 'gtm_container_id', 'type' => 'text', 'label' => 'Google Tag Manager Container ID', 'description' => 'Example: GTM-XXXXXXX', 'value' => '', 'is_public' => true, 'sort_order' => 2],
            ['group' => 'integration', 'key' => 'facebook_pixel_enabled', 'type' => 'boolean', 'label' => 'Enable Facebook Pixel', 'description' => 'Toggle Facebook Pixel script usage.', 'value' => '0', 'is_public' => true, 'sort_order' => 3],
            ['group' => 'integration', 'key' => 'facebook_pixel_id', 'type' => 'text', 'label' => 'Facebook Pixel ID', 'description' => 'Example: 123456789012345', 'value' => '', 'is_public' => true, 'sort_order' => 4],
            ['group' => 'integration', 'key' => 'tiktok_pixel_enabled', 'type' => 'boolean', 'label' => 'Enable TikTok Pixel', 'description' => 'Toggle TikTok Pixel script usage.', 'value' => '0', 'is_public' => true, 'sort_order' => 5],
            ['group' => 'integration', 'key' => 'tiktok_pixel_id', 'type' => 'text', 'label' => 'TikTok Pixel ID', 'description' => 'Example: C123ABCDEF12345', 'value' => '', 'is_public' => true, 'sort_order' => 6],
            ['group' => 'integration', 'key' => 'google_analytics_enabled', 'type' => 'boolean', 'label' => 'Enable Google Analytics', 'description' => 'Toggle GA4 tracking script usage.', 'value' => '0', 'is_public' => true, 'sort_order' => 7],
            ['group' => 'integration', 'key' => 'google_analytics_measurement_id', 'type' => 'text', 'label' => 'Google Analytics Measurement ID', 'description' => 'Example: G-XXXXXXXXXX', 'value' => '', 'is_public' => true, 'sort_order' => 8],
            ['group' => 'integration', 'key' => 'sms_enabled', 'type' => 'boolean', 'label' => 'Enable SMS API', 'description' => 'Enable SMS sending via third-party API.', 'value' => '0', 'is_public' => false, 'sort_order' => 9],
            ['group' => 'integration', 'key' => 'sms_provider', 'type' => 'text', 'label' => 'SMS Provider Name', 'description' => 'Current provider: BulkSMSBD.', 'value' => 'BulkSMSBD', 'is_public' => false, 'sort_order' => 10],
            ['group' => 'integration', 'key' => 'sms_api_base_url', 'type' => 'text', 'label' => 'SMS Send API URL', 'description' => 'BulkSMSBD send endpoint.', 'value' => 'http://www.bulksmsbd.net/api/smsapi', 'is_public' => false, 'sort_order' => 11],
            ['group' => 'integration', 'key' => 'sms_api_key', 'type' => 'text', 'label' => 'SMS API Key', 'description' => 'BulkSMSBD api_key value.', 'value' => '', 'is_public' => false, 'sort_order' => 12],
            ['group' => 'integration', 'key' => 'sms_sender_id', 'type' => 'text', 'label' => 'SMS Sender ID', 'description' => 'Approved senderid from BulkSMSBD.', 'value' => '', 'is_public' => false, 'sort_order' => 13],
            ['group' => 'integration', 'key' => 'sms_balance_url', 'type' => 'text', 'label' => 'SMS Balance API URL', 'description' => 'BulkSMSBD balance endpoint.', 'value' => 'http://www.bulksmsbd.net/api/getBalanceApi', 'is_public' => false, 'sort_order' => 14],
        ];

        foreach ($defaults as $item) {
            $exists = DB::table('settings')
                ->where('group', $item['group'])
                ->where('key', $item['key'])
                ->exists();

            if (! $exists) {
                DB::table('settings')->insert(array_merge($item, [
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
            }
        }
    }

    public function down(): void
    {
        DB::table('settings')
            ->where('group', 'integration')
            ->whereIn('key', [
                'gtm_enabled',
                'gtm_container_id',
                'facebook_pixel_enabled',
                'facebook_pixel_id',
                'tiktok_pixel_enabled',
                'tiktok_pixel_id',
                'google_analytics_enabled',
                'google_analytics_measurement_id',
                'sms_enabled',
                'sms_provider',
                'sms_api_base_url',
                'sms_api_key',
                'sms_sender_id',
                'sms_balance_url',
            ])
            ->delete();
    }
};
