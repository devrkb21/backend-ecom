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
                'key' => 'product_grid_columns_desktop',
                'value' => '5',
                'type' => 'number',
                'label' => 'Product Grid Columns (Desktop)',
                'description' => 'Number of products shown per row in product grids on desktop (allowed: 3, 4, 5, 6).',
                'is_public' => true,
                'sort_order' => 13,
            ],
            [
                'key' => 'product_grid_columns_mobile',
                'value' => '2',
                'type' => 'number',
                'label' => 'Product Grid Columns (Mobile)',
                'description' => 'Number of products shown per row in product grids on mobile (allowed: 1, 2).',
                'is_public' => true,
                'sort_order' => 14,
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
                'value' => 'global_sequence',
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
                'key' => 'logo_height_desktop',
                'value' => '40',
                'type' => 'number',
                'label' => 'Logo Height (Desktop)',
                'description' => 'Height of the site logo on desktop devices (in pixels).',
                'is_public' => true,
                'sort_order' => 4,
            ],
            [
                'key' => 'logo_height_mobile',
                'value' => '32',
                'type' => 'number',
                'label' => 'Logo Height (Mobile)',
                'description' => 'Height of the site logo on mobile devices (in pixels).',
                'is_public' => true,
                'sort_order' => 5,
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
                'description' => 'Upload a shop logo for the invoice/shipping label.',
                'is_public' => false,
                'sort_order' => 1,
            ],
            [
                'key' => 'invoice_company_name',
                'value' => 'Inner Collection',
                'type' => 'text',
                'label' => 'Company Name',
                'description' => 'Official company name printed on invoices and labels.',
                'is_public' => false,
                'sort_order' => 2,
            ],
            [
                'key' => 'invoice_company_phone',
                'value' => '',
                'type' => 'text',
                'label' => 'Company Phone',
                'description' => 'Contact phone number printed on invoices and labels.',
                'is_public' => false,
                'sort_order' => 3,
            ],
            [
                'key' => 'invoice_company_address',
                'value' => '',
                'type' => 'textarea',
                'label' => 'Company Address',
                'description' => 'Shop address printed on invoices and labels.',
                'is_public' => false,
                'sort_order' => 4,
            ],
            [
                'key' => 'invoice_company_email',
                'value' => '',
                'type' => 'text',
                'label' => 'Company Email',
                'description' => 'Shop email address printed on invoices.',
                'is_public' => false,
                'sort_order' => 5,
            ],
            [
                'key' => 'invoice_company_domain',
                'value' => 'www.innercollection.com',
                'type' => 'text',
                'label' => 'Company Website Domain',
                'description' => 'Website domain printed on the invoice header. Leave blank if not required.',
                'is_public' => false,
                'sort_order' => 6,
            ],
            [
                'key' => 'invoice_footer_bg_color',
                'value' => '#000000',
                'type' => 'color',
                'label' => 'Invoice Footer Background Color',
                'description' => 'Background color of the invoice footer. Defaults to black (#000000).',
                'is_public' => false,
                'sort_order' => 7,
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
     * Show all settings grouped - Storefront Only
     */
    public function index()
    {
        $this->ensureNavigationSettingsExist();
        $this->ensureAppearanceSettingsExist();

        $storefrontGroups = ['hero', 'navigation', 'appearance', 'seo', 'social', 'banner', 'footer'];

        $settingsCollection = Setting::whereIn('group', $storefrontGroups)
            ->orderBy('group')
            ->orderBy('sort_order')
            ->get();

        $settings = $settingsCollection->groupBy('group');

        $groups = $settingsCollection->pluck('group')->unique()->values()->toArray();

        // Extra hero variables
        $heroSettings = $settings->get('hero', collect());
        $bannersSetting = $heroSettings->firstWhere('key', 'banners');
        if (!$bannersSetting) {
            // Read current individual settings to create the initial banner slide
            $title = $heroSettings->firstWhere('key', 'title')?->value ?? 'Welcome to Our Store';
            $subtitle = $heroSettings->firstWhere('key', 'subtitle')?->value ?? '';
            $description = $heroSettings->firstWhere('key', 'description')?->value ?? '';
            $image = $heroSettings->firstWhere('key', 'image')?->value ?? '';
            $buttonText = $heroSettings->firstWhere('key', 'button_text')?->value ?? 'Shop Now';
            $buttonLink = $heroSettings->firstWhere('key', 'button_link')?->value ?? '/products';
            $enabled = filter_var($heroSettings->firstWhere('key', 'enabled')?->value ?? '1', FILTER_VALIDATE_BOOLEAN);

            $initialBanner = [
                'title' => $title,
                'subtitle' => $subtitle,
                'description' => $description,
                'image' => $image,
                'button_text' => $buttonText,
                'button_link' => $buttonLink,
                'enabled' => $enabled
            ];

            Setting::create([
                'group' => 'hero',
                'key' => 'banners',
                'type' => 'json',
                'label' => 'Banners Configuration',
                'value' => json_encode([$initialBanner]),
                'is_public' => true,
                'sort_order' => 8
            ]);

            // Reload settings
            $settingsCollection = Setting::whereIn('group', $storefrontGroups)
                ->orderBy('group')
                ->orderBy('sort_order')
                ->get();
            $settings = $settingsCollection->groupBy('group');
            $heroSettings = $settings->get('hero', collect());
        }
        $banners = json_decode($heroSettings->firstWhere('key', 'banners')?->value ?? '[]', true);

        // Extra navigation variables
        $categories = \App\Models\Category::with('children')->whereNull('parent_id')->active()->ordered()->get();
        $allCategories = $this->flattenCategories($categories);
        $allPages = \App\Models\Page::active()->get();

        $groupLabels = [
            'hero' => 'Hero Section',
            'social' => 'Social Media',
            'seo' => 'SEO Settings',
            'footer' => 'Footer Settings',
            'banner' => 'Promo Banner',
            'navigation' => 'Navigation Menu',
            'appearance' => 'Appearance & Colors',
        ];

        return view('admin.settings.site.index', compact(
            'settings',
            'groups',
            'groupLabels',
            'banners',
            'allCategories',
            'allPages'
        ));
    }

    /**
     * Show unified system configurations dashboard page (General, Checkout, Invoice, Payment, Shipping, SMS, API etc.)
     */
    public function systemIndex(Request $request)
    {
        $this->ensureGeneralOrderSettingsExist();
        $this->ensureCheckoutSettingsExist();
        $this->ensureInvoiceSettingsExist();

        $systemGroups = ['general', 'checkout', 'invoice'];
        $settingsCollection = Setting::whereIn('group', $systemGroups)
            ->orderBy('group')
            ->orderBy('sort_order')
            ->get();

        $settings = $settingsCollection->groupBy('group');

        // Extra checkout variables
        /** @var CheckoutAddressConfigService $checkoutConfigService */
        $checkoutConfigService = app(CheckoutAddressConfigService::class);
        $checkoutRaw = $checkoutConfigService->getRaw();
        $checkoutSettingValues = [
            'checkout_form_enabled' => (bool) ($checkoutRaw['checkout_form_enabled'] ?? true),
            'enable_guest_checkout' => (bool) ($checkoutRaw['enable_guest_checkout'] ?? true),
            'tax_enabled' => (bool) ($checkoutRaw['tax_enabled'] ?? false),
            'tax_percentage' => (float) ($checkoutRaw['tax_percentage'] ?? 0),
        ];
        $checkoutFieldSchema = $checkoutRaw['checkout_fields_schema'] ?? $checkoutConfigService->getDefaultFieldSchema();
        $fieldTypeOptions = $checkoutConfigService->getFieldTypeOptions();

        // 1. Integrations Variables
        $integrationSettings = Setting::where('group', 'integration')
            ->orderBy('sort_order')
            ->get()
            ->keyBy('key');
        $smsEnabled = filter_var($integrationSettings->get('sms_enabled')?->value ?? '0', FILTER_VALIDATE_BOOLEAN);

        // 2. Courier Variables
        $courierSettings = Setting::where('group', 'courier')->orderBy('sort_order')->get()->keyBy('key');
        
        $sentToCourierCountSteadfast = \App\Models\Order::where('carrier', 'steadfast')->count();
        $pendingSendCountSteadfast = \App\Models\Order::whereIn('status', ['pending', 'processing'])
            ->where(function($q) {
                $q->whereNull('carrier')->orWhere('carrier', '!=', 'steadfast');
            })
            ->count();

        $sentToCourierCountPathao = \App\Models\Order::where('carrier', 'pathao')->count();
        $pendingSendCountPathao = \App\Models\Order::whereIn('status', ['pending', 'processing'])
            ->where(function($q) {
                $q->whereNull('carrier')->orWhere('carrier', '!=', 'pathao');
            })
            ->count();

        $pathaoStores = [];
        $pathaoConnectionSuccess = false;
        $pathaoConnectionMessage = '';

        if ($courierSettings->get('pathao_enabled')?->value == '1' &&
            !empty($courierSettings->get('pathao_client_id')?->value) &&
            !empty($courierSettings->get('pathao_client_secret')?->value)) {
            
            try {
                $this->initializePathao();
                $response = \devrkb21\PathaoLaravel\Facades\PathaoLaravel::GET_STORES();
                if (isset($response['status']) && $response['status'] == 200) {
                    $pathaoStores = $response['data']['data'] ?? [];
                    $pathaoConnectionSuccess = true;
                } else {
                    $pathaoConnectionMessage = $response['message'] ?? 'Could not retrieve stores from Pathao.';
                }
            } catch (\Exception $e) {
                $pathaoConnectionMessage = $e->getMessage();
            }
        }

        // 3. Payment Gateways Variables
        $gateways = \App\Models\PaymentGateway::orderBy('sort_order')->get();
        $currencySymbol = (string) Setting::getValue('general', 'currency_symbol', '৳');

        // 4. Shipping Methods Variables
        $methods = \App\Models\ShippingMethod::withCount('locationRules')
            ->orderBy('sort_order')
            ->get();

        // 5. Order Statuses Variables
        $statuses = \App\Models\OrderStatus::query()
            ->withCount('orders')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        // 6. Cancellation Reasons Variables
        $cancellationReasonsStr = Setting::getValue('general', 'cancellation_reasons', 'Out of Stock,Customer Request,Fraudulent,Payment Failed,Other');
        $cancellationReasons = array_values(array_filter(array_map('trim', explode(',', $cancellationReasonsStr))));

        // 7. SMS Templates Variables
        $smsTemplates = \App\Services\SmsService::getOrderSmsTemplates();

        $groupLabels = [
            'general' => 'General Settings',
            'checkout' => 'Checkout Settings',
            'invoice' => 'Invoice Settings',
        ];

        return view('admin.settings.system', compact(
            'settings',
            'groupLabels',
            'checkoutSettingValues',
            'checkoutFieldSchema',
            'fieldTypeOptions',
            'integrationSettings',
            'smsEnabled',
            'courierSettings',
            'sentToCourierCountSteadfast',
            'pendingSendCountSteadfast',
            'sentToCourierCountPathao',
            'pendingSendCountPathao',
            'pathaoStores',
            'pathaoConnectionSuccess',
            'pathaoConnectionMessage',
            'gateways',
            'currencySymbol',
            'methods',
            'statuses',
            'cancellationReasons',
            'smsTemplates'
        ));
    }

    private function initializePathao(): void
    {
        $clientId = Setting::getValue('courier', 'pathao_client_id');
        $clientSecret = Setting::getValue('courier', 'pathao_client_secret');
        $secretToken = Setting::getValue('courier', 'pathao_secret_token');
        $webhookSecret = Setting::getValue('courier', 'pathao_webhook_integration_secret');
        $sandbox = filter_var(Setting::getValue('courier', 'pathao_sandbox', '0'), FILTER_VALIDATE_BOOLEAN);

        config([
            'pathao.pathao_client_id' => $clientId,
            'pathao.pathao_client_secret' => $clientSecret,
            'pathao.pathao_secret_token' => $secretToken,
            'pathao.webhook_integration_secret' => $webhookSecret,
            'pathao.sandbox' => $sandbox,
            'pathao.pathao_db_table_name' => 'pathao-courier',
        ]);
    }

    /**
     * Show settings for a specific group
     */
    public function editGroup(string $group)
    {
        if ($this->isRestrictedGroup($group)) {
            return $this->redirectToDedicatedIntegrationSettings();
        }

        $systemGroups = ['general', 'checkout', 'invoice'];
        if (in_array($group, $systemGroups)) {
            return redirect()->route('admin.settings.system.index', ['group' => $group]);
        }

        return redirect()->route('admin.settings.site.index', ['group' => $group]);
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

        $this->ensureGeneralOrderSettingsExist();
        $this->ensureCheckoutSettingsExist();
        $this->ensureNavigationSettingsExist();
        $this->ensureAppearanceSettingsExist();
        $this->ensureInvoiceSettingsExist();

        if ($group === 'hero' && $request->has('settings.banners')) {
            $bannersJson = $request->input('settings.banners', '[]');
            $banners = json_decode((string) $bannersJson, true);
            if (is_array($banners) && count($banners) > 0) {
                $firstBanner = $banners[0];
                $request->merge([
                    'settings' => array_merge($request->input('settings', []), [
                        'title' => $firstBanner['title'] ?? '',
                        'subtitle' => $firstBanner['subtitle'] ?? '',
                        'description' => $firstBanner['description'] ?? '',
                        'image' => $firstBanner['image'] ?? '',
                        'button_text' => $firstBanner['button_text'] ?? '',
                        'button_link' => $firstBanner['button_link'] ?? '',
                        'enabled' => ($firstBanner['enabled'] ?? true) ? '1' : '0'
                    ])
                ]);
            }
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
                'settings.product_grid_columns_desktop' => 'nullable|integer|in:3,4,5,6',
                'settings.product_grid_columns_mobile' => 'nullable|integer|in:1,2',
                'settings.order_number_prefix' => ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9_-]+$/'],
                'settings.order_number_generation_mode' => ['nullable', 'string', 'in:timestamp_random,date_sequence,global_sequence,custom_format'],
                'settings.order_number_custom_format' => ['nullable', 'string', 'max:100'],
                'settings.logo_height' => 'nullable|integer|min:20|max:200',
                'settings.logo_height_desktop' => 'nullable|integer|min:10|max:300',
                'settings.logo_height_mobile' => 'nullable|integer|min:10|max:200',
            ]);
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
            elseif ($setting->group === 'general' && ($setting->key === 'product_grid_columns_desktop' || $setting->key === 'product_grid_columns_mobile')) {
                if (!$request->has("settings.{$key}")) {
                    return;
                }

                $allowed = $setting->key === 'product_grid_columns_mobile' ? [1, 2] : [3, 4, 5, 6];
                $default = $setting->key === 'product_grid_columns_mobile' ? 2 : 5;
                $rawValue = (int) $request->input("settings.{$key}", $default);
                $newValue = (string) (in_array($rawValue, $allowed, true) ? $rawValue : $default);

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
            elseif ($setting->group === 'general' && in_array($setting->key, ['logo_height', 'logo_height_desktop', 'logo_height_mobile'], true)) {
                if (!$request->has("settings.{$key}")) {
                    return;
                }

                $max = match($setting->key) {
                    'logo_height_desktop' => 300,
                    'logo_height_mobile' => 200,
                    default => 200,
                };

                $rawValue = (int) $request->input("settings.{$key}", 40);
                $newValue = (string) max(10, min($max, $rawValue));

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

        $systemGroups = ['general', 'checkout', 'invoice'];
        $route = in_array($group, $systemGroups) ? 'admin.settings.system.index' : 'admin.settings.site.index';

        return redirect()->route($route, ['group' => $group])
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

        $systemGroups = ['general', 'checkout', 'invoice'];
        $route = in_array($group, $systemGroups) ? 'admin.settings.system.index' : 'admin.settings.site.index';

        return redirect()->route($route, ['group' => $group])
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

        $systemGroups = ['general', 'checkout', 'invoice'];
        $route = in_array($group, $systemGroups) ? 'admin.settings.system.index' : 'admin.settings.site.index';

        return redirect()->route($route, ['group' => $group])
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
