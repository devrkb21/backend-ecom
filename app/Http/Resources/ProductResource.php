<?php

namespace App\Http\Resources;

use App\Models\Product as ProductModel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $stockEnabled = ProductModel::isStockEnabled();
        $pricing = $this->resolveGlobalPricingSnapshot();

        // Determine if product has active variants
        $hasVariants = (($this->variants_count ?? null) !== null)
            ? $this->variants_count > 0
            : ($this->relationLoaded('variants')
                ? $this->variants->isNotEmpty()
                : $this->variants()->exists());

        // Calculate effective stock - use variant stock sum if variants exist, otherwise base stock
        $effectiveStock = $hasVariants
            ? (int) $this->total_stock
            : (int) $this->stock_quantity;

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

        $quantityOnePrice = !empty($dynamicDiscountTiers)
            ? $this->getPriceForQuantity(1)
            : (float) ($pricing['current_price'] ?? 0);

        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'short_description' => $this->short_description,
            'regular_price' => (float) ($pricing['regular_price'] ?? 0),
            'price' => (float) ($pricing['regular_price'] ?? 0),
            'sale_price' => array_key_exists('sale_price', $pricing) && $pricing['sale_price'] !== null
                ? (float) $pricing['sale_price']
                : null,
            'current_price' => (float) ($pricing['current_price'] ?? 0),
            'dynamic_price_for_quantity_1' => (float) $quantityOnePrice,
            'is_on_sale' => (bool) ($pricing['is_on_sale'] ?? false),
            'has_dynamic_discount' => !empty($dynamicDiscountTiers),
            'dynamic_discount_tiers' => $dynamicDiscountTiers,
            'default_variant_id' => $pricing['default_variant_id'] ?? null,
            'has_price_range' => (bool) ($pricing['has_price_range'] ?? false),
            'price_range_min' => (float) ($pricing['price_range_min'] ?? ($pricing['current_price'] ?? 0)),
            'price_range_max' => (float) ($pricing['price_range_max'] ?? ($pricing['current_price'] ?? 0)),
            'free_delivery' => $this->hasFreeDeliveryOffer(),
            'sku' => $this->sku,
            'stock_quantity' => $effectiveStock,
            'total_stock' => $effectiveStock,
            'in_stock' => $stockEnabled ? $effectiveStock > 0 : true,
            'stock_enabled' => $stockEnabled,
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
            'average_rating' => $this->average_rating,
            'review_count' => $this->review_count,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
