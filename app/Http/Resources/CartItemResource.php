<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'variant_id' => null,
            'quantity' => $this->quantity,
            'price' => (float) $this->price,
            'subtotal' => (float) $this->subtotal,
            'product' => new ProductResource($this->whenLoaded('product')),
            'variant' => null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
