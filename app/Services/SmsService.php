<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;

class SmsService
{
    public const BULKSMSBD_SEND_URL = 'https://www.bulksmsbd.net/api/smsapi';

    public const BULKSMSBD_BALANCE_URL = 'https://www.bulksmsbd.net/api/getBalanceApi';

    public const REVESMS_SEND_URL = 'https://smpp.revesms.com:7790/sendtext';

    public const REVESMS_BALANCE_URL = 'https://smpp.revesms.com/sms/smsConfiguration/smsClientBalance.jsp';

    public function isEnabled(): bool
    {
        return (bool) Setting::getValue('integration', 'sms_enabled', false);
    }

    /**
     * Send SMS to one or multiple phone numbers.
     *
     * @param  string|array<int, string>  $numbers
     */
    public function send(string|array $numbers, string $message): array
    {
        if (! $this->isEnabled()) {
            return [
                'success' => false,
                'code' => null,
                'message' => 'SMS integration is disabled.',
                'raw' => null,
            ];
        }

        $provider = strtolower(trim((string) Setting::getValue('integration', 'sms_provider', 'bulksmsbd')));
        $numberList = $this->normalizeNumbers($numbers);

        if ($numberList === '') {
            return [
                'success' => false,
                'code' => null,
                'message' => 'No valid phone numbers found.',
                'raw' => null,
            ];
        }

        if ($this->isCustomProvider($provider)) {
            return $this->sendCustomSms($numberList, $message);
        }

        if ($this->isReveProvider($provider)) {
            return $this->sendReveSms($numberList, $message);
        }

        return $this->sendBulkSmsBd($numberList, $message);
    }

    public function sendOtp(string $phone, string $otp, ?string $brandName = null): array
    {
        $brand = trim((string) ($brandName ?: Setting::getValue('general', 'site_name', config('app.name', 'Company'))));
        if ($brand === '') {
            $brand = 'Company';
        }

        $message = "Your {$brand} OTP is {$otp}";

        return $this->send($phone, $message);
    }

    /**
     * Send order status change SMS notification to the customer.
     */
    public function sendOrderStatusSms(Order $order, string $newStatusKey): array
    {
        if (! $this->isEnabled()) {
            return ['success' => false, 'message' => 'SMS integration is disabled.', 'raw' => null];
        }

        // Check if SMS is enabled for this status
        $smsEnabled = Setting::getValue('sms_templates', "sms_enabled_{$newStatusKey}", false);
        if (! $smsEnabled) {
            return ['success' => false, 'message' => "SMS not enabled for status: {$newStatusKey}", 'raw' => null];
        }

        // Get the template
        $template = trim((string) Setting::getValue('sms_templates', "sms_template_{$newStatusKey}", ''));
        if ($template === '') {
            return ['success' => false, 'message' => "No SMS template configured for status: {$newStatusKey}", 'raw' => null];
        }

        // Get customer phone
        $phone = $order->shipping_phone ?? '';
        if ($phone === '') {
            return ['success' => false, 'message' => 'No customer phone number found.', 'raw' => null];
        }

        // Get status label
        $statusConfig = OrderStatus::where('key', $newStatusKey)->first();
        $statusLabel = $statusConfig?->label ?? ucfirst(str_replace('_', ' ', $newStatusKey));

        // Get site name
        $siteName = Setting::getValue('general', 'site_name', config('app.name', 'Store'));

        // Replace placeholders
        $message = str_replace(
            ['{order_number}', '{customer_name}', '{status}', '{total}', '{site_name}', '{phone}'],
            [
                $order->order_number ?? '',
                $order->shipping_name ?? $order->user?->name ?? 'Customer',
                $statusLabel,
                number_format((float) ($order->total ?? 0), 2),
                $siteName,
                $phone,
            ],
            $template
        );

        return $this->send($phone, $message);
    }

