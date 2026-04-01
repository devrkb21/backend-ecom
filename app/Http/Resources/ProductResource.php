<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Determine if product has active variants
        $hasVariants = $this->relationLoaded('variants')
            ? $this->variants->isNotEmpty()
            : ($this->variants_count ?? 0) > 0;

        // Calculate effective stock - use variant stock sum if variants exist, otherwise base stock
        $effectiveStock = $hasVariants && $this->relationLoaded('variants') && $this->variants->isNotEmpty()
            ? $this->variants->sum('stock_quantity')
            : $this->stock_quantity;

        // Get primary image from product_images table
        $primaryImage = null;
        $primaryImageUrl = null;
        $dynamicDiscountTiers = $this->quantity_pricing_tiers;

        if ($this->relationLoaded('images') && $this->images->isNotEmpty()) {
            $primaryImg = $this->images->firstWhere('is_primary', true) ?? $this->images->first();
            if ($primaryImg) {
                $primaryImage = $primaryImg->image;
                $primaryImageUrl = $primaryImg->url;
            }
        }

        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'short_description' => $this->short_description,
            'regular_price' => (float) $this->regular_price,
            'price' => (float) $this->regular_price,
            'sale_price' => $this->sale_price ? (float) $this->sale_price : null,
            'current_price' => (float) ($this->sale_price ?? $this->regular_price),
            'dynamic_price_for_quantity_1' => $this->getPriceForQuantity(1),
            'is_on_sale' => $this->sale_price !== null && $this->sale_price < $this->regular_price,
            'has_dynamic_discount' => !empty($dynamicDiscountTiers),
            'dynamic_discount_tiers' => $dynamicDiscountTiers,
            'free_delivery' => $this->hasFreeDeliveryOffer(),
            'sku' => $this->sku,
            'stock_quantity' => $this->stock_quantity,
            'total_stock' => $effectiveStock,
            'in_stock' => $effectiveStock > 0,
            'image' => $primaryImage,
            'image_url' => $primaryImageUrl,
            'is_active' => $this->is_active,
            'is_featured' => $this->is_featured,
            'is_new' => $this->is_new,
            'is_bestseller' => $this->is_bestseller,
            'sales_count' => $this->sales_count,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'images' => ProductImageResource::collection($this->whenLoaded('images')),
            'variants' => ProductVariantResource::collection($this->whenLoaded('variants')),
            'has_variants' => $hasVariants,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
