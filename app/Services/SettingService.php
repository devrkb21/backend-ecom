<?php

namespace App\Services;

use App\Models\Setting;
use App\Repositories\Interfaces\SettingRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class SettingService
{
    protected const CACHE_KEY = 'settings';
    protected const CACHE_TTL = 3600;

    public function __construct(
        protected SettingRepositoryInterface $settingRepository
    ) {}

    /**
     * Get all settings for frontend (public only, grouped)
     */
    public function getAllPublicSettings(): array
    {
        return Cache::remember(self::CACHE_KEY . '.public.all', self::CACHE_TTL, function () {
            return Setting::getAllPublic();
        });
    }

    /**
     * Get settings by group (for frontend)
     */
    public function getPublicSettingsByGroup(string $group): array
    {
        return Cache::remember(self::CACHE_KEY . ".public.{$group}", self::CACHE_TTL, function () use ($group) {
            return Setting::getGroup($group, true);
        });
    }

    /**
     * Get all settings for admin (including non-public)
     */
    public function getAllSettingsForAdmin(): Collection
    {
        return Cache::remember(self::CACHE_KEY . '.admin.all', self::CACHE_TTL, function () {
            return Setting::orderBy('group')->orderBy('sort_order')->get();
        });
    }

    /**
     * Get settings by group for admin
     */
    public function getSettingsByGroupForAdmin(string $group): Collection
    {
        return $this->settingRepository->getByGroup($group, false);
    }

    /**
     * Get all available groups
     */
    public function getGroups(): array
    {
        return $this->settingRepository->getAllGroups();
    }

    /**
     * Get a single setting
     */
    public function getSetting(string $group, string $key): ?Setting
    {
        return $this->settingRepository->getByGroupAndKey($group, $key);
    }

    /**
     * Update or create a setting
     */
    public function updateSetting(string $group, string $key, array $data): Setting
    {
        // Handle image upload if present
        if (isset($data['value']) && $data['value'] instanceof UploadedFile) {
            $data['value'] = $this->uploadImage($data['value'], $group);
        }

        $setting = $this->settingRepository->updateOrCreateSetting($group, $key, array_merge(
            $data,
            ['group' => $group, 'key' => $key]
        ));

        $this->clearCache();

        return $setting;
    }

    /**
     * Bulk update settings
     */
    public function bulkUpdateSettings(array $settings): void
    {
        foreach ($settings as &$setting) {
            if (isset($setting['value']) && $setting['value'] instanceof UploadedFile) {
                $setting['value'] = $this->uploadImage($setting['value'], $setting['group']);
            }
        }

        $this->settingRepository->bulkUpdate($settings);
        $this->clearCache();
    }

    /**
     * Delete a setting
     */
    public function deleteSetting(int $id): void
    {
        $setting = $this->settingRepository->findOrFail($id);
        
        // Delete image if it's an image type
        if ($setting->type === 'image' && $setting->value) {
            Storage::disk('public')->delete($setting->value);
        }

        $this->settingRepository->delete($id);
        $this->clearCache();
    }

    /**
     * Delete all settings in a group
     */
    public function deleteGroup(string $group): void
    {
        $this->settingRepository->deleteByGroup($group);
        $this->clearCache();
    }

    /**
     * Upload an image and return the path
     */
    protected function uploadImage(UploadedFile $file, string $group): string
    {
        $filename = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
        $path = "settings/{$group}";
        
        $file->storeAs($path, $filename, 'public');

        return "{$path}/{$filename}";
    }

    /**
     * Get default settings structure
     */
    public function getDefaultSettings(): array
    {
        return [
            'hero' => [
                ['key' => 'title', 'type' => 'text', 'label' => 'Hero Title', 'value' => 'Welcome to Our Store'],
                ['key' => 'subtitle', 'type' => 'text', 'label' => 'Hero Subtitle', 'value' => 'Discover amazing products'],
                ['key' => 'description', 'type' => 'textarea', 'label' => 'Hero Description', 'value' => ''],
                ['key' => 'image', 'type' => 'image', 'label' => 'Hero Background Image', 'value' => ''],
                ['key' => 'button_text', 'type' => 'text', 'label' => 'Button Text', 'value' => 'Shop Now'],
                ['key' => 'button_link', 'type' => 'text', 'label' => 'Button Link', 'value' => '/products'],
                ['key' => 'enabled', 'type' => 'boolean', 'label' => 'Show Hero Section', 'value' => '1'],
            ],
            'general' => [
                ['key' => 'site_name', 'type' => 'text', 'label' => 'Site Name', 'value' => 'Inner Collection'],
                ['key' => 'site_logo', 'type' => 'image', 'label' => 'Site Logo', 'value' => ''],
                ['key' => 'site_favicon', 'type' => 'image', 'label' => 'Favicon', 'value' => ''],
                ['key' => 'contact_email', 'type' => 'text', 'label' => 'Contact Email', 'value' => ''],
                ['key' => 'contact_phone', 'type' => 'text', 'label' => 'Contact Phone', 'value' => ''],
                ['key' => 'address', 'type' => 'textarea', 'label' => 'Address', 'value' => ''],
                ['key' => 'currency', 'type' => 'text', 'label' => 'Currency Code', 'value' => 'BDT'],
                ['key' => 'currency_symbol', 'type' => 'text', 'label' => 'Currency Symbol', 'value' => '৳'],
            ],
            'social' => [
                ['key' => 'facebook', 'type' => 'text', 'label' => 'Facebook URL', 'value' => ''],
                ['key' => 'instagram', 'type' => 'text', 'label' => 'Instagram URL', 'value' => ''],
                ['key' => 'twitter', 'type' => 'text', 'label' => 'Twitter URL', 'value' => ''],
                ['key' => 'youtube', 'type' => 'text', 'label' => 'YouTube URL', 'value' => ''],
                ['key' => 'linkedin', 'type' => 'text', 'label' => 'LinkedIn URL', 'value' => ''],
            ],
            'seo' => [
                ['key' => 'meta_title', 'type' => 'text', 'label' => 'Meta Title', 'value' => ''],
                ['key' => 'meta_description', 'type' => 'textarea', 'label' => 'Meta Description', 'value' => ''],
                ['key' => 'meta_keywords', 'type' => 'text', 'label' => 'Meta Keywords', 'value' => ''],
                ['key' => 'og_image', 'type' => 'image', 'label' => 'OG Image', 'value' => ''],
            ],
            'footer' => [
                ['key' => 'copyright_text', 'type' => 'text', 'label' => 'Copyright Text', 'value' => '© 2026 Inner Collection. All rights reserved.'],
                ['key' => 'footer_description', 'type' => 'textarea', 'label' => 'Footer Description', 'value' => ''],
                ['key' => 'show_newsletter', 'type' => 'boolean', 'label' => 'Show Newsletter', 'value' => '1'],
                ['key' => 'newsletter_title', 'type' => 'text', 'label' => 'Newsletter Title', 'value' => 'Subscribe to our newsletter'],
            ],
            'banner' => [
                ['key' => 'promo_enabled', 'type' => 'boolean', 'label' => 'Show Promo Banner', 'value' => '0'],
                ['key' => 'promo_text', 'type' => 'text', 'label' => 'Promo Text', 'value' => ''],
                ['key' => 'promo_link', 'type' => 'text', 'label' => 'Promo Link', 'value' => ''],
                ['key' => 'promo_bg_color', 'type' => 'text', 'label' => 'Promo Background Color', 'value' => '#000000'],
            ],
        ];
    }

    /**
     * Seed default settings
     */
    public function seedDefaultSettings(): void
    {
        $defaults = $this->getDefaultSettings();
        $sortOrder = 0;

        foreach ($defaults as $group => $settings) {
            foreach ($settings as $setting) {
                Setting::updateOrCreate(
                    ['group' => $group, 'key' => $setting['key']],
                    [
                        'type' => $setting['type'],
                        'label' => $setting['label'],
                        'value' => $setting['value'],
                        'is_public' => true,
                        'sort_order' => $sortOrder++,
                    ]
                );
            }
        }

        $this->clearCache();
    }

    /**
     * Clear all settings cache
     */
    protected function clearCache(): void
    {
        Setting::clearCache();
        Cache::forget(self::CACHE_KEY . '.public.all');
        Cache::forget(self::CACHE_KEY . '.admin.all');
        
        foreach ($this->getGroups() as $group) {
            Cache::forget(self::CACHE_KEY . ".public.{$group}");
        }
    }
}
