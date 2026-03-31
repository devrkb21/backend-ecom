<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['category', 'images']);

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Category filter
        if ($categoryId = $request->input('category')) {
            $query->where('category_id', $categoryId);
        }

        // Status filter
        if ($request->has('is_active') && $request->input('is_active') !== '') {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Flags filter
        if ($request->boolean('is_featured')) {
            $query->where('is_featured', true);
        }
        if ($request->boolean('is_new')) {
            $query->where('is_new', true);
        }
        if ($request->boolean('is_bestseller')) {
            $query->where('is_bestseller', true);
        }

        // Stock filter
        if ($request->input('stock') === 'low') {
            $query->where('stock_quantity', '>', 0)->where('stock_quantity', '<=', 10);
        } elseif ($request->input('stock') === 'out') {
            $query->where('stock_quantity', '<=', 0);
        }

        $products = $query->orderByDesc('created_at')->paginate(15)->withQueryString();
        $categories = Category::orderBy('name')->get();

        return view('admin.products.index', compact('products', 'categories'));
    }

    public function create()
    {
        $categories = Category::with('children')->whereNull('parent_id')->orderBy('name')->get();
        $attributes = ProductAttribute::with('values')->get();
        return view('admin.products.create', compact('categories', 'attributes'));
    }

    public function store(StoreProductRequest $request)
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $request) {
            $product = Product::create($data);

            // Handle primary image from media library
            if ($imagePath = $request->input('image_path')) {
                $product->images()->create([
                    'image' => $imagePath,
                    'sort_order' => 0,
                    'is_primary' => true,
                ]);
            }

            // Handle gallery images from media library
            if ($galleryPaths = $request->input('gallery_image_paths', [])) {
                $maxOrder = $product->images()->max('sort_order') ?? 0;
                foreach ($galleryPaths as $index => $path) {
                    $product->images()->create([
                        'image' => $path,
                        'sort_order' => $maxOrder + $index + 1,
                        'is_primary' => false,
                    ]);
                }
            }

            // Handle variants
            if ($request->has('variants')) {
                foreach ($request->input('variants', []) as $variantData) {
                    if (empty($variantData['sku'])) continue;
                    
                    $variant = $product->variants()->create([
                        'sku' => $variantData['sku'],
                        'price_adjustment' => $variantData['price_adjustment'] ?? 0,
                        'stock_quantity' => $variantData['stock_quantity'] ?? 0,
                        'is_active' => true,
                    ]);

                    if (!empty($variantData['attribute_values'])) {
                        $variant->attributeValues()->attach($variantData['attribute_values']);
                    }
                }
            }
        });

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Product created successfully.');
    }

    public function show(Product $product)
    {
        $product->load(['category', 'images', 'variants.attributeValues.attribute']);
        return view('admin.products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        $product->load(['images', 'variants.attributeValues.attribute']);
        $categories = Category::with('children')->whereNull('parent_id')->orderBy('name')->get();
        $attributes = ProductAttribute::with('values')->get();
        return view('admin.products.edit', compact('product', 'categories', 'attributes'));
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $request, $product) {
            $product->update($data);

            // Handle primary image removal
            if ($request->boolean('remove_image')) {
                $primaryImage = $product->images()->where('is_primary', true)->first();
                if ($primaryImage) {
                    // Don't delete from storage if it's from media library
                    if (!str_starts_with($primaryImage->image, 'media/')) {
                        Storage::disk('public')->delete($primaryImage->image);
                    }
                    $primaryImage->delete();
                }
            }

            // Handle primary image from media library
            if ($imagePath = $request->input('image_path')) {
                $existingPrimary = $product->images()->where('is_primary', true)->first();
                if ($existingPrimary) {
                    // Update existing primary
                    $existingPrimary->update(['image' => $imagePath]);
                } else {
                    // Create new primary
                    $product->images()->create([
                        'image' => $imagePath,
                        'sort_order' => 0,
                        'is_primary' => true,
                    ]);
                }
            }

            // Handle gallery images from media library
            if ($galleryPaths = $request->input('gallery_image_paths', [])) {
                $maxOrder = $product->images()->max('sort_order') ?? 0;
                foreach ($galleryPaths as $index => $path) {
                    $product->images()->create([
                        'image' => $path,
                        'sort_order' => $maxOrder + $index + 1,
                        'is_primary' => false,
                    ]);
                }
            }

            // Handle image deletions
            if ($deleteImages = $request->input('delete_images', [])) {
                $imagesToDelete = $product->images()->whereIn('id', $deleteImages)->get();
                foreach ($imagesToDelete as $img) {
                    // Don't delete from storage if it's from media library
                    if (!str_starts_with($img->image, 'media/')) {
                        Storage::disk('public')->delete($img->image);
                    }
                    $img->delete();
                }
            }
        });

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product)
    {
        // Delete all product images (only non-media-library ones from storage)
        foreach ($product->images as $img) {
            if (!str_starts_with($img->image, 'media/')) {
                Storage::disk('public')->delete($img->image);
            }
        }

        $product->delete();

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Product deleted successfully.');
    }

    // Variant management
    public function storeVariant(Request $request, Product $product)
    {
        // Multiple attribute values selection
        $attributeValueIds = $request->input('attribute_value_ids', []);
        
        if (empty($attributeValueIds)) {
            return redirect()
                ->route('admin.products.edit', $product)
                ->with('error', 'Please select at least one attribute value.');
        }

        $created = 0;
        $skipped = 0;
        $priceAdjustment = $request->input('variant_price_adjustment', 0);
        $stockQuantity = $request->input('variant_stock_quantity', 0);

        // Get existing variant attribute value IDs
        $existingValueIds = $product->variants()
            ->with('attributeValues')
            ->get()
            ->flatMap(function ($variant) {
                return $variant->attributeValues->pluck('id');
            })
            ->unique()
            ->toArray();

        foreach ($attributeValueIds as $valueId) {
            // Skip if variant with this attribute value already exists
            if (in_array((int)$valueId, $existingValueIds)) {
                $skipped++;
                continue;
            }

            try {
                // Generate unique SKU
                $baseSku = $product->sku . '-' . strtoupper(substr(md5($valueId . time() . $created), 0, 6));
                
                $variant = $product->variants()->create([
                    'sku' => $baseSku,
                    'price_adjustment' => $priceAdjustment,
                    'stock_quantity' => $stockQuantity,
                    'is_active' => true,
                ]);

                // Attach the single attribute value
                $variant->attributeValues()->attach([$valueId]);
                $created++;
            } catch (\Exception $e) {
                continue;
            }
        }

        if ($created > 0) {
            $message = "{$created} variant(s) added successfully.";
            if ($skipped > 0) {
                $message .= " {$skipped} already existing value(s) skipped.";
            }
            return redirect()
                ->route('admin.products.edit', $product)
                ->with('success', $message);
        }

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('error', 'No variants were created. Selected values may already exist.');
    }

    public function destroyVariant(Product $product, ProductVariant $variant)
    {
        $variant->delete();

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('success', 'Variant deleted successfully.');
    }

    public function bulkUpdateVariants(Request $request, Product $product)
    {
        $variantIds = $request->input('variant_ids', []);
        
        if (empty($variantIds)) {
            return redirect()
                ->route('admin.products.edit', $product)
                ->with('error', 'No variants selected.');
        }

        // Handle bulk delete
        if ($request->boolean('bulk_delete')) {
            $deleted = $product->variants()->whereIn('id', $variantIds)->delete();
            return redirect()
                ->route('admin.products.edit', $product)
                ->with('success', "{$deleted} variant(s) deleted successfully.");
        }

        // Build update data
        $updateData = [];
        
        if ($request->filled('bulk_price_adjustment')) {
            $updateData['price_adjustment'] = $request->input('bulk_price_adjustment');
        }
        
        if ($request->filled('bulk_stock_quantity')) {
            $updateData['stock_quantity'] = $request->input('bulk_stock_quantity');
        }
        
        if ($request->filled('bulk_is_active')) {
            $updateData['is_active'] = $request->input('bulk_is_active') === '1';
        }

        $updated = 0;
        
        // Update variants
        if (!empty($updateData)) {
            $updated = $product->variants()->whereIn('id', $variantIds)->update($updateData);
        }
        
        // Handle add stock (increment)
        if ($request->filled('bulk_add_stock')) {
            $addStock = (int) $request->input('bulk_add_stock');
            if ($addStock > 0) {
                $product->variants()->whereIn('id', $variantIds)->increment('stock_quantity', $addStock);
                $updated = count($variantIds);
            }
        }

        if ($updated > 0) {
            return redirect()
                ->route('admin.products.edit', $product)
                ->with('success', "{$updated} variant(s) updated successfully.");
        }

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('info', 'No changes were made.');
    }

    public function updateVariant(Request $request, Product $product, ProductVariant $variant)
    {
        $request->validate([
            'sku' => 'nullable|string|max:100',
            'price_adjustment' => 'nullable|numeric',
            'stock_quantity' => 'nullable|integer|min:0',
            'is_active' => 'nullable',
            'variant_image_path' => 'nullable|string',
        ]);

        $data = [
            'sku' => $request->input('sku', $variant->sku),
            'price_adjustment' => $request->input('price_adjustment', $variant->price_adjustment),
            'stock_quantity' => $request->input('stock_quantity', $variant->stock_quantity),
            'is_active' => $request->has('is_active'),
        ];

        // Handle image removal
        if ($request->boolean('remove_variant_image') && $variant->image) {
            // Only delete if not from media library
            if (!str_starts_with($variant->image, 'media/')) {
                Storage::disk('public')->delete($variant->image);
            }
            $data['image'] = null;
        }

        // Handle image from media library
        if ($imagePath = $request->input('variant_image_path')) {
            // Delete old image if not from media library
            if ($variant->image && !str_starts_with($variant->image, 'media/')) {
                Storage::disk('public')->delete($variant->image);
            }
            $data['image'] = $imagePath;
        }

        $variant->update($data);

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('success', 'Variant updated successfully.');
    }

    public function generateVariants(Request $request, Product $product)
    {
        $attributeValues = $request->input('attribute_values', []);
        $defaultPriceAdjustment = $request->input('default_price_adjustment', 0);
        $defaultStock = $request->input('default_stock', 0);
        $skuPrefix = $request->input('sku_prefix', $product->sku);

        if (empty($attributeValues)) {
            return redirect()
                ->route('admin.products.edit', $product)
                ->with('error', 'Please select at least one attribute value.');
        }

        $created = 0;
        $skipped = 0;

        // Get existing variant attribute value IDs
        $existingValueIds = $product->variants()
            ->with('attributeValues')
            ->get()
            ->flatMap(function ($variant) {
                return $variant->attributeValues->pluck('id');
            })
            ->unique()
            ->toArray();

        foreach ($attributeValues as $index => $valueId) {
            // Skip if variant with this attribute value already exists
            if (in_array((int)$valueId, $existingValueIds)) {
                $skipped++;
                continue;
            }

            // Generate unique SKU
            $sku = $skuPrefix . '-' . strtoupper(substr(md5($valueId . $index . time()), 0, 6));

            try {
                $variant = $product->variants()->create([
                    'sku' => $sku,
                    'price_adjustment' => $defaultPriceAdjustment,
                    'stock_quantity' => $defaultStock,
                    'is_active' => true,
                ]);

                // Attach single attribute value
                $variant->attributeValues()->attach([$valueId]);
                $created++;
            } catch (\Exception $e) {
                // Skip failed variants
                continue;
            }
        }

        $message = "{$created} variant(s) created successfully.";
        if ($skipped > 0) {
            $message .= " {$skipped} already existing value(s) skipped.";
        }

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('success', $message);
    }

    // Image management
    public function setPrimaryImage(Product $product, ProductImage $image)
    {
        // Remove primary from all images
        $product->images()->update(['is_primary' => false]);
        
        // Set this as primary
        $image->update(['is_primary' => true]);

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('success', 'Primary image updated.');
    }

    public function destroyImage(Product $product, ProductImage $image)
    {
        Storage::disk('public')->delete($image->image);
        $image->delete();

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('success', 'Image deleted successfully.');
    }
}
