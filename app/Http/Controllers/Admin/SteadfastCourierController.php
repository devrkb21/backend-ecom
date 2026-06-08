<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use SteadFast\SteadFastCourierLaravelPackage\Facades\SteadfastCourier;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

class SteadfastCourierController extends Controller
{
    private const GROUP = 'courier';

    public function dashboard()
    {
        $this->ensureDefaultSettings();

        $settings = Setting::where('group', self::GROUP)
            ->orderBy('sort_order')
            ->get()
            ->keyBy('key');

        $sentToCourierCount = Order::where('carrier', 'steadfast')->count();
        $pendingSendCount = Order::whereIn('status', ['pending', 'processing'])
                                 ->where(function($q) {
                                     $q->whereNull('carrier')->orWhere('carrier', '!=', 'steadfast');
                                 })
                                 ->count();

        return view('admin.settings.couriers.steadfast', compact('settings', 'sentToCourierCount', 'pendingSendCount'));
    }

    public function update(Request $request): RedirectResponse
    {
        $this->ensureDefaultSettings();

        $validated = $request->validate([
            'steadfast_enabled' => ['nullable', 'boolean'],
            'steadfast_api_key' => ['required_if:steadfast_enabled,1', 'nullable', 'string', 'max:255'],
            'steadfast_secret_key' => ['required_if:steadfast_enabled,1', 'nullable', 'string', 'max:255'],
            'steadfast_webhook_token' => ['required_if:steadfast_enabled,1', 'nullable', 'string', 'max:255'],
        ], [
            'steadfast_api_key.required_if' => 'The SteadFast API Key is required when SteadFast is enabled.',
            'steadfast_secret_key.required_if' => 'The SteadFast Secret Key is required when SteadFast is enabled.',
            'steadfast_webhook_token.required_if' => 'The SteadFast Webhook Token is required when SteadFast is enabled.',
        ]);

        $currentValues = Setting::where('group', self::GROUP)
            ->pluck('value', 'key')
            ->all();

        $fieldToggleMap = $this->fieldToggleMap();

        foreach ($this->definitions() as $index => $definition) {
            $key = $definition['key'];

            if ($definition['type'] === 'boolean') {
                $value = $request->boolean($key) ? '1' : '0';
            } else {
                $toggleKey = $fieldToggleMap[$key] ?? null;

                if ($toggleKey && !$request->boolean($toggleKey)) {
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
            ->route('admin.settings.couriers.steadfast')
            ->with('success', 'SteadFast Courier settings updated successfully.');
    }

    public function checkBalance()
    {
        try {
            $apiKey = Setting::getValue(self::GROUP, 'steadfast_api_key');
            $secretKey = Setting::getValue(self::GROUP, 'steadfast_secret_key');
            
            if (empty($apiKey) || empty($secretKey)) {
                return response()->json([
                    'success' => false,
                    'message' => 'API credentials are not configured.'
                ]);
            }

            config([
                'steadfast-courier.api_key' => $apiKey,
                'steadfast-courier.secret_key' => $secretKey,
                'steadfast-courier.base_url' => 'https://portal.packzy.com/api/v1',
            ]);

            $response = SteadfastCourier::getCurrentBalance();

            if (isset($response['status']) && $response['status'] == 200) {
                $balance = $response['current_balance'] ?? 0;
                
                // Save it for later display
                Setting::setValue(self::GROUP, 'steadfast_last_balance', $balance);
                Setting::clearCache(self::GROUP);

                return response()->json([
                    'success' => true,
                    'balance' => $balance
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $response['message'] ?? 'Failed to retrieve balance from SteadFast API.'
            ]);

        } catch (\Exception $e) {
            Log::error('Steadfast balance check error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while communicating with SteadFast.'
            ]);
        }
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
                'key' => 'steadfast_enabled',
                'label' => 'Enable SteadFast Courier',
                'type' => 'boolean',
                'default' => '0',
                'description' => 'Enable SteadFast Courier Integration.',
                'is_public' => false,
            ],
            [
                'key' => 'steadfast_api_key',
                'label' => 'SteadFast API Key',
                'type' => 'text',
                'default' => '',
                'description' => 'SteadFast API Key.',
                'is_public' => false,
            ],
            [
                'key' => 'steadfast_secret_key',
                'label' => 'SteadFast Secret Key',
                'type' => 'text',
                'default' => '',
                'description' => 'SteadFast Secret Key.',
                'is_public' => false,
            ],
            [
                'key' => 'steadfast_webhook_token',
                'label' => 'SteadFast Webhook Auth Token',
                'type' => 'text',
                'default' => '',
                'description' => 'Bearer token for Webhook Authentication. You generate this yourself or provide it to SteadFast.',
                'is_public' => false,
            ],
            [
                'key' => 'steadfast_last_balance',
                'label' => 'SteadFast Last Balance',
                'type' => 'text',
                'default' => '',
                'description' => 'Last retrieved balance from SteadFast API.',
                'is_public' => false,
            ],
        ];
    }

    private function fieldToggleMap(): array
    {
        return [
            'steadfast_api_key' => 'steadfast_enabled',
            'steadfast_secret_key' => 'steadfast_enabled',
            'steadfast_webhook_token' => 'steadfast_enabled',
        ];
    }
}
