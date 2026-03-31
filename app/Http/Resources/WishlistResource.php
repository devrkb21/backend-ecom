<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WishlistResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $product = $this->product;
        $variant = $this->variant;

        // Calculate effective price
        $price = $product->regular_price;
        $salePrice = $product->sale_price;
        
        if ($variant) {
            $price += $variant->price_adjustment;
            if ($salePrice) {
                $salePrice += $variant->price_adjustment;
            }
        }

        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_variant_id' => $this->product_variant_id,
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'regular_price' => (float) $price,
                'sale_price' => $salePrice ? (float) $salePrice : null,
                'current_price' => (float) ($salePrice ?? $price),
                'is_on_sale' => $salePrice && $salePrice < $price,
                'image' => $product->primary_image,
                'image_url' => $product->primary_image_url,
                'in_stock' => $variant 
                    ? $variant->stock_quantity > 0 
                    : $product->stock_quantity > 0,
                'stock_quantity' => $variant 
                    ? $variant->stock_quantity 
                    : $product->stock_quantity,
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
