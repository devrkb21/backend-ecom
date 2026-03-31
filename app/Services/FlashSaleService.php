<?php

namespace App\Services;

use App\Models\FlashSale;
use App\Models\FlashSaleProduct;
use App\Models\Product;
use App\Models\OrderItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class FlashSaleService
{
    /**
     * Get all active flash sales
     */
    public function getActiveFlashSales(): Collection
    {
        return FlashSale::active()
            ->with(['products' => function ($query) {
                $query->where('flash_sale_products.is_active', true)
                    ->with('category');
            }])
            ->orderBy('priority', 'desc')
            ->orderBy('ends_at')
            ->get();
    }

    /**
     * Get featured flash sale (for homepage banner)
     */
    public function getFeaturedFlashSale(): ?FlashSale
    {
        return FlashSale::active()
            ->featured()
            ->with(['products' => function ($query) {
                $query->where('flash_sale_products.is_active', true)
                    ->limit(8);
            }])
            ->orderBy('priority', 'desc')
            ->first();
    }

    /**
     * Get upcoming flash sales
     */
    public function getUpcomingFlashSales(): Collection
    {
        return FlashSale::upcoming()
            ->orderBy('starts_at')
            ->get();
    }

    /**
     * Get flash sale by slug
     */
    public function getFlashSaleBySlug(string $slug): ?FlashSale
    {
        return FlashSale::where('slug', $slug)
            ->with(['flashSaleProducts.product.category'])
            ->first();
    }

    /**
     * Get flash sale price for a product (if any)
     */
    public function getFlashSalePrice(int $productId): ?array
    {
        $flashSaleProduct = FlashSaleProduct::whereHas('flashSale', function ($query) {
            $query->active();
        })
            ->where('product_id', $productId)
            ->where('is_active', true)
            ->with('flashSale')
            ->first();

        if (!$flashSaleProduct) {
            return null;
        }

        return [
            'flash_sale_id' => $flashSaleProduct->flash_sale_id,
            'flash_sale_name' => $flashSaleProduct->flashSale->name,
            'flash_price' => $flashSaleProduct->flash_price,
            'original_price' => $flashSaleProduct->original_price,
            'discount_percentage' => $flashSaleProduct->discount_percentage,
            'quantity_limit' => $flashSaleProduct->quantity_limit,
            'sold_count' => $flashSaleProduct->sold_count,
            'stock_remaining' => $flashSaleProduct->stock_remaining,
            'ends_at' => $flashSaleProduct->flashSale->ends_at,
            'time_remaining' => $flashSaleProduct->flashSale->time_remaining,
        ];
    }

    /**
     * Check if product is in active flash sale
     */
    public function isProductInFlashSale(int $productId): bool
    {
        return FlashSaleProduct::whereHas('flashSale', function ($query) {
            $query->active();
        })
            ->where('product_id', $productId)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Create a new flash sale
     */
    public function createFlashSale(array $data): FlashSale
    {
        $flashSale = FlashSale::create([
            'name' => $data['name'],
            'slug' => $data['slug'] ?? Str::slug($data['name']),
            'description' => $data['description'] ?? null,
            'banner_image' => $data['banner_image'] ?? null,
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'],
            'is_active' => $data['is_active'] ?? true,
            'is_featured' => $data['is_featured'] ?? false,
            'priority' => $data['priority'] ?? 0,
        ]);

        // Add products if provided
        if (!empty($data['products'])) {
            $this->addProductsToFlashSale($flashSale, $data['products']);
        }

        return $flashSale;
    }

    /**
     * Add products to flash sale
     */
    public function addProductsToFlashSale(FlashSale $flashSale, array $products): void
    {
        foreach ($products as $productData) {
            $product = Product::find($productData['product_id']);

            if (!$product) {
                continue;
            }

            $originalPrice = $product->regular_price;
            $flashPrice = $productData['flash_price'];
            $discountPercentage = round((($originalPrice - $flashPrice) / $originalPrice) * 100);

            FlashSaleProduct::updateOrCreate(
                [
                    'flash_sale_id' => $flashSale->id,
                    'product_id' => $product->id,
                ],
                [
                    'flash_price' => $flashPrice,
                    'original_price' => $originalPrice,
                    'discount_percentage' => $discountPercentage,
                    'quantity_limit' => $productData['quantity_limit'] ?? null,
                    'per_user_limit' => $productData['per_user_limit'] ?? 1,
                    'is_active' => $productData['is_active'] ?? true,
                ]
            );
        }
    }

    /**
     * Remove product from flash sale
     */
    public function removeProductFromFlashSale(FlashSale $flashSale, int $productId): bool
    {
        return FlashSaleProduct::where('flash_sale_id', $flashSale->id)
            ->where('product_id', $productId)
            ->delete() > 0;
    }

    /**
     * Validate flash sale purchase
     */
    public function validatePurchase(int $productId, int $quantity, ?int $userId = null): array
    {
        $flashSaleProduct = FlashSaleProduct::whereHas('flashSale', function ($query) {
            $query->active();
        })
            ->where('product_id', $productId)
            ->where('is_active', true)
            ->with('flashSale')
            ->first();

        if (!$flashSaleProduct) {
            return ['valid' => false, 'reason' => 'Product not in active flash sale'];
        }

        return $flashSaleProduct->canPurchase($quantity, $userId);
    }

    /**
     * Process flash sale purchase (update sold count)
     */
    public function processPurchase(int $productId, int $quantity): void
    {
        $flashSaleProduct = FlashSaleProduct::whereHas('flashSale', function ($query) {
            $query->active();
        })
            ->where('product_id', $productId)
            ->where('is_active', true)
            ->first();

        if ($flashSaleProduct) {
            $flashSaleProduct->incrementSoldCount($quantity);
        }
    }

    /**
     * Get flash sale statistics
     */
    public function getFlashSaleStats(FlashSale $flashSale): array
    {
        $products = $flashSale->flashSaleProducts;

        $totalProducts = $products->count();
        $totalQuantityLimit = $products->sum('quantity_limit');
        $totalSold = $products->sum('sold_count');
        $totalRevenue = 0;

        // Calculate revenue from orders
        $orderItems = OrderItem::whereIn('product_id', $products->pluck('product_id'))
            ->whereHas('order', function ($query) use ($flashSale) {
                $query->whereBetween('created_at', [$flashSale->starts_at, $flashSale->ends_at])
                    ->whereNotIn('status', ['cancelled', 'failed']);
            })
            ->get();

        $totalRevenue = $orderItems->sum(fn($item) => $item->price * $item->quantity);

        return [
            'total_products' => $totalProducts,
            'total_quantity_limit' => $totalQuantityLimit,
            'total_sold' => $totalSold,
            'sell_through_rate' => $totalQuantityLimit > 0 ? round(($totalSold / $totalQuantityLimit) * 100, 1) : 0,
            'total_revenue' => round($totalRevenue, 2),
            'status' => $flashSale->status,
            'time_remaining' => $flashSale->time_remaining,
        ];
    }

    /**
     * End flash sale early
     */
    public function endFlashSale(FlashSale $flashSale): FlashSale
    {
        $flashSale->update(['ends_at' => now()]);
        return $flashSale->refresh();
    }

    /**
     * Extend flash sale
     */
    public function extendFlashSale(FlashSale $flashSale, \DateTime $newEndDate): FlashSale
    {
        $flashSale->update(['ends_at' => $newEndDate]);
        return $flashSale->refresh();
    }

    /**
     * Get products with flash sale prices applied
     */
    public function applyFlashSalePrices(Collection $products): Collection
    {
        $productIds = $products->pluck('id');

        $flashSaleProducts = FlashSaleProduct::whereHas('flashSale', function ($query) {
            $query->active();
        })
            ->whereIn('product_id', $productIds)
            ->where('is_active', true)
            ->with('flashSale')
            ->get()
            ->keyBy('product_id');

        return $products->map(function ($product) use ($flashSaleProducts) {
            if ($flashSaleProducts->has($product->id)) {
                $fsp = $flashSaleProducts[$product->id];
                $product->flash_sale = [
                    'flash_price' => $fsp->flash_price,
                    'discount_percentage' => $fsp->discount_percentage,
                    'ends_at' => $fsp->flashSale->ends_at,
                    'sold_count' => $fsp->sold_count,
                    'quantity_limit' => $fsp->quantity_limit,
                ];
            } else {
                $product->flash_sale = null;
            }
            return $product;
        });
    }
}