    /**
     * Get all SMS templates for order statuses.
     */
    public static function getOrderSmsTemplates(): array
    {
        $statuses = OrderStatus::where('is_active', true)->orderBy('sort_order')->get();
        $templates = [];

        foreach ($statuses as $status) {
            $templates[$status->key] = [
                'label' => $status->label,
                'color' => $status->color,
                'enabled' => (bool) Setting::getValue('sms_templates', "sms_enabled_{$status->key}", false),
                'template' => (string) Setting::getValue('sms_templates', "sms_template_{$status->key}", ''),
            ];
        }

        return $templates;
    }

    public function getBalance(): array
    {
        if (! $this->isEnabled()) {
            return [
                'success' => false,
                'message' => 'SMS integration is disabled.',
                'balance' => null,
                'raw' => null,
            ];
        }

        $provider = strtolower(trim((string) Setting::getValue('integration', 'sms_provider', 'bulksmsbd')));

        if ($this->isCustomProvider($provider)) {
            return $this->getCustomBalance();
        }

        if ($this->isReveProvider($provider)) {
            return $this->getReveBalance();
        }

        return $this->getBulkBalance();
    }

    /**
     * @param  string|array<int, string>  $numbers
     */
    private function normalizeNumbers(string|array $numbers): string
    {
        $list = is_array($numbers) ? $numbers : explode(',', $numbers);

        $normalized = collect($list)
            ->map(function (string $number) {
                $value = preg_replace('/\s+/', '', trim($number));

                if (str_starts_with($value, '+')) {
                    $value = substr($value, 1);
                }

                if (str_starts_with($value, '01') && strlen($value) === 11) {
                    $value = '88'.$value;
                }

                return $value;
            })
            ->filter(fn (?string $number) => is_string($number) && preg_match('/^88\d{11}$/', $number))
            ->unique()
            ->values();

        return $normalized->implode(',');
    }

    /**
     * Resolve the send URL for a known provider.
     *
     * The stored URL wins unless it clearly belongs to another provider — legacy
     * rows may still carry the other provider's default, which would send REVE
     * credentials to BulkSMSBD (or vice versa).
     */
    private function resolveProviderSendUrl(string $provider): string
    {
        $stored = trim((string) Setting::getValue('integration', 'sms_api_base_url', ''));

        if ($provider === 'revesms') {
            if ($stored !== '' && ! str_contains(strtolower($stored), 'bulksmsbd')) {
                return $stored;
            }

            return self::REVESMS_SEND_URL;
        }

        if ($stored !== '' && ! str_contains(strtolower($stored), 'revesms')) {
            return $stored;
        }

        return self::BULKSMSBD_SEND_URL;
    }

    /**
     * Resolve the balance URL for a known provider (same cross-provider guard).
     */
    private function resolveProviderBalanceUrl(string $provider): string
    {
        $stored = trim((string) Setting::getValue('integration', 'sms_balance_url', ''));

        if ($provider === 'revesms') {
            if ($stored !== '' && ! str_contains(strtolower($stored), 'bulksmsbd')) {
                return $stored;
            }

            return self::REVESMS_BALANCE_URL;
        }

        if ($stored !== '' && ! str_contains(strtolower($stored), 'revesms')) {
            return $stored;
        }

        return self::BULKSMSBD_BALANCE_URL;
    }

    private function sendBulkSmsBd(string $numbers, string $message): array
    {
        $apiKey = trim((string) Setting::getValue('integration', 'sms_api_key', ''));
        $senderId = trim((string) Setting::getValue('integration', 'sms_sender_id', ''));
        $sendUrl = $this->resolveProviderSendUrl('bulksmsbd');

        if ($apiKey === '' || $senderId === '' || $sendUrl === '') {
            return [
                'success' => false,
                'code' => null,
                'message' => 'SMS integration is not fully configured.',
                'raw' => null,
            ];
        }

        $response = Http::asForm()
            ->timeout(15)
            ->post($sendUrl, [
                'api_key' => $apiKey,
                'type' => 'text',
                'number' => $numbers,
                'senderid' => $senderId,
                'message' => $message,
            ]);

        $raw = trim((string) $response->body());
        $decoded = json_decode($raw, true);
        $code = null;
        $success = false;

        if (is_array($decoded)) {
            $code = isset($decoded['response_code']) ? (int) $decoded['response_code'] : null;
            $success = ($code === 202);
        } elseif (ctype_digit($raw)) {
            $code = (int) $raw;
            $success = ($code === 202);
        }

        return [
            'success' => $success,
            'code' => $code,
            'message' => $this->resolveCodeMessage($code, $raw),
            'raw' => $raw,
        ];
    }

