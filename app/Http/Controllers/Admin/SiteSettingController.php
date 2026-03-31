<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SiteSettingController extends Controller
{
    /**
     * Show all settings grouped
     */
    public function index()
    {
        $settings = Setting::orderBy('group')->orderBy('sort_order')->get()->groupBy('group');
        $groups = Setting::distinct()->pluck('group')->toArray();

        return view('admin.settings.site.index', compact('settings', 'groups'));
    }

    /**
     * Show settings for a specific group
     */
    public function editGroup(string $group)
    {
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
        $settings = Setting::where('group', $group)->get();

        foreach ($settings as $setting) {
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
                }
            } 
            // Handle checkbox/boolean
            elseif ($setting->type === 'boolean') {
                $setting->value = $request->has("settings.{$key}") ? '1' : '0';
                $setting->save();
            }
            // Handle regular fields
            elseif ($request->has("settings.{$key}")) {
                $setting->value = $request->input("settings.{$key}");
                $setting->save();
            }
        }

        // Clear cache
        Setting::clearCache();

        return redirect()->route('admin.settings.site.edit-group', $group)
            ->with('success', 'Settings updated successfully.');
    }

    /**
     * Delete an image from a setting
     */
    public function deleteImage(Request $request, string $group, string $key)
    {
        $setting = Setting::where('group', $group)->where('key', $key)->first();

        if (!$setting || $setting->type !== 'image') {
            return back()->with('error', 'Setting not found.');
        }

        if ($setting->value && Storage::disk('public')->exists($setting->value)) {
            Storage::disk('public')->delete($setting->value);
        }

        $setting->value = '';
        $setting->save();

        Setting::clearCache();

        return back()->with('success', 'Image deleted successfully.');
    }

    /**
     * Create a new setting
     */
    public function create()
    {
        $groups = Setting::distinct()->pluck('group')->toArray();
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

        Setting::create([
            'group' => $request->group,
            'key' => $request->key,
            'label' => $request->label,
            'type' => $request->type,
            'value' => $request->value ?? '',
            'description' => $request->description,
            'is_public' => $request->boolean('is_public', true),
            'sort_order' => Setting::where('group', $request->group)->max('sort_order') + 1,
        ]);

        Setting::clearCache();

        return redirect()->route('admin.settings.site.edit-group', $request->group)
            ->with('success', 'Setting created successfully.');
    }

    /**
     * Delete a setting
     */
    public function destroy(Setting $setting)
    {
        $group = $setting->group;

        // Delete associated image if exists
        if ($setting->type === 'image' && $setting->value && Storage::disk('public')->exists($setting->value)) {
            Storage::disk('public')->delete($setting->value);
        }

        $setting->delete();
        Setting::clearCache();

        return redirect()->route('admin.settings.site.edit-group', $group)
            ->with('success', 'Setting deleted successfully.');
    }
}
