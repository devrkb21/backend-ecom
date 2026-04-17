<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
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
     * Ensure guest checkout toggle exists in checkout settings.
     */
    protected function ensureCheckoutGuestSettingExists(): void
    {
        $setting = Setting::firstOrCreate(
            ['group' => 'checkout', 'key' => 'enable_guest_checkout'],
            [
                'group' => 'checkout',
                'key' => 'enable_guest_checkout',
                'value' => '1',
                'type' => 'boolean',
                'label' => 'Enable Guest Checkout',
                'description' => 'When disabled, customers must login before adding items to cart or placing an order.',
                'is_public' => true,
                'sort_order' => 2,
            ]
        );

        if ($setting->wasRecentlyCreated) {
            Setting::clearCache('checkout');
        }
    }

    /**
     * Show all settings grouped
     */
    public function index()
    {
        $this->ensureGeneralOrderSettingsExist();
        $this->ensureCheckoutGuestSettingExists();

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
            $this->ensureCheckoutGuestSettingExists();
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
            $this->ensureCheckoutGuestSettingExists();
        }

        $settings = Setting::where('group', $group)->get();
        $updatedKeys = [];

        $settings->each(function (Setting $setting) use ($request, &$updatedKeys) {
            $key = $setting->key;
            
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
            // Handle checkbox/boolean
            elseif ($setting->type === 'boolean') {
                $newValue = $request->has("settings.{$key}") ? '1' : '0';

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
