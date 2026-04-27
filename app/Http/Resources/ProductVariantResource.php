<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $stockEnabled = Product::isStockEnabled();

        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'price_adjustment' => (float) $this->price_adjustment,
            'purchase_price' => (float) $this->purchase_price,
            'regular_price' => (float) $this->regular_price,
            'discounted_price' => (float) $this->discounted_price,
            'current_price' => (float) $this->discounted_price,
            'final_price' => (float) $this->discounted_price,
            'is_on_sale' => (float) $this->discounted_price < (float) $this->regular_price,
            'stock_quantity' => $this->stock_quantity,
            'in_stock' => $stockEnabled ? (int) $this->stock_quantity > 0 : true,
            'stock_enabled' => $stockEnabled,
            'is_active' => $this->is_active,
            'name' => $this->relationLoaded('attributeValues') ? $this->name : ($this->sku ?? ('Variant-' . $this->id)),
            'image' => $this->image,
            'image_url' => $this->image_url,
            'attributes' => $this->whenLoaded('attributeValues', function () {
                return $this->attributeValues->map(function ($value) {
                    return [
                        'attribute_id' => (int) ($value->attribute_id ?? $value->product_attribute_id),
                        'attribute_name' => $value->attribute->name,
                        'attribute_slug' => $value->attribute->slug,
                        'display_style' => $value->attribute->display_style,
                        'value_id' => $value->id,
                        'value' => $value->value,
                        'color_code' => $value->color_code,
                        'image' => $value->image,
                        'image_url' => $value->image_url,
                    ];
                });
            }),
        ];
    }
}
