<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        // Full collection for the tree view
        $allCategories = Category::with(['parent', 'children'])
            ->withCount('products')
            ->orderBy('name')
            ->get();

        // Paginated collection for the flat table
        $perPage = in_array((int) $request->input('per_page'), [20, 50, 100], true) ? (int) $request->input('per_page') : 20;
        $query = Category::with(['parent', 'children'])->withCount('products');

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        }

        $categories = $query->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.categories.index', compact('allCategories', 'categories'));
    }

    public function create()
    {
        $parentCategories = Category::whereNull('parent_id')->get();

        return view('admin.categories.create', compact('parentCategories'));
    }

    public function store(StoreCategoryRequest $request)
    {
        Category::create($request->validated());
        $this->clearCategoryCache();

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Category created successfully.');
    }

    public function edit(Category $category)
    {
        $parentCategories = Category::whereNull('parent_id')
            ->where('id', '!=', $category->id)
            ->get();

        return view('admin.categories.edit', compact('category', 'parentCategories'));
    }

    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $category->update($request->validated());
        $this->clearCategoryCache();

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Category updated successfully.');
    }

    public function destroy(Category $category)
    {
        if ($category->products()->exists()) {
            return back()->with('error', 'Cannot delete category with products.');
        }

        if ($category->children()->exists()) {
            return back()->with('error', 'Cannot delete category with subcategories.');
        }

        $category->delete();
        $this->clearCategoryCache();

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Category deleted successfully.');
    }

    private function clearCategoryCache(): void
    {
        Cache::forget('categories.all');
        Cache::forget('categories.menu');
        Cache::forget('categories.parents');
        foreach (Category::pluck('id') as $id) {
            Cache::forget("categories.children.{$id}");
        }
    }
}
