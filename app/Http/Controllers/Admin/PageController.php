<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PageController extends Controller
{
    public function index(Request $request)
    {
        $query = Page::query();
        
        if ($search = $request->input('search')) {
            $query->where('title', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
        }
        
        $pages = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();
        return view('admin.pages.index', compact('pages'));
    }

    public function create()
    {
        return view('admin.pages.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:pages,slug',
            'content' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        $validated['slug'] = $validated['slug'] ? Str::slug($validated['slug']) : Str::slug($validated['title']);
        $validated['is_active'] = $request->has('is_active');

        // Ensure unique slug
        $originalSlug = $validated['slug'];
        $count = 1;
        while (Page::where('slug', $validated['slug'])->exists()) {
            $validated['slug'] = "{$originalSlug}-{$count}";
            $count++;
        }

        Page::create($validated);

        return redirect()->route('admin.pages.index')
            ->with('success', 'Page created successfully.');
    }

    public function edit(Page $page)
    {
        return view('admin.pages.edit', compact('page'));
    }

    public function update(Request $request, Page $page)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:pages,slug,' . $page->id,
            'content' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        $validated['slug'] = $validated['slug'] ? Str::slug($validated['slug']) : Str::slug($validated['title']);
        $validated['is_active'] = $request->has('is_active');

        // Ensure unique slug
        $originalSlug = $validated['slug'];
        $count = 1;
        while (Page::where('slug', $validated['slug'])->where('id', '!=', $page->id)->exists()) {
            $validated['slug'] = "{$originalSlug}-{$count}";
            $count++;
        }

        $page->update($validated);

        return redirect()->route('admin.pages.index')
            ->with('success', 'Page updated successfully.');
    }

    public function destroy(Page $page)
    {
        $page->delete();

        return redirect()->route('admin.pages.index')
            ->with('success', 'Page deleted successfully.');
    }
}
