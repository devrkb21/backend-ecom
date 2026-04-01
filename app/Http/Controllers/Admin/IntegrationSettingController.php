<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\SmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class IntegrationSettingController extends Controller
{
    private const GROUP = 'integration';

    public function __construct(
        protected SmsService $smsService
    ) {}

    public function index()
    {
        $this->ensureDefaultSettings();

        $settings = Setting::where('group', self::GROUP)
            ->orderBy('sort_order')
            ->get()
            ->keyBy('key');

        return view('admin.settings.integrations', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $this->ensureDefaultSettings();

        $validated = $request->validate([
            'gtm_enabled' => ['nullable', 'boolean'],
            'gtm_container_id' => ['nullable', 'string', 'max:100'],
            'facebook_pixel_enabled' => ['nullable', 'boolean'],
            'facebook_pixel_id' => ['nullable', 'string', 'max:100'],
            'tiktok_pixel_enabled' => ['nullable', 'boolean'],
            'tiktok_pixel_id' => ['nullable', 'string', 'max:100'],
            'google_analytics_enabled' => ['nullable', 'boolean'],
            'google_analytics_measurement_id' => ['nullable', 'string', 'max:100'],
            'sms_enabled' => ['nullable', 'boolean'],
            'sms_provider' => ['nullable', 'string', 'max:100'],
            'sms_api_base_url' => ['nullable', 'string', 'max:255'],
            'sms_api_key' => ['nullable', 'string', 'max:255'],
            'sms_sender_id' => ['nullable', 'string', 'max:100'],
            'sms_balance_url' => ['nullable', 'string', 'max:255'],
        ]);

        foreach ($this->definitions() as $index => $definition) {
            $key = $definition['key'];

            if ($definition['type'] === 'boolean') {
                $value = $request->boolean($key) ? '1' : '0';
            } else {
                $value = trim((string) ($validated[$key] ?? ''));
            }

            Setting::setValue(self::GROUP, $key, $value, [
                'type' => $definition['type'],
                'label' => $definition['label'],
                'description' => $definition['description'],
                'is_public' => $definition['is_public'],
                'sort_order' => $index + 1,
            ]);
        }

        Setting::clearCache(self::GROUP);

        return redirect()
            ->route('admin.settings.integrations')
            ->with('success', 'Integration settings updated successfully.');
    }

    public function smsBalance(): JsonResponse
    {
        $this->ensureDefaultSettings();

        $result = $this->smsService->getBalance();

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'balance' => $result['balance'] ?? null,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'],
        ], 422);
    }

    private function ensureDefaultSettings(): void
    {
        foreach ($this->definitions() as $index => $definition) {
            Setting::firstOrCreate(
                [
                    'group' => self::GROUP,
                    'key' => $definition['key'],
                ],
                [
                    'type' => $definition['type'],
                    'label' => $definition['label'],
                    'description' => $definition['description'],
                    'value' => $definition['default'],
                    'is_public' => $definition['is_public'],
                    'sort_order' => $index + 1,
                ]
            );
        }
    }

    private function definitions(): array
    {
        return [
            [
                'key' => 'gtm_enabled',
                'label' => 'Enable Google Tag Manager',
                'type' => 'boolean',
                'default' => '0',
                'description' => 'Toggle GTM script usage.',
                'is_public' => true,
            ],
            [
                'key' => 'gtm_container_id',
                'label' => 'Google Tag Manager Container ID',
                'type' => 'text',
                'default' => '',
                'description' => 'Example: GTM-XXXXXXX',
                'is_public' => true,
            ],
            [
                'key' => 'facebook_pixel_enabled',
                'label' => 'Enable Facebook Pixel',
                'type' => 'boolean',
                'default' => '0',
                'description' => 'Toggle Facebook Pixel script usage.',
                'is_public' => true,
            ],
            [
                'key' => 'facebook_pixel_id',
                'label' => 'Facebook Pixel ID',
                'type' => 'text',
                'default' => '',
                'description' => 'Example: 123456789012345',
                'is_public' => true,
            ],
            [
                'key' => 'tiktok_pixel_enabled',
                'label' => 'Enable TikTok Pixel',
                'type' => 'boolean',
                'default' => '0',
                'description' => 'Toggle TikTok Pixel script usage.',
                'is_public' => true,
            ],
            [
                'key' => 'tiktok_pixel_id',
                'label' => 'TikTok Pixel ID',
                'type' => 'text',
                'default' => '',
                'description' => 'Example: C123ABCDEF12345',
                'is_public' => true,
            ],
            [
                'key' => 'google_analytics_enabled',
                'label' => 'Enable Google Analytics',
                'type' => 'boolean',
                'default' => '0',
                'description' => 'Toggle GA4 tracking script usage.',
                'is_public' => true,
            ],
            [
                'key' => 'google_analytics_measurement_id',
                'label' => 'Google Analytics Measurement ID',
                'type' => 'text',
                'default' => '',
                'description' => 'Example: G-XXXXXXXXXX',
                'is_public' => true,
            ],
            [
                'key' => 'sms_enabled',
                'label' => 'Enable SMS API',
                'type' => 'boolean',
                'default' => '0',
                'description' => 'Enable SMS sending via third-party API.',
                'is_public' => false,
            ],
            [
                'key' => 'sms_provider',
                'label' => 'SMS Provider Name',
                'type' => 'text',
                'default' => 'BulkSMSBD',
                'description' => 'Current provider: BulkSMSBD.',
                'is_public' => false,
            ],
            [
                'key' => 'sms_api_base_url',
                'label' => 'SMS Send API URL',
                'type' => 'text',
                'default' => 'http://www.bulksmsbd.net/api/smsapi',
                'description' => 'BulkSMSBD send endpoint.',
                'is_public' => false,
            ],
            [
                'key' => 'sms_api_key',
                'label' => 'SMS API Key',
                'type' => 'text',
                'default' => '',
                'description' => 'BulkSMSBD api_key value.',
                'is_public' => false,
            ],
            [
                'key' => 'sms_sender_id',
                'label' => 'SMS Sender ID',
                'type' => 'text',
                'default' => '',
                'description' => 'Approved senderid from BulkSMSBD.',
                'is_public' => false,
            ],
            [
                'key' => 'sms_balance_url',
                'label' => 'SMS Balance API URL',
                'type' => 'text',
                'default' => 'http://www.bulksmsbd.net/api/getBalanceApi',
                'description' => 'BulkSMSBD balance endpoint.',
                'is_public' => false,
            ],
        ];
    }
}
