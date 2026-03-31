<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\RelatedProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RelatedProductController extends Controller
{
    public function __construct(
        protected RelatedProductService $relatedService
    ) {}

    /**
     * Get related products for a product
     */
    public function index(Product $product, Request $request): JsonResponse
    {
        $limit = $request->get('limit', 8);

        $relatedProducts = $this->relatedService->getRelatedProducts($product, $limit);

        return response()->json([
            'success' => true,
            'data' => $relatedProducts->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'regular_price' => $p->regular_price,
                'sale_price' => $p->sale_price,
                'image' => $p->image,
                'category' => $p->category?->name,
            ]),
        ]);
    }

    /**
     * Get frequently bought together
     */
    public function frequentlyBoughtTogether(Product $product, Request $request): JsonResponse
    {
        $limit = $request->get('limit', 4);

        $products = $this->relatedService->getFrequentlyBoughtTogether($product, [], $limit);

        return response()->json([
            'success' => true,
            'data' => $products->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'regular_price' => $p->regular_price,
                'sale_price' => $p->sale_price,
                'image' => $p->image,
            ]),
        ]);
    }

    /**
     * Get cart recommendations
     */
    public function cartRecommendations(Request $request): JsonResponse
    {
        $productIds = $request->get('product_ids', []);
        $limit = $request->get('limit', 6);

        $recommendations = $this->relatedService->getCartRecommendations($productIds, $limit);

        return response()->json([
            'success' => true,
            'data' => $recommendations->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'regular_price' => $p->regular_price,
                'sale_price' => $p->sale_price,
                'image' => $p->image,
                'category' => $p->category?->name,
            ]),
        ]);
    }

    /**
     * Get upsell products
     */
    public function upsell(Product $product, Request $request): JsonResponse
    {
        $limit = $request->get('limit', 4);

        $products = $this->relatedService->getUpsellProducts($product, $limit);

        return response()->json([
            'success' => true,
            'data' => $products->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'regular_price' => $p->regular_price,
                'sale_price' => $p->sale_price,
                'image' => $p->image,
            ]),
        ]);
    }

    /**
     * Get cross-sell products
     */
    public function crossSell(Product $product, Request $request): JsonResponse
    {
        $limit = $request->get('limit', 4);

        $products = $this->relatedService->getCrossSellProducts($product, $limit);

        return response()->json([
            'success' => true,
            'data' => $products->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'regular_price' => $p->regular_price,
                'sale_price' => $p->sale_price,
                'image' => $p->image,
                'category' => $p->category?->name,
            ]),
        ]);
    }
}
