<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Order;
use App\Models\OrderStatus;
use Illuminate\Support\Facades\Http;

class SmsService
{
    private const DEFAULT_SEND_URL = 'http://www.bulksmsbd.net/api/smsapi';
    private const DEFAULT_BALANCE_URL = 'http://www.bulksmsbd.net/api/getBalanceApi';

    public function isEnabled(): bool
    {
        return (bool) Setting::getValue('integration', 'sms_enabled', false);
    }

    /**
     * Send SMS to one or multiple phone numbers.
     *
     * @param string|array<int, string> $numbers
     */
    public function send(string|array $numbers, string $message): array
    {
        if (!$this->isEnabled()) {
            return [
                'success' => false,
                'code' => null,
                'message' => 'SMS integration is disabled.',
                'raw' => null,
            ];
        }

        $apiKey = trim((string) Setting::getValue('integration', 'sms_api_key', ''));
        $senderId = trim((string) Setting::getValue('integration', 'sms_sender_id', ''));
        $sendUrl = trim((string) Setting::getValue('integration', 'sms_api_base_url', self::DEFAULT_SEND_URL));

        if ($apiKey === '' || $senderId === '' || $sendUrl === '') {
            return [
                'success' => false,
                'code' => null,
                'message' => 'SMS integration is not fully configured.',
                'raw' => null,
            ];
        }

        $numberList = $this->normalizeNumbers($numbers);
        if ($numberList === '') {
            return [
                'success' => false,
                'code' => null,
                'message' => 'No valid phone numbers found.',
                'raw' => null,
            ];
        }

        $payload = [
            'api_key' => $apiKey,
            'type' => 'text',
            'number' => $numberList,
            'senderid' => $senderId,
            'message' => $message,
        ];

        $response = Http::asForm()
            ->timeout(15)
            ->withoutVerifying()
            ->post($sendUrl, $payload);

        $raw = trim((string) $response->body());
        
        // Attempt to parse JSON response
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
        if (!$this->isEnabled()) {
            return ['success' => false, 'message' => 'SMS integration is disabled.', 'raw' => null];
        }

        // Check if SMS is enabled for this status
        $smsEnabled = Setting::getValue('sms_templates', "sms_enabled_{$newStatusKey}", false);
        if (!$smsEnabled) {
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
        if (!$this->isEnabled()) {
            return [
                'success' => false,
                'message' => 'SMS integration is disabled.',
                'balance' => null,
                'raw' => null,
            ];
        }

        $apiKey = trim((string) Setting::getValue('integration', 'sms_api_key', ''));
        $balanceUrl = trim((string) Setting::getValue('integration', 'sms_balance_url', self::DEFAULT_BALANCE_URL));

        if ($apiKey === '' || $balanceUrl === '') {
            return [
                'success' => false,
                'message' => 'SMS balance API is not configured.',
                'balance' => null,
                'raw' => null,
            ];
        }

        $response = Http::timeout(10)
            ->withoutVerifying()
            ->get($balanceUrl, ['api_key' => $apiKey]);

        $raw = trim((string) $response->body());

        if (!$response->successful()) {
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

    /**
     * @param string|array<int, string> $numbers
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
                    $value = '88' . $value;
                }

                return $value;
            })
            ->filter(fn(?string $number) => is_string($number) && preg_match('/^88\d{11}$/', $number))
            ->unique()
            ->values();

        return $normalized->implode(',');
    }

    private function resolveCodeMessage(?int $code, string $raw): string
    {
        $messages = [
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

        // If it's a JSON response, try to find a message field
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $msg = $decoded['error_message'] ?? $decoded['success_message'] ?? $decoded['message'] ?? null;
            if ($msg && trim((string)$msg) !== '') {
                return trim((string)$msg);
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
