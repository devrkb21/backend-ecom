<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'price_adjustment' => (float) $this->price_adjustment,
            'final_price' => (float) $this->final_price,
            'stock_quantity' => $this->stock_quantity,
            'in_stock' => $this->stock_quantity > 0,
            'is_active' => $this->is_active,
            'name' => $this->name,
            'image' => $this->image,
            'image_url' => $this->image_url,
            'attributes' => $this->whenLoaded('attributeValues', function () {
                return $this->attributeValues->map(function ($value) {
                    return [
                        'attribute_id' => $value->product_attribute_id,
                        'attribute_name' => $value->attribute->name,
                        'attribute_slug' => $value->attribute->slug,
                        'value_id' => $value->id,
                        'value' => $value->value,
                        'color_code' => $value->color_code,
                    ];
                });
            }),
        ];
    }
}
