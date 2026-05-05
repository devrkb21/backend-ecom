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
     * Ensure appearance settings exist.
     */
    protected function ensureAppearanceSettingsExist(): void
    {
        $definitions = [
            [
                'key' => 'primary_color',
                'value' => '#db2777', // Default accent color (pink-600)
                'type' => 'color',
                'label' => 'Primary Color',
                'description' => 'Main accent color used for buttons, badges, and primary highlights.',
                'is_public' => true,
                'sort_order' => 1,
            ],
            [
                'key' => 'primary_hover_color',
                'value' => '#be185d', // Default accent hover (pink-700)
                'type' => 'color',
                'label' => 'Primary Hover / Link Color',
                'description' => 'Color used for links and button hover states.',
                'is_public' => true,
                'sort_order' => 2,
            ],
        ];

        $created = false;

        foreach ($definitions as $definition) {
            $setting = Setting::firstOrCreate(
                ['group' => 'appearance', 'key' => $definition['key']],
                array_merge(['group' => 'appearance'], $definition)
            );

            if ($setting->wasRecentlyCreated) {
                $created = true;
            }
        }

        if ($created) {
            Setting::clearCache('appearance');
        }
    }

    /**
     * Ensure order and cart behavior settings exist in the general group.
     */
    protected function ensureGeneralOrderSettingsExist(): void
    {
        $definitions = [
            [
                'key' => 'site_title',
                'value' => 'Inner Collection - Your Everyday Store',
                'type' => 'text',
                'label' => 'Site Title',
                'description' => 'The main title of the website, displayed in the browser tab and SEO.',
                'is_public' => true,
                'sort_order' => 1,
            ],
            [
                'key' => 'site_description',
                'value' => 'Discover quality products at Inner Collection',
                'type' => 'textarea',
                'label' => 'Site Description',
                'description' => 'A brief description of your website used for SEO and metadata.',
                'is_public' => true,
                'sort_order' => 2,
            ],
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
                'key' => 'order_number_custom_format',
                'value' => '{PREFIX}-{YYYY}{MM}{DD}-{SEQ:4}',
                'type' => 'text',
                'label' => 'Order Number Custom Format',
                'description' => 'Used when mode is Custom Format. Placeholders: {PREFIX}, {YYYY}, {YY}, {MM}, {DD}, {SEQ:length}, {RAND:length}. Example: {PREFIX}-{YYYY}{MM}{DD}-{SEQ:4}',
                'is_public' => false,
                'sort_order' => 16,
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
     * Ensure invoice settings exist.
     */
    protected function ensureInvoiceSettingsExist(): void
    {
        $definitions = [
            [
                'key' => 'invoice_logo',
                'value' => '',
                'type' => 'image',
                'label' => 'Invoice Logo',
                'description' => 'Logo to display on invoices and packaging slips.',
                'is_public' => false,
                'sort_order' => 1,
            ],
            [
                'key' => 'invoice_company_name',
                'value' => config('app.name', 'Inner Collection'),
                'type' => 'text',
                'label' => 'Company Name',
                'description' => 'Display name of your company on invoices.',
                'is_public' => false,
                'sort_order' => 2,
            ],
            [
                'key' => 'invoice_company_address',
                'value' => '',
                'type' => 'textarea',
                'label' => 'Company Address',
                'description' => 'Physical address of your business.',
                'is_public' => false,
                'sort_order' => 3,
            ],
            [
                'key' => 'invoice_company_phone',
                'value' => '',
                'type' => 'text',
                'label' => 'Company Phone',
                'description' => 'Contact phone number for support.',
                'is_public' => false,
                'sort_order' => 4,
            ],
            [
                'key' => 'invoice_company_email',
                'value' => '',
                'type' => 'text',
                'label' => 'Company Email',
                'description' => 'Contact email address for support.',
                'is_public' => false,
                'sort_order' => 5,
            ],
            [
                'key' => 'invoice_primary_color',
                'value' => '#1e293b',
                'type' => 'color',
                'label' => 'Invoice Theme Color',
                'description' => 'Primary color used for headers and accents in the PDF.',
                'is_public' => false,
                'sort_order' => 6,
            ],
            [
                'key' => 'invoice_title',
                'value' => 'Invoice',
                'type' => 'text',
                'label' => 'Invoice Document Title',
                'description' => 'The main heading on the invoice PDF.',
                'is_public' => false,
                'sort_order' => 7,
            ],
            [
                'key' => 'packaging_slip_title',
                'value' => 'Packaging Slip',
                'type' => 'text',
                'label' => 'Packaging Slip Title',
                'description' => 'The main heading on the packaging slip PDF.',
                'is_public' => false,
                'sort_order' => 8,
            ],
            [
                'key' => 'invoice_bill_to_label',
                'value' => 'Bill To',
                'type' => 'text',
                'label' => 'Bill To Label',
                'description' => 'Label for customer billing information.',
                'is_public' => false,
                'sort_order' => 9,
            ],
            [
                'key' => 'invoice_ship_to_label',
                'value' => 'Ship To',
                'type' => 'text',
                'label' => 'Ship To Label',
                'description' => 'Label for shipping address information.',
                'is_public' => false,
                'sort_order' => 10,
            ],
            [
                'key' => 'invoice_item_label',
                'value' => 'Item Description',
                'type' => 'text',
                'label' => 'Item Column Label',
                'description' => 'Heading for the product details column.',
                'is_public' => false,
                'sort_order' => 11,
            ],
            [
                'key' => 'invoice_qty_label',
                'value' => 'Qty',
                'type' => 'text',
                'label' => 'Quantity Column Label',
                'description' => 'Heading for the quantity column.',
                'is_public' => false,
                'sort_order' => 12,
            ],
            [
                'key' => 'invoice_price_label',
                'value' => 'Price',
                'type' => 'text',
                'label' => 'Price Column Label',
                'description' => 'Heading for the unit price column.',
                'is_public' => false,
                'sort_order' => 13,
            ],
            [
                'key' => 'invoice_total_label',
                'value' => 'Total',
                'type' => 'text',
                'label' => 'Total Column Label',
                'description' => 'Heading for the line total column.',
                'is_public' => false,
                'sort_order' => 14,
            ],
            [
                'key' => 'invoice_show_payment_status',
                'value' => '1',
                'type' => 'boolean',
                'label' => 'Show Payment Status',
                'description' => 'Toggle visibility of the payment status badge.',
                'is_public' => false,
                'sort_order' => 15,
            ],
            [
                'key' => 'invoice_show_shipping_method',
                'value' => '1',
                'type' => 'boolean',
                'label' => 'Show Shipping Method',
                'description' => 'Toggle visibility of the shipping method.',
                'is_public' => false,
                'sort_order' => 16,
            ],
            [
                'key' => 'invoice_font_size',
                'value' => '12',
                'type' => 'number',
                'label' => 'Base Font Size (px)',
                'description' => 'Adjust the global text size for the PDF.',
                'is_public' => false,
                'sort_order' => 17,
            ],
            [
                'key' => 'invoice_footer_notes',
                'value' => 'Thank you for your business!',
                'type' => 'textarea',
                'label' => 'Invoice Footer Notes',
                'description' => 'Notes displayed at the bottom of every invoice.',
                'is_public' => false,
                'sort_order' => 18,
            ],
            [
                'key' => 'invoice_terms',
                'value' => '',
                'type' => 'textarea',
                'label' => 'Terms & Conditions',
                'description' => 'Standard terms and conditions displayed on the invoice.',
                'is_public' => false,
                'sort_order' => 19,
            ],
            [
                'key' => 'invoice_custom_css',
                'value' => '',
                'type' => 'textarea',
                'label' => 'Custom CSS',
                'description' => 'Advanced: Add custom CSS to style the PDF documents.',
                'is_public' => false,
                'sort_order' => 20,
            ],
        ];

        $created = false;

        foreach ($definitions as $definition) {
            $setting = Setting::firstOrCreate(
                ['group' => 'invoice', 'key' => $definition['key']],
                array_merge(['group' => 'invoice'], $definition)
            );

            if ($setting->wasRecentlyCreated) {
                $created = true;
            }
        }

        if ($created) {
            Setting::clearCache('invoice');
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
     * Ensure navigation settings exist.
     */
    protected function ensureNavigationSettingsExist(): void
    {
        $defaultMenu = [
            ['label' => 'All Products', 'url' => '/products', 'type' => 'link'],
            ['label' => 'Categories', 'url' => '/categories', 'type' => 'link'],
            ['label' => 'Deals', 'url' => '/products?on_sale=true', 'type' => 'link']
        ];

        $setting = Setting::firstOrCreate(
            ['group' => 'navigation', 'key' => 'header_menu'],
            [
                'group' => 'navigation',
                'key' => 'header_menu',
                'value' => json_encode($defaultMenu),
                'type' => 'json',
                'label' => 'Header Menu',
                'description' => 'A JSON array of menu items: [{"label": "All Products", "url": "/products"}].',
                'is_public' => true,
                'sort_order' => 1,
            ]
        );

        if ($setting->wasRecentlyCreated) {
            Setting::clearCache('navigation');
        }
    }

    /**
     * Show all settings grouped
     */
    public function index()
    {
        $this->ensureGeneralOrderSettingsExist();
        $this->ensureCheckoutSettingsExist();
        $this->ensureNavigationSettingsExist();
        $this->ensureAppearanceSettingsExist();
        $this->ensureInvoiceSettingsExist();

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

        if ($group === 'navigation') {
            $this->ensureNavigationSettingsExist();
        }

        if ($group === 'appearance') {
            $this->ensureAppearanceSettingsExist();
        }

        if ($group === 'invoice') {
            $this->ensureInvoiceSettingsExist();
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
            'navigation' => 'Navigation Menu',
            'appearance' => 'Appearance & Colors',
            'invoice' => 'Invoice & Packaging Slip',
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

        $data = [
            'settings' => $settings,
            'group' => $group,
            'groupLabel' => $groupLabels[$group] ?? ucfirst($group),
        ];

        if ($group === 'navigation') {
            $categories = \App\Models\Category::with('children')->whereNull('parent_id')->active()->ordered()->get();
            $data['allCategories'] = $this->flattenCategories($categories);
            $data['allPages'] = \App\Models\Page::active()->get();
        }

        return view('admin.settings.site.edit-group', $data);
    }

    private function flattenCategories($categories, $level = 0)
    {
        $flattened = collect();
        foreach ($categories as $category) {
            $category->name_with_indent = str_repeat('— ', $level) . $category->name;
            $flattened->push($category);
            if ($category->children && $category->children->count() > 0) {
                $flattened = $flattened->merge($this->flattenCategories($category->children, $level + 1));
            }
        }
        return $flattened;
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
                'settings.site_title' => 'nullable|string|max:255',
                'settings.site_description' => 'nullable|string|max:1000',
                'settings.product_grid_columns' => 'nullable|integer|in:3,4,5,6',
                'settings.order_number_prefix' => ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9_-]+$/'],
                'settings.order_number_generation_mode' => ['nullable', 'string', 'in:timestamp_random,date_sequence,global_sequence,custom_format'],
                'settings.order_number_custom_format' => ['nullable', 'string', 'max:100'],
                'settings.logo_height' => 'nullable|integer|min:20|max:120',
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

                $allowedModes = ['timestamp_random', 'date_sequence', 'global_sequence', 'custom_format'];
                $rawValue = (string) $request->input("settings.{$key}", 'timestamp_random');
                $newValue = in_array($rawValue, $allowedModes, true) ? $rawValue : 'timestamp_random';

                if ($newValue !== $setting->value) {
                    $setting->value = $newValue;
                    $setting->save();
                    $updatedKeys[] = $key;
                }
            }
            // Handle logo height as clamped integer
            elseif ($setting->group === 'general' && $setting->key === 'logo_height') {
                if (!$request->has("settings.{$key}")) {
                    return;
                }

                $rawValue = (int) $request->input("settings.{$key}", 40);
                $newValue = (string) max(20, min(120, $rawValue));

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
        $types = ['text', 'textarea', 'image', 'boolean', 'number', 'json', 'color'];

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
            'type' => 'required|string|in:text,textarea,image,boolean,number,json,color',
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
