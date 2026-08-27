<?php

namespace App\Http\Resources;

use App\Services\CheckoutTaxService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $subtotal = (float) $this->subtotal;
        $discountAmount = (float) ($this->discount_amount ?? 0);
        /** @var CheckoutTaxService $checkoutTaxService */
        $checkoutTaxService = app(CheckoutTaxService::class);
        $taxAmount = $checkoutTaxService->calculateTaxAmount($subtotal);
        $total = max(0, $subtotal - $discountAmount + $taxAmount);

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'items' => CartItemResource::collection($this->whenLoaded('items')),
            'item_count' => $this->item_count,
            'subtotal' => $subtotal,
            'discount' => $discountAmount,
            'discount_amount' => $discountAmount,
            'tax' => $taxAmount,
            'total' => (float) $total,
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
