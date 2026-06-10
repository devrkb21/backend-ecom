<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use devrkb21\PathaoLaravel\APIBase\PathaoAuth;
use devrkb21\PathaoLaravel\Facades\PathaoLaravel;
use devrkb21\PathaoLaravel\Requests\PathaoStoreRequest;

class PathaoCourierController extends Controller
{
    private const GROUP = 'courier';

    public function dashboard()
    {
        $this->ensureDefaultSettings();

        $settings = Setting::where('group', self::GROUP)
            ->orderBy('sort_order')
            ->get()
            ->keyBy('key');

        $sentToCourierCount = \App\Models\Order::where('carrier', 'pathao')->count();
        $pendingSendCount = \App\Models\Order::whereIn('status', ['pending', 'processing'])
                                 ->where(function($q) {
                                     $q->whereNull('carrier')->orWhere('carrier', '!=', 'pathao');
                                 })
                                 ->count();

        // Try to fetch stores list if authenticated
        $stores = [];
        $connectionSuccess = false;
        $connectionMessage = '';

        if ($settings->get('pathao_enabled')?->value == '1' &&
            !empty($settings->get('pathao_client_id')?->value) &&
            !empty($settings->get('pathao_client_secret')?->value)) {
            
            try {
                $this->initializePathao();
                
                $response = PathaoLaravel::GET_STORES();
                
                if (isset($response['status']) && $response['status'] == 200) {
                    $stores = $response['data']['data'] ?? [];
                    $connectionSuccess = true;
                } else {
                    $connectionMessage = $response['message'] ?? 'Could not retrieve stores from Pathao.';
                }
            } catch (\Exception $e) {
                $connectionMessage = $e->getMessage();
            }
        }

        $merchantInfo = Setting::getValue(self::GROUP, 'pathao_merchant_info');

        return view('admin.settings.couriers.pathao', compact(
            'settings',
            'sentToCourierCount',
            'pendingSendCount',
            'stores',
            'connectionSuccess',
            'connectionMessage',
            'merchantInfo'
        ));
    }

    public function update(Request $request): RedirectResponse
    {
        $this->ensureDefaultSettings();

        $validated = $request->validate([
            'pathao_enabled' => ['nullable', 'boolean'],
            'pathao_client_id' => ['required_if:pathao_enabled,1', 'nullable', 'string', 'max:255'],
            'pathao_client_secret' => ['required_if:pathao_enabled,1', 'nullable', 'string', 'max:255'],
            'pathao_sandbox' => ['nullable', 'boolean'],
            'pathao_store_id' => ['nullable', 'string', 'max:255'],
            'pathao_webhook_integration_secret' => ['nullable', 'string', 'max:255'],
        ], [
            'pathao_client_id.required_if' => 'The Pathao Client ID is required when Pathao is enabled.',
            'pathao_client_secret.required_if' => 'The Pathao Client Secret is required when Pathao is enabled.',
        ]);

        $currentValues = Setting::where('group', self::GROUP)
            ->pluck('value', 'key')
            ->all();

        $clientId = trim((string)$request->input('pathao_client_id'));
        $clientSecret = trim((string)$request->input('pathao_client_secret'));
        $sandbox = $request->boolean('pathao_sandbox') ? '1' : '0';
        $enabled = $request->boolean('pathao_enabled') ? '1' : '0';
        $storeId = trim((string)$request->input('pathao_store_id'));

        // If credentials changed or enabled, let's request token programmatically
        $secretToken = $currentValues['pathao_secret_token'] ?? '';
        
        if ($enabled === '1' && 
            (!empty($clientId) && !empty($clientSecret)) &&
            ($clientId !== ($currentValues['pathao_client_id'] ?? '') || 
             $clientSecret !== ($currentValues['pathao_client_secret'] ?? '') ||
             $sandbox !== ($currentValues['pathao_sandbox'] ?? '0') ||
             empty($secretToken))
        ) {
            try {
                config([
                    'pathao.pathao_client_id' => $clientId,
                    'pathao.pathao_client_secret' => $clientSecret,
                    'pathao.sandbox' => $sandbox === '1',
                    'pathao.pathao_db_table_name' => 'pathao-courier',
                ]);

                $auth = new PathaoAuth();
                $cred = [
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                ];

                $response = $auth->getAccessToken($cred);
                $data = $response->getData();

                if ($response->isSuccess() && !empty($data['secret_token'])) {
                    $secretToken = $data['secret_token'];
                } else {
                    $errorMsg = $response->getMessage() ?: 'Invalid credentials or connection error.';
                    return back()
                        ->withInput()
                        ->with('error', 'Pathao Authentication Failed: ' . $errorMsg);
                }
            } catch (\Exception $e) {
                Log::error('Pathao config token fetch error: ' . $e->getMessage());
                return back()
                    ->withInput()
                    ->with('error', 'Pathao Authentication Exception: ' . $e->getMessage());
            }
        }

        $definitions = $this->definitions();
        
        foreach ($definitions as $index => $definition) {
            $key = $definition['key'];
            
            if ($key === 'pathao_enabled') {
                $value = $enabled;
            } elseif ($key === 'pathao_sandbox') {
                $value = $sandbox;
            } elseif ($key === 'pathao_secret_token') {
                $value = $secretToken;
            } elseif (array_key_exists($key, $validated)) {
                $value = trim((string)($validated[$key] ?? ''));
            } else {
                $value = (string)($currentValues[$key] ?? ($definition['default'] ?? ''));
            }

            Setting::setValue(self::GROUP, $key, $value, [
                'type' => $definition['type'],
                'label' => $definition['label'],
                'description' => $definition['description'],
                'is_public' => $definition['is_public'],
                'sort_order' => $index + 10, // Steadfast handles 1-5, we start around 10
            ]);
        }

        Setting::clearCache(self::GROUP);

        return redirect()
            ->route('admin.settings.couriers.pathao')
            ->with('success', 'Pathao Courier settings updated and authenticated successfully.');
    }

