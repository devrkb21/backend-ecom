<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FlashSale;
use App\Services\FlashSaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FlashSaleController extends Controller
{
    public function __construct(
        protected FlashSaleService $flashSaleService
    ) {}

    /**
     * Get all active flash sales
     */
    public function index(): JsonResponse
    {
        $flashSales = $this->flashSaleService->getActiveFlashSales();

        return response()->json([
            'success' => true,
            'data' => $flashSales->map(fn($sale) => $this->formatFlashSale($sale)),
        ]);
    }

    /**
     * Get featured flash sale (for homepage)
     */
    public function featured(): JsonResponse
    {
        $flashSale = $this->flashSaleService->getFeaturedFlashSale();

        if (!$flashSale) {
            return response()->json([
                'success' => true,
                'data' => null,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatFlashSale($flashSale),
        ]);
    }

    /**
     * Get upcoming flash sales
     */
    public function upcoming(): JsonResponse
    {
        $flashSales = $this->flashSaleService->getUpcomingFlashSales();

        return response()->json([
            'success' => true,
            'data' => $flashSales->map(fn($sale) => [
                'id' => $sale->id,
                'name' => $sale->name,
                'slug' => $sale->slug,
                'description' => $sale->description,
                'banner_image' => $sale->banner_image,
                'starts_at' => $sale->starts_at->toIso8601String(),
                'time_until_start' => $sale->starts_at->diffForHumans(),
            ]),
        ]);
    }

    /**
     * Get flash sale details by slug
     */
    public function show(string $slug): JsonResponse
    {
        $flashSale = $this->flashSaleService->getFlashSaleBySlug($slug);

        if (!$flashSale) {
            return response()->json([
                'success' => false,
                'message' => 'Flash sale not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatFlashSale($flashSale, true),
        ]);
    }

    /**
     * Check if product is in flash sale
     */
    public function checkProduct(int $productId): JsonResponse
    {
        $flashSalePrice = $this->flashSaleService->getFlashSalePrice($productId);

        return response()->json([
            'success' => true,
            'in_flash_sale' => $flashSalePrice !== null,
            'data' => $flashSalePrice,
        ]);
    }

    /**
     * Validate flash sale purchase
     */
    public function validatePurchase(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $userId = auth('sanctum')->id();
        $result = $this->flashSaleService->validatePurchase(
            $request->product_id,
            $request->quantity,
            $userId
        );

        return response()->json([
            'success' => $result['valid'] ?? $result['allowed'] ?? false,
            'data' => $result,
        ]);
    }

    /**
     * Format flash sale for API response
     */
    protected function formatFlashSale(FlashSale $sale, bool $includeProducts = true): array
    {
        $data = [
            'id' => $sale->id,
            'name' => $sale->name,
            'slug' => $sale->slug,
            'description' => $sale->description,
            'banner_image' => $sale->banner_image,
            'starts_at' => $sale->starts_at->toIso8601String(),
            'ends_at' => $sale->ends_at->toIso8601String(),
            'status' => $sale->status,
            'time_remaining' => $sale->time_remaining,
            'is_featured' => $sale->is_featured,
        ];

        if ($includeProducts && $sale->relationLoaded('flashSaleProducts')) {
            $data['products'] = $sale->flashSaleProducts->map(fn($fsp) => [
                'id' => $fsp->product->id,
                'name' => $fsp->product->name,
                'slug' => $fsp->product->slug,
                'image' => $fsp->product->image,
                'category' => $fsp->product->category?->name,
                'original_price' => $fsp->original_price,
                'flash_price' => $fsp->flash_price,
                'discount_percentage' => $fsp->discount_percentage,
                'quantity_limit' => $fsp->quantity_limit,
                'sold_count' => $fsp->sold_count,
                'stock_remaining' => $fsp->stock_remaining,
                'is_sold_out' => $fsp->is_sold_out,
                'per_user_limit' => $fsp->per_user_limit,
            ]);
        } elseif ($includeProducts && $sale->relationLoaded('products')) {
            $data['products'] = $sale->products->map(fn($product) => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'image' => $product->image,
                'category' => $product->category?->name,
                'original_price' => $product->pivot->original_price,
                'flash_price' => $product->pivot->flash_price,
                'discount_percentage' => $product->pivot->discount_percentage,
                'quantity_limit' => $product->pivot->quantity_limit,
                'sold_count' => $product->pivot->sold_count,
            ]);
        }

        return $data;
    }
}
