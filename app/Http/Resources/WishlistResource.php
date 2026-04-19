<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WishlistResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $product = $this->product;
        $variant = $this->variant;
        $productPricing = $product->resolveGlobalPricingSnapshot();

        // Calculate effective price
        $price = (float) ($productPricing['regular_price'] ?? $product->regular_price);
        $salePrice = array_key_exists('sale_price', $productPricing) && $productPricing['sale_price'] !== null
            ? (float) $productPricing['sale_price']
            : null;
        $stockEnabled = Product::isStockEnabled();
        if ($variant) {
            $variantRegular = (float) $variant->regular_price;
            $variantCurrent = (float) $variant->discounted_price;

            $price = $variantRegular;
            $salePrice = $variantCurrent < $variantRegular ? $variantCurrent : null;
        }

        $currentPrice = $variant
            ? (float) $variant->discounted_price
            : (float) ($productPricing['current_price'] ?? ($salePrice ?? $price));

        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_variant_id' => $this->product_variant_id,
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'regular_price' => (float) $price,
                'sale_price' => $salePrice !== null ? (float) $salePrice : null,
                'current_price' => (float) $currentPrice,
                'is_on_sale' => $variant
                    ? ($salePrice && $salePrice < $price)
                    : (bool) ($productPricing['is_on_sale'] ?? ($salePrice && $salePrice < $price)),
                'image' => $product->primary_image,
                'image_url' => $product->primary_image_url,
                'in_stock' => $stockEnabled
                    ? ($variant ? $variant->hasStock() : $product->hasStock())
                    : true,
                'stock_quantity' => $variant
                    ? $variant->stock_quantity
                    : ($product->hasActiveVariants() ? $product->total_stock : $product->stock_quantity),
                'stock_enabled' => $stockEnabled,
                'category' => $product->category ? [
                    'id' => $product->category->id,
                    'name' => $product->category->name,
                    'slug' => $product->category->slug,
                ] : null,
            ],
            'variant' => $variant ? [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'name' => $variant->variant_name ?? $variant->sku,
                'attributes' => $variant->attribute_values ?? [],
            ] : null,
            'added_at' => $this->created_at->toISOString(),
        ];
    }
}
