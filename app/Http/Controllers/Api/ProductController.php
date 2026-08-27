<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ProductVariantResource;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ProductController extends Controller
{
    public function __construct(
        protected ProductService $productService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = $this->perPage();
        $sortBy = (string) $request->query('sort_by', 'created_at');
        $sortOrder = strtolower((string) $request->query('sort_order', 'desc')) === 'asc' ? 'asc' : 'desc';
        $search = trim((string) $request->query('search', ''));
        $categoryId = $request->query('category_id');
        $hasFeaturedFilter = $request->has('is_featured');
        $isFeatured = $request->boolean('is_featured');
        $hasOnSaleFilter = $request->has('is_on_sale');
        $isOnSale = $request->boolean('is_on_sale');
        $inStockOnly = $request->has('in_stock') && $request->boolean('in_stock');
        $minPrice = $request->query('min_price');
        $maxPrice = $request->query('max_price');
        $page = max(1, (int) $request->query('page', 1));

        $cacheVersion = (int) Cache::get('products.v2.version', 1);
        $cachePayload = [
            'search' => $search,
            'category_id' => $categoryId !== null && $categoryId !== '' ? (int) $categoryId : null,
            'is_featured' => $hasFeaturedFilter ? $isFeatured : null,
            'is_on_sale' => $hasOnSaleFilter ? $isOnSale : null,
            'in_stock' => $inStockOnly,
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
            'sort_by' => $sortBy,
            'sort_order' => $sortOrder,
            'per_page' => $perPage,
            'page' => $page,
        ];

        $cacheKey = 'api.products.index.v'.$cacheVersion.'.'.md5((string) json_encode($cachePayload));

        $responsePayload = Cache::remember($cacheKey, 120, function () use (
            $perPage,
            $sortBy,
            $sortOrder,
            $search,
            $categoryId,
            $hasFeaturedFilter,
            $isFeatured,
            $hasOnSaleFilter,
            $isOnSale,
            $inStockOnly,
            $minPrice,
            $maxPrice,
            $page
        ) {
            $query = Product::query()
                ->active()
                ->with([
                    'category',
                    'images',
                    'variants' => function ($variantQuery) {
                        $variantQuery
                            ->where('is_active', true)
                            ->select([
                                'id',
                                'product_id',
                                'sku',
                                'price_adjustment',
                                'purchase_price',
                                'regular_price',
                                'discounted_price',
                                'stock_quantity',
                                'is_active',
                                'image',
                            ]);
                    },
                ])
                ->withCount('variants');

            if ($search !== '') {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('short_description', 'like', "%{$search}%");
                });
            }

            if ($categoryId !== null && $categoryId !== '') {
                $categoryIds = [];
                if (is_array($categoryId)) {
                    $categoryIds = array_map('intval', $categoryId);
                } elseif (is_string($categoryId) && str_contains($categoryId, ',')) {
                    $categoryIds = array_map('intval', explode(',', $categoryId));
                } else {
                    $categoryIds = [(int) $categoryId];
                }

                $query->where(function ($q) use ($categoryIds) {
                    $q->whereIn('category_id', $categoryIds)
                        ->orWhereHas('categories', function ($q2) use ($categoryIds) {
                            $q2->whereIn('categories.id', $categoryIds);
                        });
                });
            }

            if ($hasFeaturedFilter) {
                $query->where('is_featured', $isFeatured);
            }

            if ($hasOnSaleFilter && $isOnSale) {
                $query->where(function ($saleQuery) {
                    $saleQuery
                        ->where(function ($baseSaleQuery) {
                            $baseSaleQuery
                                ->whereNotNull('sale_price')
                                ->whereColumn('sale_price', '<', 'regular_price');
                        })
                        ->orWhereHas('variants', function ($variantSaleQuery) {
                            $variantSaleQuery
                                ->where('is_active', true)
                                ->whereNotNull('discounted_price')
                                ->whereColumn('discounted_price', '<', 'regular_price');
                        });
                });
            }

            if ($inStockOnly) {
                $query->inStock();
            }

            if ($minPrice !== null && $minPrice !== '' && is_numeric((string) $minPrice)) {
                $query->whereRaw('COALESCE(sale_price, regular_price) >= ?', [(float) $minPrice]);
            }

            if ($maxPrice !== null && $maxPrice !== '' && is_numeric((string) $maxPrice)) {
                $query->whereRaw('COALESCE(sale_price, regular_price) <= ?', [(float) $maxPrice]);
            }

            if ($sortBy === 'price') {
                $query->orderByRaw('COALESCE(sale_price, regular_price) '.strtoupper($sortOrder));
            } elseif (in_array($sortBy, ['name', 'created_at', 'sales_count'], true)) {
                $query->orderBy($sortBy, $sortOrder);
            } else {
                $query->orderByDesc('created_at');
            }

            $products = $query->paginate($perPage, ['*'], 'page', $page);

            return ProductResource::collection($products)->response()->getData(true);
        });

        return $this->successResponse($responsePayload);
    }

    public function show(int $id): JsonResponse
    {
        $product = $this->productService->getProductWithDetails($id);

        return $this->successResponse(new ProductResource($product));
    }

    public function showBySlug(string $slug): JsonResponse
    {
        $product = $this->productService->getProductBySlug($slug);

        if (! $product) {
            return $this->errorResponse('Product not found', 404);
        }

        return $this->successResponse(new ProductResource($product));
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->productService->createProduct($request->validated());

        return $this->createdResponse(new ProductResource($product), 'Product created successfully');
    }

    public function update(UpdateProductRequest $request, int $id): JsonResponse
    {
        $product = $this->productService->updateProduct($id, $request->validated());

        return $this->successResponse(new ProductResource($product), 'Product updated successfully');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        if (! $request->user()->isAdmin()) {
            return $this->errorResponse('Unauthorized', 403);
        }

        $this->productService->deleteProduct($id);

        return $this->successResponse(null, 'Product deleted successfully');
    }

    public function featured(): JsonResponse
    {
        $products = $this->productService->getFeaturedProducts();

        return $this->successResponse(ProductResource::collection($products));
    }

    public function newProducts(): JsonResponse
    {
        $products = $this->productService->getNewProducts();

        return $this->successResponse(ProductResource::collection($products));
    }

    public function bestsellers(): JsonResponse
    {
        $products = $this->productService->getBestsellers();

        return $this->successResponse(ProductResource::collection($products));
    }

    public function byCategory(Request $request, int $categoryId): JsonResponse
    {
        $products = $this->productService->getProductsByCategory($categoryId, $this->perPage());

        return $this->successResponse(ProductResource::collection($products)->response()->getData(true));
    }

    public function search(Request $request): JsonResponse
    {
        $request->validate(['q' => 'required|string|min:2']);

        $products = $this->productService->searchProducts($request->get('q'));

        return $this->successResponse(ProductResource::collection($products));
    }

    public function variants(int $id): JsonResponse
    {
        $product = $this->productService->getProductWithDetails($id);

        if (! $product) {
            return $this->errorResponse('Product not found', 404);
        }

        return $this->successResponse(ProductVariantResource::collection($product->variants));
    }

    public function bulkAction(Request $request): JsonResponse
    {
        if (! $request->user()->isAdmin()) {
            return $this->errorResponse('Unauthorized', 403);
        }

        $request->validate([
            'product_ids' => ['required', 'array', 'min:1'],
            'product_ids.*' => ['integer', 'exists:products,id'],
            'action' => ['required', 'string', 'in:activate,deactivate,delete,update_price'],
            'value' => ['nullable'],
        ]);

        $productIds = $request->product_ids;
        $action = $request->action;
        $count = count($productIds);

        switch ($action) {
            case 'activate':
                Product::whereIn('id', $productIds)->update(['is_active' => true]);
                $message = "{$count} products activated.";
                break;

            case 'deactivate':
                Product::whereIn('id', $productIds)->update(['is_active' => false]);
                $message = "{$count} products deactivated.";
                break;

            case 'delete':
                Product::whereIn('id', $productIds)->delete();
                $message = "{$count} products deleted.";
                break;

            case 'update_price':
                $request->validate([
                    'value' => ['required', 'array'],
                    'value.type' => ['required', 'in:fixed,percentage'],
                    'value.amount' => ['required', 'numeric'],
                ]);

                $type = $request->value['type'];
                $amount = $request->value['amount'];

                $products = Product::whereIn('id', $productIds)->get();
                foreach ($products as $product) {
                    /** @var Product $product */
                    $newPrice = $type === 'percentage'
                        ? $product->regular_price * (1 + $amount / 100)
                        : $product->regular_price + $amount;

                    $product->update(['regular_price' => max(0, round($newPrice, 2))]);
                }
                $message = "{$count} products price updated.";
                break;

            default:
                return $this->errorResponse('Invalid action.', 400);
        }

        $this->productService->clearProductCache();

        return $this->successResponse(null, $message);
    }
}