    private function sendReveSms(string $numbers, string $message): array
    {
        $apiKey = trim((string) Setting::getValue('integration', 'revesms_api_key', ''));
        $secretKey = trim((string) Setting::getValue('integration', 'revesms_secret_key', ''));

        // REVE SMS has its own sender ID setting (callerID) — do not fall back to the
        // BulkSMSBD sender ID, they are different accounts.
        $senderId = trim((string) Setting::getValue('integration', 'revesms_sender_id', ''));
        $sendUrl = $this->resolveProviderSendUrl('revesms');

        if ($apiKey === '' || $secretKey === '' || $senderId === '' || $sendUrl === '') {
            return [
                'success' => false,
                'code' => null,
                'message' => 'REVE SMS integration is not fully configured.',
                'raw' => null,
            ];
        }

        $response = Http::timeout(15)->get($sendUrl, [
            'apikey' => $apiKey,
            'secretkey' => $secretKey,
            'callerID' => $senderId,
            'toUser' => $numbers,
            'messageContent' => $message,
        ]);

        $raw = trim((string) $response->body());
        $decoded = json_decode($raw, true);
        $code = null;
        $success = false;

        if (is_array($decoded)) {
            $status = (string) ($decoded['Status'] ?? $decoded['status'] ?? '');
            $text = strtoupper((string) ($decoded['Text'] ?? $decoded['text'] ?? ''));
            $code = is_numeric($status) ? (int) $status : null;
            $success = $status === '0' || str_contains($text, 'ACCEPT') || str_contains($text, 'SUCCESS');
        } elseif (ctype_digit($raw)) {
            $code = (int) $raw;
            $success = $code === 0;
        } else {
            $success = str_contains(strtoupper($raw), 'ACCEPT') || str_contains(strtoupper($raw), 'SUCCESS');
        }

        return [
            'success' => $success,
            'code' => $code,
            'message' => $this->resolveCodeMessage($code, $raw),
            'raw' => $raw,
        ];
    }

    private function getBulkBalance(): array
    {
        $apiKey = trim((string) Setting::getValue('integration', 'sms_api_key', ''));
        $balanceUrl = $this->resolveProviderBalanceUrl('bulksmsbd');

        if ($apiKey === '' || $balanceUrl === '') {
            return [
                'success' => false,
                'message' => 'SMS balance API is not configured.',
                'balance' => null,
                'raw' => null,
            ];
        }

        $response = Http::timeout(10)->get($balanceUrl, ['api_key' => $apiKey]);
        $raw = trim((string) $response->body());

        if (! $response->successful()) {
            return [
                'success' => false,
                'message' => 'Failed to fetch balance.',
                'balance' => null,
                'raw' => $raw,
            ];
        }

        $code = ctype_digit($raw) ? (int) $raw : null;
        if ($code !== null && $code >= 1000) {
            return [
                'success' => false,
                'message' => $this->resolveCodeMessage($code, $raw),
                'balance' => null,
                'raw' => $raw,
            ];
        }

        $balance = $this->extractBalanceValue($raw);

        if ($balance === null) {
            return [
                'success' => false,
                'message' => 'Could not parse balance from SMS API response.',
                'balance' => null,
                'raw' => $raw,
            ];
        }

        return [
            'success' => true,
            'message' => 'Balance fetched successfully.',
            'balance' => $balance,
            'raw' => $raw,
        ];
    }

