<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ProductVariantResource;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(
        protected ProductService $productService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $products = $this->productService->getAllProducts($this->perPage());

        return $this->successResponse(ProductResource::collection($products)->response()->getData(true));
    }

    public function show(int $id): JsonResponse
    {
        $product = $this->productService->getProductWithDetails($id);

        return $this->successResponse(new ProductResource($product));
    }

    public function showBySlug(string $slug): JsonResponse
    {
        $product = $this->productService->getProductBySlug($slug);

        if (!$product) {
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
        if (!$request->user()->isAdmin()) {
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

        if (!$product) {
            return $this->errorResponse('Product not found', 404);
        }

        return $this->successResponse(ProductVariantResource::collection($product->variants));
    }

    public function bulkAction(Request $request): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
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
                \App\Models\Product::whereIn('id', $productIds)->update(['is_active' => true]);
                $message = "{$count} products activated.";
                break;

            case 'deactivate':
                \App\Models\Product::whereIn('id', $productIds)->update(['is_active' => false]);
                $message = "{$count} products deactivated.";
                break;

            case 'delete':
                \App\Models\Product::whereIn('id', $productIds)->delete();
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

                $products = \App\Models\Product::whereIn('id', $productIds)->get();
                foreach ($products as $product) {
                    /** @var \App\Models\Product $product */
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
