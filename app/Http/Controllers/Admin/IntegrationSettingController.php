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
            'mail_enabled' => ['nullable', 'boolean'],
            'mail_mailer' => ['nullable', 'string', 'in:smtp,sendmail'],
            'mail_host' => ['nullable', 'string', 'max:255'],
            'mail_port' => ['nullable', 'string', 'max:10'],
            'mail_username' => ['nullable', 'string', 'max:255'],
            'mail_password' => ['nullable', 'string', 'max:255'],
            'mail_encryption' => ['nullable', 'string', 'in:tls,ssl,'],
            'mail_from_address' => ['nullable', 'email', 'max:255'],
            'mail_from_name' => ['nullable', 'string', 'max:255'],
            'sms_enabled' => ['nullable', 'boolean'],
            'sms_provider' => ['nullable', 'string', 'max:100'],
            'sms_api_base_url' => ['nullable', 'string', 'max:255'],
            'sms_api_key' => ['nullable', 'string', 'max:255'],
            'sms_sender_id' => ['nullable', 'string', 'max:100'],
            'sms_balance_url' => ['nullable', 'string', 'max:255'],
            'live_chat_enabled' => ['nullable', 'boolean'],
            'live_chat_whatsapp_enabled' => ['nullable', 'boolean'],
            'live_chat_whatsapp_number' => ['nullable', 'string', 'max:20'],
            'live_chat_whatsapp_message' => ['nullable', 'string', 'max:500'],
            'live_chat_messenger_enabled' => ['nullable', 'boolean'],
            'live_chat_messenger_link' => ['nullable', 'string', 'max:500'],
            'live_chat_button_position' => ['nullable', 'string', 'in:bottom-right,bottom-left'],
            'live_chat_welcome_text' => ['nullable', 'string', 'max:200'],
            'live_chat_button_color' => ['nullable', 'string', 'max:20'],
            'site_verification_entries' => ['nullable', 'array'],
            'site_verification_entries.*.provider' => ['nullable', 'string', 'max:50'],
            'site_verification_entries.*.code' => ['nullable', 'string', 'max:255'],
            'site_verification_entries.*.meta_name' => ['nullable', 'string', 'max:120', 'regex:/^[A-Za-z0-9._:-]+$/'],
        ]);

        $currentValues = Setting::where('group', self::GROUP)
            ->pluck('value', 'key')
            ->all();

        $fieldToggleMap = $this->fieldToggleMap();

        foreach ($this->definitions() as $index => $definition) {
            $key = $definition['key'];

            if ($definition['type'] === 'boolean') {
                $value = $request->boolean($key) ? '1' : '0';
            } elseif ($definition['type'] === 'json') {
                $rawValue = $validated[$key] ?? $request->input($key, []);

                if ($key === 'site_verification_entries') {
                    $value = $this->normalizeSiteVerificationEntries(is_array($rawValue) ? $rawValue : []);
                } else {
                    $value = is_array($rawValue) ? $rawValue : [];
                }
            } else {
                $toggleKey = $fieldToggleMap[$key] ?? null;

                if ($toggleKey && ! $request->boolean($toggleKey)) {
                    // Preserve existing value when the integration is disabled.
                    $value = (string) ($currentValues[$key] ?? ($definition['default'] ?? ''));
                } elseif (array_key_exists($key, $validated)) {
                    $value = trim((string) ($validated[$key] ?? ''));
                } else {
                    $value = (string) ($currentValues[$key] ?? ($definition['default'] ?? ''));
                }
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
                'key' => 'mail_enabled',
                'label' => 'Enable Custom Mail',
                'type' => 'boolean',
                'default' => '0',
                'description' => 'Toggle custom mail configurations usage.',
                'is_public' => false,
            ],
            [
                'key' => 'mail_mailer',
                'label' => 'Mailer Configuration',
                'type' => 'text',
                'default' => 'smtp',
                'description' => 'SMTP or PHP Mail (sendmail)',
                'is_public' => false,
            ],
            [
                'key' => 'mail_host',
                'label' => 'Mail Host',
                'type' => 'text',
                'default' => '',
                'description' => 'SMTP Host',
                'is_public' => false,
            ],
            [
                'key' => 'mail_port',
                'label' => 'Mail Port',
                'type' => 'text',
                'default' => '587',
                'description' => 'SMTP Port',
                'is_public' => false,
            ],
            [
                'key' => 'mail_username',
                'label' => 'Mail Username',
                'type' => 'text',
                'default' => '',
                'description' => 'SMTP Username',
                'is_public' => false,
            ],
            [
                'key' => 'mail_password',
                'label' => 'Mail Password',
                'type' => 'text',
                'default' => '',
                'description' => 'SMTP Password',
                'is_public' => false,
            ],
            [
                'key' => 'mail_encryption',
                'label' => 'Mail Encryption',
                'type' => 'text',
                'default' => 'tls',
                'description' => 'tls, ssl or empty',
                'is_public' => false,
            ],
            [
                'key' => 'mail_from_address',
                'label' => 'Mail From Address',
                'type' => 'text',
                'default' => '',
                'description' => 'Default from address for emails',
                'is_public' => false,
            ],
            [
                'key' => 'mail_from_name',
                'label' => 'Mail From Name',
                'type' => 'text',
                'default' => '',
                'description' => 'Default from name for emails',
                'is_public' => false,
            ],
            [
                'key' => 'site_verification_entries',
                'label' => 'Site Verification Entries',
                'type' => 'json',
                'default' => '[]',
                'description' => 'List of domain verification entries rendered for search platforms.',
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
            [
                'key' => 'live_chat_enabled',
                'label' => 'Enable Live Chat Widget',
                'type' => 'boolean',
                'default' => '0',
                'description' => 'Master toggle for the floating live chat widget.',
                'is_public' => true,
            ],
            [
                'key' => 'live_chat_whatsapp_enabled',
                'label' => 'Enable WhatsApp',
                'type' => 'boolean',
                'default' => '0',
                'description' => 'Show WhatsApp option in chat widget.',
                'is_public' => true,
            ],
            [
                'key' => 'live_chat_whatsapp_number',
                'label' => 'WhatsApp Phone Number',
                'type' => 'text',
                'default' => '',
                'description' => 'Phone number with country code (e.g. +8801XXXXXXXXX).',
                'is_public' => true,
            ],
            [
                'key' => 'live_chat_whatsapp_message',
                'label' => 'WhatsApp Pre-filled Message',
                'type' => 'text',
                'default' => 'Hello! I need help.',
                'description' => 'Default message pre-filled when opening WhatsApp chat.',
                'is_public' => true,
            ],
            [
                'key' => 'live_chat_messenger_enabled',
                'label' => 'Enable Messenger',
                'type' => 'boolean',
                'default' => '0',
                'description' => 'Show Messenger option in chat widget.',
                'is_public' => true,
            ],
            [
                'key' => 'live_chat_messenger_link',
                'label' => 'Messenger Page Link',
                'type' => 'text',
                'default' => '',
                'description' => 'Facebook Page Messenger link (e.g. https://m.me/yourpage).',
                'is_public' => true,
            ],
            [
                'key' => 'live_chat_button_position',
                'label' => 'Button Position',
                'type' => 'text',
                'default' => 'bottom-right',
                'description' => 'Floating button position on screen.',
                'is_public' => true,
            ],
            [
                'key' => 'live_chat_welcome_text',
                'label' => 'Welcome Text',
                'type' => 'text',
                'default' => 'Chat with us!',
                'description' => 'Tooltip text shown on hover.',
                'is_public' => true,
            ],
            [
                'key' => 'live_chat_button_color',
                'label' => 'Button Color',
                'type' => 'text',
                'default' => '#7C3AED',
                'description' => 'Main chat button background color.',
                'is_public' => true,
            ],
        ];
    }

    private function fieldToggleMap(): array
    {
        return [
            'gtm_container_id' => 'gtm_enabled',
            'facebook_pixel_id' => 'facebook_pixel_enabled',
            'tiktok_pixel_id' => 'tiktok_pixel_enabled',
            'google_analytics_measurement_id' => 'google_analytics_enabled',
            'mail_mailer' => 'mail_enabled',
            'mail_host' => 'mail_enabled',
            'mail_port' => 'mail_enabled',
            'mail_username' => 'mail_enabled',
            'mail_password' => 'mail_enabled',
            'mail_encryption' => 'mail_enabled',
            'mail_from_address' => 'mail_enabled',
            'mail_from_name' => 'mail_enabled',
            'sms_provider' => 'sms_enabled',
            'sms_api_base_url' => 'sms_enabled',
            'sms_api_key' => 'sms_enabled',
            'sms_sender_id' => 'sms_enabled',
            'sms_balance_url' => 'sms_enabled',
            'live_chat_whatsapp_enabled' => 'live_chat_enabled',
            'live_chat_whatsapp_number' => 'live_chat_enabled',
            'live_chat_whatsapp_message' => 'live_chat_enabled',
            'live_chat_messenger_enabled' => 'live_chat_enabled',
            'live_chat_messenger_link' => 'live_chat_enabled',
            'live_chat_button_position' => 'live_chat_enabled',
            'live_chat_welcome_text' => 'live_chat_enabled',
            'live_chat_button_color' => 'live_chat_enabled',
        ];
    }

    private function verificationProviders(): array
    {
        return ['google', 'bing', 'yandex', 'pinterest', 'facebook', 'custom'];
    }

    private function normalizeSiteVerificationEntries(array $entries): array
    {
        $allowedProviders = $this->verificationProviders();
        $normalized = [];

        foreach ($entries as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $provider = strtolower(trim((string) ($entry['provider'] ?? '')));
            $code = trim((string) ($entry['code'] ?? ''));

            if (! in_array($provider, $allowedProviders, true) || $code === '') {
                continue;
            }

            $item = [
                'provider' => $provider,
                'code' => $code,
            ];

            if ($provider === 'custom') {
                $metaName = strtolower(trim((string) ($entry['meta_name'] ?? '')));
                if ($metaName === '') {
                    continue;
                }

                $item['meta_name'] = $metaName;
            }

            $normalized[] = $item;
        }

        return array_values($normalized);
    }
}
