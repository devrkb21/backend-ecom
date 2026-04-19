<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\CheckoutAddressConfigService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SiteSettingController extends Controller
{
    private const RESTRICTED_GROUPS = ['integration'];

    /**
     * Ensure order and cart behavior settings exist in the general group.
     */
    protected function ensureGeneralOrderSettingsExist(): void
    {
        $definitions = [
            [
                'key' => 'call_for_order_phone',
                'value' => '',
                'type' => 'text',
                'label' => 'Call For Order Phone',
                'description' => 'Phone number used for the call for order button on product pages.',
                'is_public' => true,
                'sort_order' => 6,
            ],
            [
                'key' => 'whatsapp_order_phone',
                'value' => '',
                'type' => 'text',
                'label' => 'WhatsApp Order Number',
                'description' => 'WhatsApp number used for quick order from product pages.',
                'is_public' => true,
                'sort_order' => 7,
            ],
            [
                'key' => 'whatsapp_order_message',
                'value' => 'Assalamu Alaikum, I want to order: {product_name}. Product URL: {product_url}. Quantity: {quantity}.',
                'type' => 'textarea',
                'label' => 'WhatsApp Order Message',
                'description' => 'Template for WhatsApp order message. Available placeholders: {product_name}, {product_url}, {quantity}, {price}, {sku}.',
                'is_public' => true,
                'sort_order' => 8,
            ],
            [
                'key' => 'open_side_cart_on_add',
                'value' => '1',
                'type' => 'boolean',
                'label' => 'Open Side Cart After Add To Cart',
                'description' => 'When enabled, the side cart drawer opens immediately after a product is added to cart.',
                'is_public' => true,
                'sort_order' => 9,
            ],
            [
                'key' => 'product_grid_columns',
                'value' => '5',
                'type' => 'number',
                'label' => 'Product Grid Columns',
                'description' => 'Number of products shown per row in product grids (allowed: 3, 4, 5, 6).',
                'is_public' => true,
                'sort_order' => 13,
            ],
            [
                'key' => 'order_number_prefix',
                'value' => 'ORD',
                'type' => 'text',
                'label' => 'Order Number Prefix',
                'description' => 'Prefix used at the start of each order number (letters, numbers, dash, underscore).',
                'is_public' => false,
                'sort_order' => 14,
            ],
            [
                'key' => 'order_number_generation_mode',
                'value' => 'timestamp_random',
                'type' => 'text',
                'label' => 'Order Number Generation Mode',
                'description' => 'Choose how order numbers are generated.',
                'is_public' => false,
                'sort_order' => 15,
            ],
            [
                'key' => 'stock_enabled',
                'value' => '1',
                'type' => 'boolean',
                'label' => 'Enable Stock Tracking',
                'description' => 'When disabled, stock is ignored across cart and checkout and stock inputs become optional.',
                'is_public' => true,
                'sort_order' => 16,
            ],
        ];

        $created = false;

        foreach ($definitions as $definition) {
            $setting = Setting::firstOrCreate(
                ['group' => 'general', 'key' => $definition['key']],
                array_merge(['group' => 'general'], $definition)
            );

            if ($setting->wasRecentlyCreated) {
                $created = true;
            }
        }

        if ($created) {
            Setting::clearCache('general');
        }
    }

    /**
     * Ensure checkout field-manager settings exist.
     */
    protected function ensureCheckoutSettingsExist(): void
    {
        /** @var CheckoutAddressConfigService $checkoutConfigService */
        $checkoutConfigService = app(CheckoutAddressConfigService::class);

        $definitions = [
            [
                'key' => 'checkout_form_enabled',
                'value' => '1',
                'type' => 'boolean',
                'label' => 'Enable Checkout Form',
                'description' => 'Disable to block checkout submissions from frontend.',
                'is_public' => true,
                'sort_order' => 1,
            ],
            [
                'key' => 'enable_guest_checkout',
                'value' => '1',
                'type' => 'boolean',
                'label' => 'Enable Guest Checkout',
                'description' => 'When disabled, customers must login before adding items to cart or placing an order.',
                'is_public' => true,
                'sort_order' => 2,
            ],
            [
                'key' => 'tax_enabled',
                'value' => '0',
                'type' => 'boolean',
                'label' => 'Enable Tax',
                'description' => 'Toggle tax calculation for checkout and order totals.',
                'is_public' => true,
                'sort_order' => 3,
            ],
            [
                'key' => 'tax_percentage',
                'value' => '0',
                'type' => 'decimal',
                'label' => 'Tax Percentage',
                'description' => 'Percentage applied on cart subtotal when tax is enabled.',
                'is_public' => true,
                'sort_order' => 4,
            ],
            [
                'key' => 'checkout_fields_schema',
                'value' => json_encode($checkoutConfigService->getDefaultFieldSchema()),
                'type' => 'json',
                'label' => 'Checkout Fields Schema',
                'description' => 'JSON schema for fully customizable billing, shipping, and additional checkout fields.',
                'is_public' => true,
                'sort_order' => 5,
            ],
        ];

        $created = false;

        foreach ($definitions as $definition) {
            $setting = Setting::firstOrCreate(
                ['group' => 'checkout', 'key' => $definition['key']],
                array_merge(['group' => 'checkout'], $definition)
            );

            if ($setting->wasRecentlyCreated) {
                $created = true;
            }
        }

        if ($created) {
            Setting::clearCache('checkout');
        }
    }

    /**
     * Show all settings grouped
     */
    public function index()
    {
        $this->ensureGeneralOrderSettingsExist();
        $this->ensureCheckoutSettingsExist();

        $settings = Setting::whereNotIn('group', self::RESTRICTED_GROUPS)
            ->orderBy('group')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('group');

        $groups = Setting::whereNotIn('group', self::RESTRICTED_GROUPS)
            ->distinct()
            ->orderBy('group')
            ->pluck('group')
            ->toArray();

        return view('admin.settings.site.index', compact('settings', 'groups'));
    }

    /**
     * Show settings for a specific group
     */
    public function editGroup(string $group)
    {
        if ($this->isRestrictedGroup($group)) {
            return $this->redirectToDedicatedIntegrationSettings();
        }

        if ($group === 'general') {
            $this->ensureGeneralOrderSettingsExist();
        }

        if ($group === 'checkout') {
            $this->ensureCheckoutSettingsExist();
        }

        $settings = Setting::where('group', $group)->orderBy('sort_order')->get();
        
        if ($settings->isEmpty()) {
            return redirect()->route('admin.settings.site.index')
                ->with('error', 'Setting group not found.');
        }

        $groupLabels = [
            'hero' => 'Hero Section',
            'general' => 'General Settings',
            'social' => 'Social Media',
            'seo' => 'SEO Settings',
            'footer' => 'Footer Settings',
            'banner' => 'Promo Banner',
            'checkout' => 'Checkout Settings',
        ];

        if ($group === 'checkout') {
            /** @var CheckoutAddressConfigService $checkoutConfigService */
            $checkoutConfigService = app(CheckoutAddressConfigService::class);
            $checkoutRaw = $checkoutConfigService->getRaw();

            $settingValues = [
                'checkout_form_enabled' => (bool) ($checkoutRaw['checkout_form_enabled'] ?? true),
                'enable_guest_checkout' => (bool) ($checkoutRaw['enable_guest_checkout'] ?? true),
                'tax_enabled' => (bool) ($checkoutRaw['tax_enabled'] ?? false),
                'tax_percentage' => (float) ($checkoutRaw['tax_percentage'] ?? 0),
            ];

            return view('admin.settings.site.edit-checkout', [
                'settings' => $settings,
                'group' => $group,
                'groupLabel' => $groupLabels[$group] ?? ucfirst($group),
                'settingValues' => $settingValues,
                'checkoutFieldSchema' => $checkoutRaw['checkout_fields_schema'] ?? $checkoutConfigService->getDefaultFieldSchema(),
                'fieldTypeOptions' => $checkoutConfigService->getFieldTypeOptions(),
            ]);
        }

        return view('admin.settings.site.edit-group', [
            'settings' => $settings,
            'group' => $group,
            'groupLabel' => $groupLabels[$group] ?? ucfirst($group),
        ]);
    }

    /**
     * Update settings for a group
     */
    public function updateGroup(Request $request, string $group)
    {
        if ($this->isRestrictedGroup($group)) {
            return $this->redirectToDedicatedIntegrationSettings();
        }

        if ($group === 'checkout') {
            $request->validate([
                'settings.tax_enabled' => 'nullable|boolean',
                'settings.tax_percentage' => 'nullable|numeric|min:0|max:100',
            ]);
        }

        if ($group === 'general') {
            $request->validate([
                'settings.product_grid_columns' => 'nullable|integer|in:3,4,5,6',
                'settings.order_number_prefix' => ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9_-]+$/'],
                'settings.order_number_generation_mode' => ['nullable', 'string', 'in:timestamp_random,date_sequence,global_sequence'],
            ]);

            $this->ensureGeneralOrderSettingsExist();
        }

        if ($group === 'checkout') {
            $this->ensureCheckoutSettingsExist();
        }

        $settings = Setting::where('group', $group)->get();
        $updatedKeys = [];

        $settings->each(function (Setting $setting) use ($request, &$updatedKeys) {
            $key = $setting->key;

            if (
                $setting->group === 'checkout'
                && !in_array($key, ['checkout_form_enabled', 'enable_guest_checkout', 'tax_enabled', 'tax_percentage', 'checkout_fields_schema'], true)
            ) {
                return;
            }
            
            // Handle image type - now uses media library path
            if ($setting->type === 'image') {
                $newValue = $request->input("settings.{$key}");
                
                // Only update if value changed
                if ($newValue !== $setting->value) {
                    // Delete old image if it's not from media library
                    if ($setting->value && !str_starts_with($setting->value, 'media/') && Storage::disk('public')->exists($setting->value)) {
                        Storage::disk('public')->delete($setting->value);
                    }
                    
                    $setting->value = $newValue ?: '';
                    $setting->save();
                    $updatedKeys[] = $key;
                }
            } 
            // Handle checkout field schema JSON
            elseif ($setting->key === 'checkout_fields_schema') {
                /** @var CheckoutAddressConfigService $checkoutConfigService */
                $checkoutConfigService = app(CheckoutAddressConfigService::class);

                $rawJson = $request->input("settings.{$key}", '[]');
                $decoded = json_decode((string) $rawJson, true);
                $normalized = $checkoutConfigService->normalizeFieldSchema(is_array($decoded) ? $decoded : []);
                $newValue = json_encode($normalized);

                if ($newValue !== $setting->value) {
                    $setting->value = $newValue;
                    $setting->save();
                    $updatedKeys[] = $key;
                }
            }
            // Handle checkbox/boolean
            elseif ($setting->type === 'boolean') {
                $newValue = $request->has("settings.{$key}") ? '1' : '0';

                if ($newValue !== $setting->value) {
                    $setting->value = $newValue;
                    $setting->save();
                    $updatedKeys[] = $key;
                }
            }
            // Handle tax percentage as sanitized numeric value
            elseif ($setting->group === 'checkout' && $setting->key === 'tax_percentage') {
                $rawValue = $request->input("settings.{$key}", 0);
                $numericValue = is_numeric($rawValue) ? (float) $rawValue : 0.0;
                $newValue = (string) max(0, min(100, $numericValue));

                if ($newValue !== $setting->value) {
                    $setting->value = $newValue;
                    $setting->save();
                    $updatedKeys[] = $key;
                }
            }
            // Handle product grid columns as constrained enum values
            elseif ($setting->group === 'general' && $setting->key === 'product_grid_columns') {
                if (!$request->has("settings.{$key}")) {
                    return;
                }

                $allowed = [3, 4, 5, 6];
                $rawValue = (int) $request->input("settings.{$key}", 5);
                $newValue = (string) (in_array($rawValue, $allowed, true) ? $rawValue : 5);

                if ($newValue !== $setting->value) {
                    $setting->value = $newValue;
                    $setting->save();
                    $updatedKeys[] = $key;
                }
            }
            // Handle order number prefix
            elseif ($setting->group === 'general' && $setting->key === 'order_number_prefix') {
                if (!$request->has("settings.{$key}")) {
                    return;
                }

                $rawValue = strtoupper(trim((string) $request->input("settings.{$key}", 'ORD')));
                $sanitized = preg_replace('/[^A-Z0-9_-]/', '', $rawValue) ?? '';
                $newValue = $sanitized !== '' ? substr($sanitized, 0, 20) : 'ORD';

                if ($newValue !== $setting->value) {
                    $setting->value = $newValue;
                    $setting->save();
                    $updatedKeys[] = $key;
                }
            }
            // Handle order number generation mode
            elseif ($setting->group === 'general' && $setting->key === 'order_number_generation_mode') {
                if (!$request->has("settings.{$key}")) {
                    return;
                }

                $allowedModes = ['timestamp_random', 'date_sequence', 'global_sequence'];
                $rawValue = (string) $request->input("settings.{$key}", 'timestamp_random');
                $newValue = in_array($rawValue, $allowedModes, true) ? $rawValue : 'timestamp_random';

                if ($newValue !== $setting->value) {
                    $setting->value = $newValue;
                    $setting->save();
                    $updatedKeys[] = $key;
                }
            }
            // Handle regular fields
            elseif ($request->has("settings.{$key}")) {
                $newValue = $request->input("settings.{$key}");

                if ($newValue !== $setting->value) {
                    $setting->value = $newValue;
                    $setting->save();
                    $updatedKeys[] = $key;
                }
            }
        });

        // Clear cache
        Setting::clearCache($group);

        if ($group === 'checkout' && !empty($updatedKeys)) {
            Log::debug('Checkout settings updated.', [
                'updated_keys' => array_values(array_unique($updatedKeys)),
            ]);
        }

        return redirect()->route('admin.settings.site.edit-group', $group)
            ->with('success', 'Settings updated successfully.');
    }

    /**
     * Delete an image from a setting
     */
    public function deleteImage(Request $request, string $group, string $key)
    {
        if ($this->isRestrictedGroup($group)) {
            return $this->redirectToDedicatedIntegrationSettings();
        }

        $setting = Setting::where('group', $group)->where('key', $key)->first();

        if (!$setting || $setting->type !== 'image') {
            return back()->with('error', 'Setting not found.');
        }

        if ($setting->value && Storage::disk('public')->exists($setting->value)) {
            Storage::disk('public')->delete($setting->value);
        }

        $setting->value = '';
        $setting->save();

        Setting::clearCache($group, $key);

        return back()->with('success', 'Image deleted successfully.');
    }

    /**
     * Create a new setting
     */
    public function create()
    {
        $groups = Setting::whereNotIn('group', self::RESTRICTED_GROUPS)
            ->distinct()
            ->orderBy('group')
            ->pluck('group')
            ->toArray();
        $types = ['text', 'textarea', 'image', 'boolean', 'number', 'json'];

        return view('admin.settings.site.create', compact('groups', 'types'));
    }

    /**
     * Store a new setting
     */
    public function store(Request $request)
    {
        $request->validate([
            'group' => 'required|string|max:255',
            'key' => 'required|string|max:255|unique:settings,key,NULL,id,group,' . $request->group,
            'label' => 'required|string|max:255',
            'type' => 'required|string|in:text,textarea,image,boolean,number,json',
            'value' => 'nullable',
            'description' => 'nullable|string',
            'is_public' => 'boolean',
        ]);

        $group = trim((string) $request->group);

        if ($this->isRestrictedGroup($group)) {
            return redirect()
                ->route('admin.settings.integrations')
                ->with('error', 'Integration settings are managed from the Integrations menu.');
        }

        Setting::create([
            'group' => $group,
            'key' => $request->key,
            'label' => $request->label,
            'type' => $request->type,
            'value' => $request->value ?? '',
            'description' => $request->description,
            'is_public' => $request->boolean('is_public', true),
            'sort_order' => Setting::where('group', $group)->max('sort_order') + 1,
        ]);

        Setting::clearCache($group, $request->key);

        return redirect()->route('admin.settings.site.edit-group', $group)
            ->with('success', 'Setting created successfully.');
    }

    /**
     * Delete a setting
     */
    public function destroy(Setting $setting)
    {
        $group = $setting->group;

        if ($this->isRestrictedGroup($group)) {
            return $this->redirectToDedicatedIntegrationSettings();
        }

        // Delete associated image if exists
        if ($setting->type === 'image' && $setting->value && Storage::disk('public')->exists($setting->value)) {
            Storage::disk('public')->delete($setting->value);
        }

        $setting->delete();
        Setting::clearCache($group);

        return redirect()->route('admin.settings.site.edit-group', $group)
            ->with('success', 'Setting deleted successfully.');
    }

    protected function isRestrictedGroup(string $group): bool
    {
        return in_array(strtolower($group), self::RESTRICTED_GROUPS, true);
    }

    protected function redirectToDedicatedIntegrationSettings()
    {
        return redirect()
            ->route('admin.settings.integrations')
            ->with('info', 'Integration settings are managed from the Integrations menu.');
    }
}
