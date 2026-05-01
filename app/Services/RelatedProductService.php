<?php

namespace App\Services;

use App\Models\Product;
use App\Models\RelatedProduct;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class RelatedProductService
{
    /**
     * Get related products for a product
     */
    public function getRelatedProducts(Product $product, int $limit = 8): Collection
    {
        // First, get manually set related products
        $manualRelated = $this->getManualRelated($product, $limit);

        if ($manualRelated->count() >= $limit) {
            return $manualRelated->take($limit);
        }

        // Fill remaining slots with auto-generated recommendations
        $remaining = $limit - $manualRelated->count();
        $excludeIds = $manualRelated->pluck('id')->push($product->id)->toArray();

        $autoRelated = $this->getAutoRelated($product, $remaining, $excludeIds);

        return $manualRelated->merge($autoRelated);
    }

    /**
     * Get manually set related products
     */
    protected function getManualRelated(Product $product, int $limit): Collection
    {
        return Product::whereIn('id', function ($query) use ($product) {
            $query->select('related_product_id')
                ->from('related_products')
                ->where('product_id', $product->id)
                ->orderByDesc('score');
        })
            ->where('is_active', true)
            ->with(['images'])
            ->limit($limit)
            ->get();
    }

    /**
     * Auto-generate related products based on various factors
     */
    protected function getAutoRelated(Product $product, int $limit, array $excludeIds = []): Collection
    {
        if ($limit <= 0) {
            return collect();
        }

        // Combine different recommendation strategies
        $recommendations = collect();

        // 1. Same category products (weight: 3)
        $categoryProducts = $this->getSameCategoryProducts($product, $excludeIds, ceil($limit * 0.5));
        $recommendations = $recommendations->merge($categoryProducts);

        // 2. Frequently bought together (weight: 5)
        $frequentlyBought = $this->getFrequentlyBoughtTogether($product, $excludeIds, ceil($limit * 0.3));
        $recommendations = $recommendations->merge($frequentlyBought);

        // 3. Similar price range (weight: 2)
        $similarPrice = $this->getSimilarPriceProducts($product, $excludeIds, ceil($limit * 0.2));
        $recommendations = $recommendations->merge($similarPrice);

        return $recommendations->unique('id')->take($limit);
    }

    /**
     * Get products from same category
     */
    protected function getSameCategoryProducts(Product $product, array $excludeIds, int $limit): Collection
    {
        return Product::where('category_id', $product->category_id)
            ->where('is_active', true)
            ->whereNotIn('id', array_merge($excludeIds, [$product->id]))
            ->with(['images'])
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    /**
     * Get frequently bought together products
     */
    public function getFrequentlyBoughtTogether(Product $product, array $excludeIds = [], int $limit = 5): Collection
    {
        // Find orders containing this product
        $orderIds = OrderItem::where('product_id', $product->id)
            ->pluck('order_id');

        if ($orderIds->isEmpty()) {
            return collect();
        }

        $productIds = DB::table('order_items')
            ->select('product_id')
            ->selectRaw('COUNT(*) as purchase_count')
            ->whereIn('order_id', $orderIds)
            ->where('product_id', '!=', $product->id)
            ->whereNotIn('product_id', $excludeIds)
            ->groupBy('product_id')
            ->orderByDesc('purchase_count')
            ->limit($limit)
            ->pluck('product_id');

        if ($productIds->isEmpty()) {
            return collect();
        }

        $products = Product::whereIn('id', $productIds)
            ->where('is_active', true)
            ->with(['images'])
            ->get();

        return $productIds->map(function ($id) use ($products) {
            return $products->firstWhere('id', $id);
        })->filter()->values();
    }

    /**
     * Get products in similar price range
     */
    protected function getSimilarPriceProducts(Product $product, array $excludeIds, int $limit): Collection
    {
        $minPrice = $product->regular_price * 0.7;
        $maxPrice = $product->regular_price * 1.3;

        return Product::where('is_active', true)
            ->whereBetween('regular_price', [$minPrice, $maxPrice])
            ->whereNotIn('id', array_merge($excludeIds, [$product->id]))
            ->with(['images'])
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    /**
     * Set manual related products
     */
    public function setRelatedProducts(Product $product, array $relatedProductIds, string $relationType = 'manual'): void
    {
        // Remove existing manual relations
        RelatedProduct::where('product_id', $product->id)
            ->where('relation_type', $relationType)
            ->delete();

        // Add new relations
        foreach ($relatedProductIds as $index => $relatedId) {
            if ($relatedId != $product->id) {
                RelatedProduct::create([
                    'product_id' => $product->id,
                    'related_product_id' => $relatedId,
                    'relation_type' => $relationType,
                    'score' => count($relatedProductIds) - $index, // Higher score for earlier items
                ]);
            }
        }
    }

    /**
     * Sync frequently bought together relations (run periodically)
     */
    public function syncFrequentlyBoughtTogether(int $minPurchases = 3): void
    {
        // Get all product pairs bought together
        $pairs = DB::table('order_items as oi1')
            ->select('oi1.product_id', 'oi2.product_id as related_product_id')
            ->selectRaw('COUNT(*) as frequency')
            ->join('order_items as oi2', function ($join) {
                $join->on('oi1.order_id', '=', 'oi2.order_id')
                    ->whereColumn('oi1.product_id', '<', 'oi2.product_id');
            })
            ->join('orders', 'oi1.order_id', '=', 'orders.id')
            ->whereNotIn('orders.status', ['cancelled', 'failed'])
            ->groupBy('oi1.product_id', 'oi2.product_id')
            ->having('frequency', '>=', $minPurchases)
            ->get();

        // Clear old frequently bought relations
        RelatedProduct::where('relation_type', 'frequently_bought')->delete();

        // Insert new relations (both directions)
        foreach ($pairs as $pair) {
            RelatedProduct::create([
                'product_id' => $pair->product_id,
                'related_product_id' => $pair->related_product_id,
                'relation_type' => 'frequently_bought',
                'score' => $pair->frequency,
            ]);

            RelatedProduct::create([
                'product_id' => $pair->related_product_id,
                'related_product_id' => $pair->product_id,
                'relation_type' => 'frequently_bought',
                'score' => $pair->frequency,
            ]);
        }
    }

    /**
     * Get "You may also like" products for cart
     */
    public function getCartRecommendations(array $cartProductIds, int $limit = 6): Collection
    {
        if (empty($cartProductIds)) {
            // Return popular/featured products
            return Product::where('is_active', true)
                ->where('is_featured', true)
                ->with(['images'])
                ->inRandomOrder()
                ->limit($limit)
                ->get();
        }

        // Get products frequently bought with cart items
        $orderIds = OrderItem::whereIn('product_id', $cartProductIds)
            ->pluck('order_id')
            ->unique();

        $recommendedProductIds = DB::table('order_items')
            ->select('product_id')
            ->selectRaw('COUNT(*) as relevance_score')
            ->whereIn('order_id', $orderIds)
            ->whereNotIn('product_id', $cartProductIds)
            ->groupBy('product_id')
            ->orderByDesc('relevance_score')
            ->limit($limit)
            ->pluck('product_id');

        if ($recommendedProductIds->isEmpty()) {
            return collect();
        }

        $products = Product::whereIn('id', $recommendedProductIds)
            ->where('is_active', true)
            ->with(['images'])
            ->get();

        return $recommendedProductIds->map(function ($id) use ($products) {
            return $products->firstWhere('id', $id);
        })->filter()->values();
    }

    /**
     * Get upsell products (higher priced alternatives)
     */
    public function getUpsellProducts(Product $product, int $limit = 4): Collection
    {
        return Product::where('category_id', $product->category_id)
            ->where('is_active', true)
            ->where('id', '!=', $product->id)
            ->where('regular_price', '>', $product->regular_price)
            ->with(['images'])
            ->orderBy('regular_price')
            ->limit($limit)
            ->get();
    }

    /**
     * Get cross-sell products (complementary products)
     */
    public function getCrossSellProducts(Product $product, int $limit = 4): Collection
    {
        // First try frequently bought together
        $crossSell = $this->getFrequentlyBoughtTogether($product, [], $limit);

        if ($crossSell->count() < $limit) {
            // Fill with products from different categories
            $remaining = $limit - $crossSell->count();
            $excludeIds = $crossSell->pluck('id')->push($product->id)->toArray();

            $otherCategory = Product::where('category_id', '!=', $product->category_id)
                ->where('is_active', true)
                ->whereNotIn('id', $excludeIds)
                ->with(['images'])
                ->inRandomOrder()
                ->limit($remaining)
                ->get();

            $crossSell = $crossSell->merge($otherCategory);
        }

        return $crossSell;
    }
}