    private function getReveBalance(): array
    {
        $clientId = trim((string) Setting::getValue('integration', 'revesms_client_id', ''));
        $balanceUrl = $this->resolveProviderBalanceUrl('revesms');

        if ($clientId === '' || $balanceUrl === '') {
            return [
                'success' => false,
                'message' => 'REVE SMS balance API is not configured.',
                'balance' => null,
                'raw' => null,
            ];
        }

        // Stored balance URLs may include a stale ?client=... query; strip it so the
        // current client ID is always used.
        $balanceUrl = preg_replace('/\?.*$/', '', $balanceUrl);

        $response = Http::timeout(10)->get($balanceUrl, [
            'client' => $clientId,
        ]);

        $raw = trim((string) $response->body());

        if (! $response->successful()) {
            return [
                'success' => false,
                'message' => 'Failed to fetch balance.',
                'balance' => null,
                'raw' => $raw,
            ];
        }

        $balance = $this->extractBalanceValue($raw);

        if ($balance === null) {
            return [
                'success' => false,
                'message' => 'Could not parse balance from SMS API response.',
                'balance' => null,
                'raw' => $raw,
            ];
        }

        return [
            'success' => true,
            'message' => 'Balance fetched successfully.',
            'balance' => $balance,
            'raw' => $raw,
        ];
    }    private function sendCustomSms(string $numbers, string $message): array
    {
        $endpoint = $this->resolveCustomEndpoint('send');

        return match ($endpoint) {
            'revesms' => $this->sendReveSms($numbers, $message),
            'bulksmsbd' => $this->sendBulkSmsBd($numbers, $message),
            default => [
                'success' => false,
                'code' => null,
                'message' => 'Custom SMS gateway is not configured. Provide send credentials for REVE SMS or BulkSMSBD, or set a send API URL.',
                'raw' => null,
            ],
        };
    }

    private function getCustomBalance(): array
    {
        $endpoint = $this->resolveCustomEndpoint('balance');

        return match ($endpoint) {
            'revesms' => $this->getReveBalance(),
            'bulksmsbd' => $this->getBulkBalance(),
            default => [
                'success' => false,
                'message' => 'Custom SMS gateway balance is not configured. Provide a client ID (REVE) or API key (BulkSMSBD), or set a balance API URL.',
                'balance' => null,
                'raw' => null,
            ],
        };
    }

    /**
     * Resolve which known gateway format a custom gateway should use.
     *
     * Custom gateway mode starts blank: explicit custom_* credentials take priority,
     * then URL fingerprints, then any previously saved provider credentials.
     */
    private function resolveCustomEndpoint(string $purpose): ?string
    {
        $customApiKey = trim((string) Setting::getValue('integration', 'custom_sms_api_key', ''));
        $customSecretKey = trim((string) Setting::getValue('integration', 'custom_sms_secret_key', ''));
        $customSenderId = trim((string) Setting::getValue('integration', 'custom_sms_sender_id', ''));
        $customClientId = trim((string) Setting::getValue('integration', 'custom_sms_client_id', ''));
        $customSendUrl = trim((string) Setting::getValue('integration', 'custom_sms_send_url', ''));
        $customBalanceUrl = trim((string) Setting::getValue('integration', 'custom_sms_balance_url', ''));

        $revesmsApiKey = trim((string) Setting::getValue('integration', 'revesms_api_key', ''));
        $revesmsSecretKey = trim((string) Setting::getValue('integration', 'revesms_secret_key', ''));
        $revesmsSenderId = trim((string) Setting::getValue('integration', 'revesms_sender_id', ''));
        $revesmsClientId = trim((string) Setting::getValue('integration', 'revesms_client_id', ''));

        $bulkApiKey = trim((string) Setting::getValue('integration', 'sms_api_key', ''));
        $bulkSenderId = trim((string) Setting::getValue('integration', 'sms_sender_id', ''));

        $sendUrl = strtolower($customSendUrl !== '' ? $customSendUrl : (string) Setting::getValue('integration', 'sms_api_base_url', ''));
        $balanceUrl = strtolower($customBalanceUrl !== '' ? $customBalanceUrl : (string) Setting::getValue('integration', 'sms_balance_url', ''));

        if ($purpose === 'send') {
            if ($revesmsApiKey !== '' && $revesmsSecretKey !== '' && ($revesmsSenderId !== '' || $customSenderId !== '')) {
                return 'revesms';
            }

            if ($bulkApiKey !== '' && $bulkSenderId !== '') {
                return 'bulksmsbd';
            }
        } else {
            if ($revesmsClientId !== '') {
                return 'revesms';
            }

            if ($bulkApiKey !== '') {
                return 'bulksmsbd';
            }
        }

        if ($customApiKey !== '' || str_contains($sendUrl, 'revesms') || str_contains($balanceUrl, 'revesms')) {
            return 'revesms';
        }

        if (str_contains($sendUrl, 'bulksmsbd') || str_contains($balanceUrl, 'bulksmsbd')) {
            return 'bulksmsbd';
        }

        return null;
    }

