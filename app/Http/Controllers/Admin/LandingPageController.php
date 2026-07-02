<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LandingPage;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class LandingPageController extends Controller
{
    public function index(Request $request)
    {
        $query = LandingPage::with('product');

        if ($search = $request->input('search')) {
            $query->where('title', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhereHas('product', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
        }

        $landingPages = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();
        return view('admin.landing-pages.index', compact('landingPages'));
    }

    public function create()
    {
        $products = Product::active()->orderBy('name')->get(['id', 'name']);
        return view('admin.landing-pages.create', compact('products'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_ids'    => 'required|array|min:1',
            'product_ids.*'  => 'required|exists:products,id',
            'title'          => 'required|string|max:255',
            'slug'           => 'nullable|string|max:255|unique:landing_pages,slug',
            'template_type'  => 'required|string|in:default,clothing,am,khejur,digital_item,inner_item,sexual_item',
            'theme_color'    => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'banner_image'   => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:4096',
            'video_embed_code' => 'nullable|string',
            'features'       => 'nullable|array',
            'testimonials'   => 'nullable|array',
            'custom_css'     => 'nullable|string',
            'is_active'      => 'boolean',
            'show_location'  => 'boolean',
        ]);

        // Normalize product_ids (unique ints)
        $productIds = array_values(array_unique(array_map('intval', $validated['product_ids'])));
        $validated['product_ids'] = $productIds;
        // Keep primary product_id as first selected (backward compat)
        $validated['product_id'] = $productIds[0] ?? null;

        $validated['slug'] = $validated['slug'] ? Str::slug($validated['slug']) : Str::slug($validated['title']);
        $validated['is_active'] = $request->has('is_active');
        $validated['show_location'] = $request->has('show_location');

        // Ensure unique slug
        $originalSlug = $validated['slug'];
        $count = 1;
        while (LandingPage::where('slug', $validated['slug'])->exists()) {
            $validated['slug'] = "{$originalSlug}-{$count}";
            $count++;
        }

        // Handle Banner Image File Upload
        if ($request->hasFile('banner_image')) {
            $path = $request->file('banner_image')->store('landing-pages', 'public');
            $validated['banner_image'] = '/storage/' . $path;
        }

        // Normalize features
        if (isset($validated['features'])) {
            $validated['features'] = array_values(array_filter($validated['features'], function ($item) {
                return !empty($item['title']);
            }));
        }

        // Normalize testimonials
        if (isset($validated['testimonials'])) {
            $validated['testimonials'] = array_values(array_filter($validated['testimonials'], function ($item) {
                return !empty($item['name']);
            }));
        }

        LandingPage::create($validated);

        return redirect()->route('admin.landing-pages.index')
            ->with('success', 'Landing page created successfully.');
    }

    public function edit(LandingPage $landingPage)
    {
        $products = Product::active()->orderBy('name')->get(['id', 'name']);
        return view('admin.landing-pages.edit', compact('landingPage', 'products'));
    }

    public function update(Request $request, LandingPage $landingPage)
    {
        $validated = $request->validate([
            'product_ids'    => 'required|array|min:1',
            'product_ids.*'  => 'required|exists:products,id',
            'title'          => 'required|string|max:255',
            'slug'           => 'nullable|string|max:255|unique:landing_pages,slug,' . $landingPage->id,
            'template_type'  => 'required|string|in:default,clothing,am,khejur,digital_item,inner_item,sexual_item',
            'theme_color'    => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'banner_image'   => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:4096',
            'video_embed_code' => 'nullable|string',
            'features'       => 'nullable|array',
            'testimonials'   => 'nullable|array',
            'custom_css'     => 'nullable|string',
            'is_active'      => 'boolean',
            'show_location'  => 'boolean',
        ]);

        // Normalize product_ids
        $productIds = array_values(array_unique(array_map('intval', $validated['product_ids'])));
        $validated['product_ids'] = $productIds;
        // Keep primary product_id as first selected (backward compat)
        $validated['product_id'] = $productIds[0] ?? null;

        $validated['slug'] = $validated['slug'] ? Str::slug($validated['slug']) : Str::slug($validated['title']);
        $validated['is_active'] = $request->has('is_active');
        $validated['show_location'] = $request->has('show_location');

        // Ensure unique slug
        $originalSlug = $validated['slug'];
        $count = 1;
        while (LandingPage::where('slug', $validated['slug'])->where('id', '!=', $landingPage->id)->exists()) {
            $validated['slug'] = "{$originalSlug}-{$count}";
            $count++;
        }

        // Handle Banner Image File Upload
        if ($request->hasFile('banner_image')) {
            if ($landingPage->banner_image) {
                $oldPath = str_replace('/storage/', '', $landingPage->banner_image);
                Storage::disk('public')->delete($oldPath);
            }
            $path = $request->file('banner_image')->store('landing-pages', 'public');
            $validated['banner_image'] = '/storage/' . $path;
        } else {
            $validated['banner_image'] = $landingPage->banner_image;
        }

        // Normalize features
        if (isset($validated['features'])) {
            $validated['features'] = array_values(array_filter($validated['features'], function ($item) {
                return !empty($item['title']);
            }));
        } else {
            $validated['features'] = [];
        }

        // Normalize testimonials
        if (isset($validated['testimonials'])) {
            $validated['testimonials'] = array_values(array_filter($validated['testimonials'], function ($item) {
                return !empty($item['name']);
            }));
        } else {
            $validated['testimonials'] = [];
        }

        $landingPage->update($validated);

        return redirect()->route('admin.landing-pages.index')
            ->with('success', 'Landing page updated successfully.');
    }

    public function destroy(LandingPage $landingPage)
    {
        if ($landingPage->banner_image) {
            $oldPath = str_replace('/storage/', '', $landingPage->banner_image);
            Storage::disk('public')->delete($oldPath);
        }

        $landingPage->delete();

        return redirect()->route('admin.landing-pages.index')
            ->with('success', 'Landing page deleted successfully.');
    }
}