    public function syncLocations(): RedirectResponse
    {
        try {
            $this->initializePathao();
            
            // Register command manually since it's restricted to console in the package
            app(\Illuminate\Contracts\Console\Kernel::class)->registerCommand(app(\devrkb21\PathaoLaravel\Commands\PathaoSyncLocationsCommand::class));
            
            // Run pathao sync locations command
            Artisan::call('pathao:sync-locations');
            
            return redirect()
                ->route('admin.settings.couriers.pathao')
                ->with('success', 'Locations synced successfully from Pathao API.');
        } catch (\Exception $e) {
            Log::error('Pathao locations sync error: ' . $e->getMessage());
            return redirect()
                ->route('admin.settings.couriers.pathao')
                ->with('error', 'An error occurred during location sync: ' . $e->getMessage());
        }
    }

    public function createStore(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_name' => ['required', 'string', 'max:255'],
            'contact_number' => ['required', 'string', 'regex:/^(?:\+880|880|01[3-9])\d{8}$/'],
            'address' => ['required', 'string', 'min:10'],
            'city_id' => ['required', 'numeric'],
            'zone_id' => ['required', 'numeric'],
            'area_id' => ['required', 'numeric'],
        ], [
            'contact_number.regex' => 'The contact number format is invalid. Must be a valid Bangladeshi number (e.g. 01712345678).',
        ]);

        try {
            $this->initializePathao();

            $storeRequest = new PathaoStoreRequest();
            $storeRequest->replace([
                'name' => $request->name,
                'contact_name' => $request->contact_name,
                'contact_number' => $request->contact_number,
                'address' => $request->address,
                'city_id' => (int)$request->city_id,
                'zone_id' => (int)$request->zone_id,
                'area_id' => (int)$request->area_id,
            ]);

            $response = PathaoLaravel::CREATE_STORE($storeRequest);

            if (isset($response['status']) && $response['status'] == 200) {
                $storeName = $response['data']['data']['store_name'] ?? $request->name;
                $storeId = $response['data']['data']['store_id'] ?? null;
                
                $message = "Store '{$storeName}' created successfully in Pathao.";
                if ($storeId) {
                    $message .= " Store ID: {$storeId}.";
                }
                
                return redirect()
                    ->route('admin.settings.couriers.pathao')
                    ->with('success', $message);
            } else {
                $errorMsg = $response['message'] ?? 'Unknown API error';
                if (isset($response['data']) && is_array($response['data'])) {
                    $errorMsg .= ' - ' . json_encode($response['data']);
                }
                return back()
                    ->withInput()
                    ->with('error', 'Pathao API Error: ' . $errorMsg);
            }
        } catch (\Exception $e) {
            Log::error('Pathao Store Creation Error: ' . $e->getMessage());
            return back()
                ->withInput()
                ->with('error', 'Pathao Store Creation Error: ' . $e->getMessage());
        }
    }

    public function testConnection(): RedirectResponse
    {
        try {
            $this->initializePathao();

            $response = PathaoLaravel::GET_MERCHANT_INFO();
            
            if (isset($response['status']) && $response['status'] == 200) {
                $data = $response['data']['data'] ?? $response['data'] ?? [];
                
                if (!empty($data['merchant_name'])) {
                    // Update cache in database setting
                    Setting::setValue(self::GROUP, 'pathao_merchant_info', $data, [
                        'type' => 'json',
                        'label' => 'Pathao Merchant Info Cache',
                        'description' => 'Cached Pathao merchant profile data.',
                        'is_public' => false,
                        'sort_order' => 16,
                    ]);
                    Setting::clearCache(self::GROUP);

                    return redirect()
                        ->route('admin.settings.couriers.pathao')
                        ->with('success', 'Pathao Connection Successful! Merchant profile loaded and cached.');
                }
            }

            $errorMsg = $response['message'] ?? 'Invalid response format from Pathao API.';
            if (isset($response['data']) && is_array($response['data'])) {
                $errorMsg .= ' - ' . json_encode($response['data']);
            }
            return redirect()
                ->route('admin.settings.couriers.pathao')
                ->with('error', 'Pathao Connection Failed: ' . $errorMsg);

        } catch (\Exception $e) {
            Log::error('Pathao Test Connection Error: ' . $e->getMessage());
            return redirect()
                ->route('admin.settings.couriers.pathao')
                ->with('error', 'Pathao Connection Error: ' . $e->getMessage());
        }
    }

    private function initializePathao(): void
    {
        $clientId = Setting::getValue(self::GROUP, 'pathao_client_id');
        $clientSecret = Setting::getValue(self::GROUP, 'pathao_client_secret');
        $secretToken = Setting::getValue(self::GROUP, 'pathao_secret_token');
        $webhookSecret = Setting::getValue(self::GROUP, 'pathao_webhook_integration_secret');
        $sandbox = filter_var(Setting::getValue(self::GROUP, 'pathao_sandbox', '0'), FILTER_VALIDATE_BOOLEAN);

        config([
            'pathao.pathao_client_id' => $clientId,
            'pathao.pathao_client_secret' => $clientSecret,
            'pathao.pathao_secret_token' => $secretToken,
            'pathao.webhook_integration_secret' => $webhookSecret,
            'pathao.sandbox' => $sandbox,
            'pathao.pathao_db_table_name' => 'pathao-courier',
        ]);
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
                    'sort_order' => $index + 10,
                ]
            );
        }
    }

    private function definitions(): array
    {
        return [
            [
                'key' => 'pathao_enabled',
                'label' => 'Enable Pathao Courier',
                'type' => 'boolean',
                'default' => '0',
                'description' => 'Enable Pathao Courier Integration.',
                'is_public' => false,
            ],
            [
                'key' => 'pathao_client_id',
                'label' => 'Pathao Client ID',
                'type' => 'text',
                'default' => '',
                'description' => 'Pathao Client ID from Merchant API credentials.',
                'is_public' => false,
            ],
            [
                'key' => 'pathao_client_secret',
                'label' => 'Pathao Client Secret',
                'type' => 'text',
                'default' => '',
                'description' => 'Pathao Client Secret from Merchant API credentials.',
                'is_public' => false,
            ],
            [
                'key' => 'pathao_sandbox',
                'label' => 'Pathao Sandbox Mode',
                'type' => 'boolean',
                'default' => '1',
                'description' => 'Enable Pathao Sandbox/Staging Mode.',
                'is_public' => false,
            ],
            [
                'key' => 'pathao_secret_token',
                'label' => 'Pathao Secret Token',
                'type' => 'text',
                'default' => '',
                'description' => 'Pathao Secret Auth Token returned after credentials handshake.',
                'is_public' => false,
            ],
            [
                'key' => 'pathao_webhook_integration_secret',
                'label' => 'Pathao Webhook Integration Secret',
                'type' => 'text',
                'default' => '',
                'description' => 'UUID provided by Pathao Dashboard for webhook integration challenge.',
                'is_public' => false,
            ],
            [
                'key' => 'pathao_store_id',
                'label' => 'Pathao Default Store ID',
                'type' => 'text',
                'default' => '',
                'description' => 'The default Store ID to use for order creation.',
                'is_public' => false,
            ],
            [
                'key' => 'pathao_merchant_info',
                'label' => 'Pathao Merchant Info Cache',
                'type' => 'json',
                'default' => '[]',
                'description' => 'Cached Pathao merchant profile data.',
                'is_public' => false,
            ],
        ];
    }
}