    private function isBulkProvider(string $provider): bool
    {
        return $provider === 'bulksmsbd' || str_contains($provider, 'bulk');
    }

    private function isReveProvider(string $provider): bool
    {
        return $provider === 'revesms' || str_contains($provider, 'reve');
    }

    private function isCustomProvider(string $provider): bool
    {
        return $provider === 'custom';
    }

    private function resolveCodeMessage(?int $code, string $raw): string
    {
        $messages = [
            0 => 'SMS submitted successfully.',
            1 => 'SMS request failed.',
            2 => 'SMS request pending.',
            4 => 'SMS request sent.',
            101 => 'Internal server error.',
            108 => 'Wrong password or secret key.',
            109 => 'API key or user not provided, deleted, or invalid.',
            114 => 'Required message content or message id was not provided.',
            202 => 'SMS submitted successfully.',
            1001 => 'Invalid number.',
            1002 => 'Sender ID is invalid or disabled.',
            1003 => 'Required fields are missing.',
            1005 => 'Internal SMS provider error.',
            1006 => 'Balance validity not available.',
            1007 => 'Insufficient balance.',
            1011 => 'User ID not found.',
            1012 => 'Masking SMS must be sent in Bengali.',
            1013 => 'Sender ID gateway not found for this API key.',
            1014 => 'Sender type name not found.',
            1015 => 'No valid gateway found for this sender ID.',
            1016 => 'Sender type active price info not found.',
            1017 => 'Sender type price info not found.',
            1018 => 'SMS account is disabled.',
            1019 => 'Sender type price is disabled.',
            1020 => 'Parent account not found.',
            1021 => 'Parent account active price not found.',
            1031 => 'Account not verified.',
            1032 => 'IP is not whitelisted.',
        ];

        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $msg = $decoded['error_message'] ?? $decoded['success_message'] ?? $decoded['message'] ?? null;
            if ($msg && trim((string) $msg) !== '') {
                return trim((string) $msg);
            }
        }

        if ($code !== null && isset($messages[$code])) {
            return $messages[$code];
        }

        return $raw !== '' ? $raw : 'Unknown SMS API response.';
    }

    private function extractBalanceValue(string $raw): ?string
    {
        if ($raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            foreach (['balance', 'current_balance', 'amount'] as $key) {
                if (isset($decoded[$key]) && is_scalar($decoded[$key])) {
                    $candidate = trim((string) $decoded[$key]);
                    if ($candidate !== '' && preg_match('/^-?\d+(?:\.\d+)?$/', $candidate)) {
                        return $candidate;
                    }
                }
            }
        }

        if (preg_match('/-?\d+(?:\.\d+)?/', $raw, $matches)) {
            return $matches[0];
        }

        return null;
    }
}
