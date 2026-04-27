<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AttributeController extends Controller
{
    public function index(Request $request)
    {
        $perPage = in_array((int) $request->input('per_page'), [20, 50, 100], true) ? (int) $request->input('per_page') : 20;
        $attributes = ProductAttribute::with('values')->orderBy('name')->paginate($perPage)->withQueryString();
        return view('admin.attributes.index', compact('attributes'));
    }

    public function create()
    {
        return view('admin.attributes.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:product_attributes,slug'],
            'display_style' => ['required', 'string', 'in:circle,rounded'],
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        ProductAttribute::create($validated);

        return redirect()
            ->route('admin.attributes.index')
            ->with('success', 'Attribute created successfully.');
    }

    public function edit(ProductAttribute $attribute)
    {
        $attribute->load('values');
        return view('admin.attributes.edit', compact('attribute'));
    }

    public function update(Request $request, ProductAttribute $attribute)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:product_attributes,slug,' . $attribute->id],
            'display_style' => ['required', 'string', 'in:circle,rounded'],
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $attribute->update($validated);

        return redirect()
            ->route('admin.attributes.index')
            ->with('success', 'Attribute updated successfully.');
    }

    public function destroy(ProductAttribute $attribute)
    {
        $attribute->delete();

        return redirect()
            ->route('admin.attributes.index')
            ->with('success', 'Attribute deleted successfully.');
    }

    // Attribute Values
    public function storeValue(Request $request, ProductAttribute $attribute)
    {
        $validated = $request->validate([
            'value' => ['required', 'string', 'max:255'],
            'color_code' => ['nullable', 'string', 'max:7'],
            'image' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $attribute->values()->create($this->normalizeAttributeValueData($validated));

        return redirect()
            ->route('admin.attributes.edit', $attribute)
            ->with('success', 'Value added successfully.');
    }

    public function updateValue(Request $request, ProductAttributeValue $value)
    {
        $validated = $request->validate([
            'value' => ['required', 'string', 'max:255'],
            'color_code' => ['nullable', 'string', 'max:7'],
            'image' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $value->update($this->normalizeAttributeValueData($validated));

        return redirect()
            ->route('admin.attributes.edit', $value->attribute)
            ->with('success', 'Value updated successfully.');
    }

    public function destroyValue(ProductAttributeValue $value)
    {
        $attribute = $value->attribute;
        $value->delete();

        return redirect()
            ->route('admin.attributes.edit', $attribute)
            ->with('success', 'Value deleted successfully.');
    }

    private function normalizeAttributeValueData(array $data): array
    {
        $data['value'] = trim((string) ($data['value'] ?? ''));

        if (array_key_exists('color_code', $data)) {
            $colorCode = trim((string) ($data['color_code'] ?? ''));
            $data['color_code'] = $colorCode !== '' ? strtoupper($colorCode) : null;
        }

        if (array_key_exists('image', $data)) {
            $image = trim((string) ($data['image'] ?? ''));
            $data['image'] = $image !== '' ? $image : null;
        }

        return $data;
    }
}
