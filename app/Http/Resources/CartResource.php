<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'items' => CartItemResource::collection($this->whenLoaded('items')),
            'item_count' => $this->item_count,
            'subtotal' => (float) $this->subtotal,
            'discount' => (float) ($this->discount_amount ?? 0),
            'discount_amount' => (float) ($this->discount_amount ?? 0),
            'tax' => 0,
            'total' => (float) $this->total,
            'coupon_code' => $this->coupon?->code ?? null,
            'coupon' => $this->when($this->coupon_id, function () {
                return [
                    'id' => $this->coupon?->id,
                    'code' => $this->coupon?->code,
                    'name' => $this->coupon?->name,
                    'type' => $this->coupon?->type,
                    'value' => $this->coupon?->value,
                    'formatted_value' => $this->coupon?->formatted_value,
                    'free_shipping' => $this->coupon?->free_shipping,
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
