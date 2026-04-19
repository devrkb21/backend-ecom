<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function __construct(
        protected ProductService $frontendProductService
    ) {}

    public function index(Request $request)
    {
        $query = Product::with([
            'category',
            'images',
            'variants' => function ($variantQuery) {
                $variantQuery
                    ->where('is_active', true)
                    ->select(['id', 'product_id', 'regular_price', 'discounted_price', 'stock_quantity', 'is_active']);
            },
        ])->withCount('variants');

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
        $stockEnabled = Product::isStockEnabled();
        return view('admin.products.create', compact('categories', 'attributes', 'stockEnabled'));
    }

    public function store(StoreProductRequest $request)
    {
        $data = $this->prepareProductData($request->validated(), $request);

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

            if ($request->boolean('is_variable') || $product->variants()->exists()) {
                $this->syncProductBaseStockFromVariants($product);
            }
        });

        $this->clearFrontendProductCache();

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
        $stockEnabled = Product::isStockEnabled();
        return view('admin.products.edit', compact('product', 'categories', 'attributes', 'stockEnabled'));
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $data = $this->prepareProductData($request->validated(), $request, $product);

        DB::transaction(function () use ($data, $request, $product) {
            $product->update($data);

            // Handle primary image removal
            if ($request->boolean('remove_image')) {
                $primaryImage = $product->images()->where('is_primary', true)->first();
                if ($primaryImage instanceof ProductImage) {
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
                if ($existingPrimary instanceof ProductImage) {
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

            if ($request->boolean('is_variable') || $product->variants()->exists()) {
                $this->syncProductBaseStockFromVariants($product);
            }
        });

        $this->clearFrontendProductCache();

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
        $this->clearFrontendProductCache();

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Product deleted successfully.');
    }

    // Variant management
    public function storeVariant(Request $request, Product $product)
    {
        $stockEnabled = Product::isStockEnabled();

        $request->validate([
            'attribute_groups' => ['nullable', 'array'],
            'attribute_groups.*' => ['array'],
            'attribute_groups.*.*' => ['integer', 'exists:product_attribute_values,id'],
            'attribute_value_ids' => ['nullable', 'array'],
            'attribute_value_ids.*' => ['integer', 'exists:product_attribute_values,id'],
            'variant_price_adjustment' => ['nullable', 'numeric'],
            'variant_stock_quantity' => $stockEnabled
                ? ['required', 'integer', 'min:0']
                : ['nullable', 'integer', 'min:0'],
        ]);

        $attributeGroups = $this->normalizeAttributeGroups($request->input('attribute_groups', []));

        // Backward compatibility for old single-list payload.
        if (empty($attributeGroups)) {
            $attributeGroups = $this->expandLegacyValueSelectionToGroups(
                $request->input('attribute_value_ids', [])
            );
        }

        if (empty($attributeGroups)) {
            return redirect()
                ->route('admin.products.edit', $product)
                ->with('error', 'Please select at least one attribute value.');
        }

        $priceAdjustment = (float) $request->input('variant_price_adjustment', 0);
        $stockQuantity = $stockEnabled
            ? (int) $request->input('variant_stock_quantity', 0)
            : 0;

        try {
            [$created, $skipped] = $this->createVariantsFromAttributeGroups(
                $product,
                $attributeGroups,
                $priceAdjustment,
                $stockQuantity,
                (string) $product->sku
            );

            $this->syncProductBaseStockFromVariants($product);
            $this->clearFrontendProductCache();
        } catch (\Throwable $exception) {
            return redirect()
                ->route('admin.products.edit', $product)
                ->with('error', $exception->getMessage());
        }

        if ($created > 0) {
            $message = "{$created} variant combination(s) added successfully.";
            if ($skipped > 0) {
                $message .= " {$skipped} existing combination(s) skipped.";
            }
            return redirect()
                ->route('admin.products.edit', $product)
                ->with('success', $message);
        }

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('error', 'No variants were created. Selected combinations may already exist.');
    }

    public function destroyVariant(Product $product, ProductVariant $variant)
    {
        $variant->delete();
        $this->syncProductBaseStockFromVariants($product);
        $this->clearFrontendProductCache();

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('success', 'Variant deleted successfully.');
    }

    public function bulkUpdateVariants(Request $request, Product $product)
    {
        $stockEnabled = Product::isStockEnabled();
        $variantIds = $request->input('variant_ids', []);

        if (empty($variantIds)) {
            return redirect()
                ->route('admin.products.edit', $product)
                ->with('error', 'No variants selected.');
        }

        // Handle bulk delete
        if ($request->boolean('bulk_delete')) {
            $deleted = $product->variants()->whereIn('id', $variantIds)->delete();
            $this->syncProductBaseStockFromVariants($product);
            $this->clearFrontendProductCache();
            return redirect()
                ->route('admin.products.edit', $product)
                ->with('success', "{$deleted} variant(s) deleted successfully.");
        }

        // Build update data
        $updateData = [];

        if ($request->filled('bulk_price_adjustment')) {
            $updateData['price_adjustment'] = $request->input('bulk_price_adjustment');
        }

        if ($stockEnabled && $request->filled('bulk_stock_quantity')) {
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
        if ($stockEnabled && $request->filled('bulk_add_stock')) {
            $addStock = (int) $request->input('bulk_add_stock');
            if ($addStock > 0) {
                $product->variants()->whereIn('id', $variantIds)->increment('stock_quantity', $addStock);
                $updated = count($variantIds);
            }
        }

        $this->syncProductBaseStockFromVariants($product);
        $this->clearFrontendProductCache();

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
        $stockEnabled = Product::isStockEnabled();

        $request->validate([
            'sku' => 'nullable|string|max:100',
            'price_adjustment' => 'nullable|numeric',
            'stock_quantity' => $stockEnabled
                ? 'required|integer|min:0'
                : 'nullable|integer|min:0',
            'is_active' => 'nullable',
            'variant_image_path' => 'nullable|string',
        ]);

        $data = [
            'sku' => $request->input('sku', $variant->sku),
            'price_adjustment' => $request->input('price_adjustment', $variant->price_adjustment),
            'is_active' => $request->has('is_active'),
        ];

        if ($stockEnabled) {
            $data['stock_quantity'] = $request->input('stock_quantity', $variant->stock_quantity);
        }

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
        $this->syncProductBaseStockFromVariants($product);
        $this->clearFrontendProductCache();

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('success', 'Variant updated successfully.');
    }

    public function updateVariantMatrix(Request $request, Product $product)
    {
        $stockEnabled = Product::isStockEnabled();

        $request->validate([
            'default_variant_id' => ['nullable', 'integer', 'min:1'],
            'variants' => ['required', 'array', 'min:1'],
            'variants.*.id' => ['required', 'integer', 'distinct', 'exists:product_variants,id'],
            'variants.*.sku' => ['required', 'string', 'max:100'],
            'variants.*.purchase_price' => ['required', 'numeric', 'min:0'],
            'variants.*.regular_price' => ['required', 'numeric', 'min:0'],
            'variants.*.discounted_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.stock_quantity' => $stockEnabled
                ? ['required', 'integer', 'min:0']
                : ['nullable', 'integer', 'min:0'],
            'variants.*.is_active' => ['nullable', 'boolean'],
        ]);

        $rows = collect($request->input('variants', []))->map(function (array $row) use ($stockEnabled) {
            $stockQuantity = array_key_exists('stock_quantity', $row)
                ? max(0, (int) ($row['stock_quantity'] ?? 0))
                : null;

            $regularPrice = round((float) ($row['regular_price'] ?? 0), 2);

            $discountedPriceInput = $row['discounted_price'] ?? null;
            $discountedPrice = ($discountedPriceInput === null || $discountedPriceInput === '')
                ? null
                : round((float) $discountedPriceInput, 2);

            return [
                'id' => (int) ($row['id'] ?? 0),
                'sku' => trim((string) ($row['sku'] ?? '')),
                'purchase_price' => round((float) ($row['purchase_price'] ?? 0), 2),
                'regular_price' => $regularPrice,
                'discounted_price' => $discountedPrice,
                'stock_quantity' => $stockEnabled ? ($stockQuantity ?? 0) : $stockQuantity,
                'is_active' => (bool) ($row['is_active'] ?? false),
            ];
        })->values();

        if ($rows->contains(fn (array $row) => $row['id'] <= 0 || $row['sku'] === '')) {
            return redirect()
                ->route('admin.products.edit', $product)
                ->with('error', 'Each variant row must include a valid SKU.');
        }

        $normalizedSkus = $rows
            ->pluck('sku')
            ->map(fn (string $sku) => strtolower($sku))
            ->all();

        if (count($normalizedSkus) !== count(array_unique($normalizedSkus))) {
            return redirect()
                ->route('admin.products.edit', $product)
                ->with('error', 'Duplicate SKU found in the submitted rows.');
        }

        if ($rows->contains(fn (array $row) => $row['discounted_price'] !== null && $row['discounted_price'] > $row['regular_price'])) {
            return redirect()
                ->route('admin.products.edit', $product)
                ->with('error', 'Discounted price cannot be greater than regular price.');
        }

        $requestedDefaultVariantIdInput = $request->input('default_variant_id');
        $requestedDefaultVariantId = ($requestedDefaultVariantIdInput === null || $requestedDefaultVariantIdInput === '')
            ? null
            : (int) $requestedDefaultVariantIdInput;

        if ($requestedDefaultVariantId !== null) {
            $defaultRow = $rows->first(fn (array $row) => $row['id'] === $requestedDefaultVariantId);

            if (!$defaultRow) {
                return redirect()
                    ->route('admin.products.edit', $product)
                    ->with('error', 'Selected base variant does not belong to this product.');
            }

            if (!$defaultRow['is_active']) {
                return redirect()
                    ->route('admin.products.edit', $product)
                    ->with('error', 'Base variant must be active. Please enable the selected variant first.');
            }
        }

        $variantIds = $rows->pluck('id')->all();

        $variants = $product->variants()
            ->whereIn('id', $variantIds)
            ->get()
            ->keyBy('id');

        if ($variants->count() !== count($variantIds)) {
            return redirect()
                ->route('admin.products.edit', $product)
                ->with('error', 'One or more variants do not belong to this product.');
        }

        $basePriceForAdjustment = round((float) ($product->sale_price ?? $product->regular_price), 2);

        try {
            DB::transaction(function () use ($rows, $variants, $basePriceForAdjustment, $stockEnabled, $product, $requestedDefaultVariantId) {
                foreach ($rows as $row) {
                    $variantId = (int) $row['id'];
                    $sku = $row['sku'];

                    $skuExists = ProductVariant::query()
                        ->where('sku', $sku)
                        ->where('id', '!=', $variantId)
                        ->exists();

                    if ($skuExists) {
                        throw new \RuntimeException("SKU already exists: {$sku}");
                    }

                    /** @var ProductVariant $variant */
                    $variant = $variants->get($variantId);
                    $effectiveDiscountedPrice = $row['discounted_price'] ?? $row['regular_price'];
                    $priceAdjustment = round((float) $effectiveDiscountedPrice - $basePriceForAdjustment, 2);

                    $updateData = [
                        'sku' => $sku,
                        'purchase_price' => $row['purchase_price'],
                        'regular_price' => $row['regular_price'],
                        'discounted_price' => $row['discounted_price'],
                        'price_adjustment' => $priceAdjustment,
                        'is_active' => $row['is_active'],
                    ];

                    if ($stockEnabled && $row['stock_quantity'] !== null) {
                        $updateData['stock_quantity'] = $row['stock_quantity'];
                    }

                    $variant->update($updateData);
                }

                $this->syncProductDefaultVariantSelection($product, $requestedDefaultVariantId);
            });

            $this->syncProductBaseStockFromVariants($product);
            $this->clearFrontendProductCache();
        } catch (\Throwable $exception) {
            return redirect()
                ->route('admin.products.edit', $product)
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('success', 'Variant matrix updated successfully.');
    }

    public function generateVariants(Request $request, Product $product)
    {
        $isJsonRequest = $request->expectsJson() || $request->ajax();
        $stockEnabled = Product::isStockEnabled();

        $request->validate([
            'attribute_groups' => ['nullable', 'array'],
            'attribute_groups.*' => ['array'],
            'attribute_groups.*.*' => ['integer', 'exists:product_attribute_values,id'],
            'attribute_values' => ['nullable', 'array'],
            'attribute_values.*' => ['integer', 'exists:product_attribute_values,id'],
            'default_price_adjustment' => ['nullable', 'numeric'],
            'default_stock' => $stockEnabled
                ? ['required', 'integer', 'min:0']
                : ['nullable', 'integer', 'min:0'],
            'sku_prefix' => ['nullable', 'string', 'max:100'],
        ]);

        $attributeGroups = $this->normalizeAttributeGroups($request->input('attribute_groups', []));

        // Backward compatibility for old single-list payload.
        if (empty($attributeGroups)) {
            $attributeGroups = $this->expandLegacyValueSelectionToGroups(
                $request->input('attribute_values', [])
            );
        }

        if (empty($attributeGroups)) {
            if ($isJsonRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please select at least one attribute value.',
                ], 422);
            }

            return redirect()
                ->route('admin.products.edit', $product)
                ->with('error', 'Please select at least one attribute value.');
        }

        $defaultPriceAdjustment = (float) $request->input('default_price_adjustment', 0);
        $defaultStock = $stockEnabled
            ? (int) $request->input('default_stock', 0)
            : 0;
        $skuPrefix = (string) $request->input('sku_prefix', $product->sku);

        try {
            $syncSummary = $this->syncVariantsFromAttributeGroups(
                $product,
                $attributeGroups,
                $defaultPriceAdjustment,
                $defaultStock,
                $skuPrefix
            );

            $this->syncProductBaseStockFromVariants($product);
            $this->clearFrontendProductCache();
        } catch (\Throwable $exception) {
            if ($isJsonRequest) {
                return response()->json([
                    'success' => false,
                    'message' => $exception->getMessage(),
                ], 422);
            }

            return redirect()
                ->route('admin.products.edit', $product)
                ->with('error', $exception->getMessage());
        }

        $created = (int) ($syncSummary['created'] ?? 0);
        $updated = (int) ($syncSummary['updated'] ?? 0);
        $deleted = (int) ($syncSummary['deleted'] ?? 0);
        $unchanged = (int) ($syncSummary['unchanged'] ?? 0);
        $changedCount = $created + $updated + $deleted;

        $viewData = $this->buildVariantMatrixViewData($product);

        if ($changedCount > 0) {
            $messageParts = [];
            if ($created > 0) {
                $messageParts[] = "{$created} created";
            }
            if ($updated > 0) {
                $messageParts[] = "{$updated} updated";
            }
            if ($deleted > 0) {
                $messageParts[] = "{$deleted} removed";
            }

            $message = 'Variants synced: ' . implode(', ', $messageParts) . '.';
            if ($unchanged > 0) {
                $message .= " {$unchanged} already matched.";
            }

            if ($isJsonRequest) {
                return response()->json([
                    'success' => true,
                    'created' => $created,
                    'updated' => $updated,
                    'deleted' => $deleted,
                    'unchanged' => $unchanged,
                    'changed_count' => $changedCount,
                    'message' => $message,
                    'variant_count' => $viewData['product']->variants->count(),
                    'matrix_html' => view('admin.products.partials.variant-matrix', $viewData)->render(),
                ]);
            }

            return redirect()
                ->route('admin.products.edit', $product)
                ->with('success', $message);
        }

        $message = 'No changes needed. Variants already match selected combinations.';

        if ($isJsonRequest) {
            return response()->json([
                'success' => true,
                'created' => 0,
                'updated' => 0,
                'deleted' => 0,
                'unchanged' => $unchanged,
                'changed_count' => 0,
                'message' => $message,
                'variant_count' => $viewData['product']->variants->count(),
                'matrix_html' => view('admin.products.partials.variant-matrix', $viewData)->render(),
            ]);
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
        $this->clearFrontendProductCache();

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('success', 'Primary image updated.');
    }

    public function destroyImage(Product $product, ProductImage $image)
    {
        Storage::disk('public')->delete($image->image);
        $image->delete();
        $this->clearFrontendProductCache();

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('success', 'Image deleted successfully.');
    }

    private function prepareProductData(array $data, Request $request, ?Product $product = null): array
    {
        unset($data['dynamic_discount_tiers'], $data['free_delivery']);

        $isVariableProduct = $request->boolean('is_variable') || ($product?->isVariableProduct() ?? false);

        if ($isVariableProduct) {
            if (!array_key_exists('regular_price', $data) || $data['regular_price'] === null || $data['regular_price'] === '') {
                $data['regular_price'] = (float) ($product?->regular_price ?? 0.01);
            } else {
                $data['regular_price'] = max(0.01, round((float) $data['regular_price'], 2));
            }

            if (array_key_exists('sale_price', $data) && $data['sale_price'] !== null && $data['sale_price'] !== '') {
                $data['sale_price'] = round(max(0, (float) $data['sale_price']), 2);
            } else {
                $data['sale_price'] = null;
            }
        }

        if ($isVariableProduct) {
            $data['stock_quantity'] = (int) ($product?->variants()->sum('stock_quantity') ?? 0);
        } elseif (!array_key_exists('stock_quantity', $data) || $data['stock_quantity'] === null || $data['stock_quantity'] === '') {
            $data['stock_quantity'] = (int) ($product?->stock_quantity ?? 0);
        } else {
            $data['stock_quantity'] = max(0, (int) $data['stock_quantity']);
        }

        $metaData = is_array($product?->meta_data) ? $product->meta_data : [];
        $metaData = is_array($metaData) ? $metaData : [];

        $normalizedTiers = $this->normalizeDynamicDiscountTiers($request->input('dynamic_discount_tiers', []));
        if (!empty($normalizedTiers)) {
            $metaData['quantity_pricing'] = $normalizedTiers;
        } else {
            unset($metaData['quantity_pricing']);
        }

        if ($request->boolean('free_delivery')) {
            $metaData['free_delivery'] = true;
        } else {
            unset($metaData['free_delivery']);
        }

        $metaData['is_variable'] = $isVariableProduct;

        $data['meta_data'] = empty($metaData) ? null : $metaData;

        return $data;
    }

    private function syncProductBaseStockFromVariants(Product $product): void
    {
        $hasVariants = $product->variants()->exists();

        if (!$hasVariants && !$product->isVariableProduct()) {
            $this->syncProductDefaultVariantSelection($product);
            return;
        }

        $totalStock = (int) $product->variants()->sum('stock_quantity');

        if ((int) $product->stock_quantity !== $totalStock) {
            $product->forceFill(['stock_quantity' => $totalStock])->saveQuietly();
        }

        $this->syncProductDefaultVariantSelection($product);
    }

    private function syncProductDefaultVariantSelection(Product $product, ?int $requestedDefaultVariantId = null): void
    {
        $metaData = is_array($product->meta_data) ? $product->meta_data : [];

        $currentDefaultIdRaw = $metaData['default_variant_id'] ?? null;
        $currentDefaultId = is_numeric($currentDefaultIdRaw) ? (int) $currentDefaultIdRaw : null;

        $targetDefaultId = null;

        if ($requestedDefaultVariantId !== null && $requestedDefaultVariantId > 0) {
            $targetDefaultId = $requestedDefaultVariantId;
        } elseif ($currentDefaultId !== null && $currentDefaultId > 0) {
            $targetDefaultId = $currentDefaultId;
        }

        if ($targetDefaultId !== null) {
            $isValidActiveVariant = $product->variants()
                ->where('id', $targetDefaultId)
                ->where('is_active', true)
                ->exists();

            if ($isValidActiveVariant) {
                $metaData['default_variant_id'] = $targetDefaultId;
            } else {
                unset($metaData['default_variant_id']);
            }
        } else {
            unset($metaData['default_variant_id']);
        }

        $normalizedMetaData = empty($metaData) ? null : $metaData;

        if ($product->meta_data === $normalizedMetaData) {
            return;
        }

        $product->forceFill(['meta_data' => $normalizedMetaData])->saveQuietly();
    }

    private function clearFrontendProductCache(): void
    {
        $this->frontendProductService->clearProductCache();
    }

    private function normalizeDynamicDiscountTiers(mixed $tiers): array
    {
        if (!is_array($tiers)) {
            return [];
        }

        $normalized = [];

        foreach ($tiers as $tier) {
            if (!is_array($tier)) {
                continue;
            }

            $minQuantity = (int) ($tier['min_quantity'] ?? 0);
            $unitPriceInput = $tier['unit_price'] ?? null;

            if ($minQuantity < 1 || $unitPriceInput === null || $unitPriceInput === '') {
                continue;
            }

            $unitPrice = round((float) $unitPriceInput, 2);
            if ($unitPrice <= 0) {
                continue;
            }

            // Last value wins for duplicate min quantities.
            $normalized[$minQuantity] = [
                'min_quantity' => $minQuantity,
                'unit_price' => $unitPrice,
            ];
        }

        ksort($normalized);

        return array_values($normalized);
    }

    private function buildVariantMatrixViewData(Product $product): array
    {
        $productForView = $product->fresh(['variants.attributeValues.attribute'])
            ?: $product->load(['variants.attributeValues.attribute']);

        $stockEnabled = Product::isStockEnabled();

        $variantAttributes = $productForView->variants
            ->flatMap(fn(ProductVariant $variant) => $variant->attributeValues->map(fn($value) => $value->attribute))
            ->filter()
            ->unique('id')
            ->sortBy('id')
            ->values();

        return [
            'product' => $productForView,
            'variantAttributes' => $variantAttributes,
            'variantBasePurchasePrice' => (float) ($productForView->buy_price ?? 0),
            'variantBaseRegularPrice' => (float) $productForView->regular_price,
            'variantBaseDiscountPrice' => (float) ($productForView->sale_price ?? $productForView->regular_price),
            'stockEnabled' => $stockEnabled,
        ];
    }

    private function createVariantsFromAttributeGroups(
        Product $product,
        array $rawAttributeGroups,
        float $priceAdjustment,
        int $stockQuantity,
        ?string $skuPrefix = null
    ): array {
        $attributeGroups = $this->normalizeAttributeGroups($rawAttributeGroups);
        if (empty($attributeGroups)) {
            return [0, 0];
        }

        $allValueIds = array_values(array_unique(array_merge(...array_values($attributeGroups))));

        $values = ProductAttributeValue::query()
            ->select(['id', 'attribute_id'])
            ->whereIn('id', $allValueIds)
            ->get()
            ->keyBy('id');

        if ($values->count() !== count($allValueIds)) {
            throw new \InvalidArgumentException('One or more selected attribute values are invalid.');
        }

        foreach ($attributeGroups as $attributeId => $valueIds) {
            foreach ($valueIds as $valueId) {
                $value = $values->get($valueId);
                if (!$value || (int) $value->attribute_id !== (int) $attributeId) {
                    throw new \InvalidArgumentException('Selected values do not match the selected attributes.');
                }
            }
        }

        $existingSignatures = $product->variants()
            ->with('attributeValues:id')
            ->get()
            ->mapWithKeys(function (ProductVariant $variant) {
                $signature = $this->buildAttributeCombinationSignature($variant->attributeValues->pluck('id')->all());
                return [$signature => true];
            })
            ->all();

        $combinations = $this->generateAttributeCombinations(array_values($attributeGroups));

        $created = 0;
        $skipped = 0;

        foreach ($combinations as $combinationValueIds) {
            $signature = $this->buildAttributeCombinationSignature($combinationValueIds);

            if (isset($existingSignatures[$signature])) {
                $skipped++;
                continue;
            }

            $variant = $product->variants()->create([
                'sku' => $this->generateUniqueVariantSku($product, $combinationValueIds, $skuPrefix),
                'price_adjustment' => $priceAdjustment,
                'stock_quantity' => max(0, $stockQuantity),
                'is_active' => true,
            ]);

            $variant->attributeValues()->sync($combinationValueIds);

            $existingSignatures[$signature] = true;
            $created++;
        }

        return [$created, $skipped];
    }

    private function syncVariantsFromAttributeGroups(
        Product $product,
        array $rawAttributeGroups,
        float $priceAdjustment,
        int $stockQuantity,
        ?string $skuPrefix = null
    ): array {
        $attributeGroups = $this->normalizeAttributeGroups($rawAttributeGroups);
        if (empty($attributeGroups)) {
            return [
                'created' => 0,
                'updated' => 0,
                'deleted' => 0,
                'unchanged' => 0,
            ];
        }

        $allValueIds = array_values(array_unique(array_merge(...array_values($attributeGroups))));

        $values = ProductAttributeValue::query()
            ->select(['id', 'attribute_id'])
            ->whereIn('id', $allValueIds)
            ->get()
            ->keyBy('id');

        if ($values->count() !== count($allValueIds)) {
            throw new \InvalidArgumentException('One or more selected attribute values are invalid.');
        }

        foreach ($attributeGroups as $attributeId => $valueIds) {
            foreach ($valueIds as $valueId) {
                $value = $values->get($valueId);
                if (!$value || (int) $value->attribute_id !== (int) $attributeId) {
                    throw new \InvalidArgumentException('Selected values do not match the selected attributes.');
                }
            }
        }

        $targetCombinations = [];
        foreach ($this->generateAttributeCombinations(array_values($attributeGroups)) as $combinationValueIds) {
            $normalizedValueIds = array_values(array_unique(array_map('intval', $combinationValueIds)));
            sort($normalizedValueIds);

            $signature = $this->buildAttributeCombinationSignature($normalizedValueIds);
            $targetCombinations[$signature] = $normalizedValueIds;
        }

        return DB::transaction(function () use ($product, $targetCombinations, $priceAdjustment, $stockQuantity, $skuPrefix) {
            $existingVariants = $product->variants()
                ->with('attributeValues:id')
                ->get();

            $existingBySignature = [];
            $existingValueMap = [];

            foreach ($existingVariants as $variant) {
                $valueIds = $variant->attributeValues
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                $signature = $this->buildAttributeCombinationSignature($valueIds);

                if (!isset($existingBySignature[$signature])) {
                    $existingBySignature[$signature] = [];
                }

                $existingBySignature[$signature][] = $variant;
                $existingValueMap[$variant->id] = $valueIds;
            }

            $created = 0;
            $updated = 0;
            $deleted = 0;
            $unchanged = 0;

            $missingCombinations = [];

            foreach ($targetCombinations as $signature => $targetValueIds) {
                if (!empty($existingBySignature[$signature])) {
                    array_shift($existingBySignature[$signature]);
                    $unchanged++;
                    continue;
                }

                $missingCombinations[$signature] = $targetValueIds;
            }

            $reusableVariants = [];
            foreach ($existingBySignature as $variantsForSignature) {
                foreach ($variantsForSignature as $variant) {
                    $reusableVariants[$variant->id] = $variant;
                }
            }

            $stillMissingCombinations = [];

            foreach ($missingCombinations as $signature => $targetValueIds) {
                $bestVariantId = null;
                $bestOverlap = -1;

                foreach ($reusableVariants as $variantId => $candidateVariant) {
                    $candidateValueIds = $existingValueMap[$variantId] ?? [];
                    $overlap = count(array_intersect($candidateValueIds, $targetValueIds));

                    if ($overlap > $bestOverlap) {
                        $bestOverlap = $overlap;
                        $bestVariantId = $variantId;
                    }
                }

                if ($bestVariantId === null) {
                    $stillMissingCombinations[$signature] = $targetValueIds;
                    continue;
                }

                $variantToUpdate = $reusableVariants[$bestVariantId];
                $variantToUpdate->attributeValues()->sync($targetValueIds);

                unset($reusableVariants[$bestVariantId], $existingValueMap[$bestVariantId]);
                $updated++;
            }

            foreach ($reusableVariants as $variantToDelete) {
                $variantToDelete->delete();
                $deleted++;
            }

            foreach ($stillMissingCombinations as $combinationValueIds) {
                $variant = $product->variants()->create([
                    'sku' => $this->generateUniqueVariantSku($product, $combinationValueIds, $skuPrefix),
                    'price_adjustment' => $priceAdjustment,
                    'stock_quantity' => max(0, $stockQuantity),
                    'is_active' => true,
                ]);

                $variant->attributeValues()->sync($combinationValueIds);
                $created++;
            }

            return [
                'created' => $created,
                'updated' => $updated,
                'deleted' => $deleted,
                'unchanged' => $unchanged,
            ];
        });
    }

    private function normalizeAttributeGroups(mixed $rawGroups): array
    {
        if (!is_array($rawGroups)) {
            return [];
        }

        $normalized = [];

        foreach ($rawGroups as $rawAttributeId => $rawValueIds) {
            $attributeId = (int) $rawAttributeId;
            if ($attributeId <= 0 || !is_array($rawValueIds)) {
                continue;
            }

            $valueIds = array_values(array_unique(array_filter(
                array_map('intval', $rawValueIds),
                fn (int $id) => $id > 0
            )));

            if (!empty($valueIds)) {
                sort($valueIds);
                $normalized[$attributeId] = $valueIds;
            }
        }

        ksort($normalized);

        return $normalized;
    }

    private function expandLegacyValueSelectionToGroups(mixed $rawValueIds): array
    {
        if (!is_array($rawValueIds)) {
            return [];
        }

        $valueIds = array_values(array_unique(array_filter(
            array_map('intval', $rawValueIds),
            fn (int $id) => $id > 0
        )));

        if (empty($valueIds)) {
            return [];
        }

        return ProductAttributeValue::query()
            ->select(['id', 'attribute_id'])
            ->whereIn('id', $valueIds)
            ->get()
            ->groupBy('attribute_id')
            ->map(function ($items) {
                return $items
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->sort()
                    ->values()
                    ->all();
            })
            ->filter(fn (array $ids) => !empty($ids))
            ->sortKeys()
            ->toArray();
    }

    private function generateAttributeCombinations(array $attributeValueSets): array
    {
        if (empty($attributeValueSets)) {
            return [];
        }

        $combinations = [[]];

        foreach ($attributeValueSets as $valueSet) {
            $next = [];

            foreach ($combinations as $combination) {
                foreach ($valueSet as $valueId) {
                    $candidate = $combination;
                    $candidate[] = (int) $valueId;
                    $next[] = $candidate;
                }
            }

            $combinations = $next;
        }

        return $combinations;
    }

    private function buildAttributeCombinationSignature(array $valueIds): string
    {
        $normalized = array_values(array_unique(array_map('intval', $valueIds)));
        sort($normalized);

        return implode('-', $normalized);
    }

    private function generateUniqueVariantSku(Product $product, array $combinationValueIds, ?string $skuPrefix = null): string
    {
        $prefix = strtoupper(trim((string) ($skuPrefix ?: $product->sku ?: ('VAR' . $product->id))));
        $prefix = preg_replace('/[^A-Z0-9\-]/', '', $prefix) ?: ('VAR' . $product->id);

        $seed = strtoupper(substr(md5($product->id . ':' . implode('-', $combinationValueIds)), 0, 6));
        $base = rtrim($prefix, '-') . '-' . $seed;

        $candidate = $base;
        $counter = 1;

        while (ProductVariant::query()->where('sku', $candidate)->exists()) {
            $candidate = $base . '-' . $counter;
            $counter++;
        }

        return $candidate;
    }
}
