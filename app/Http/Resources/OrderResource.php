<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $subtotal = (float) $this->subtotal;
        $tax = (float) $this->tax;
        $shipping = (float) $this->shipping;
        $discountAmount = (float) ($this->discount_amount ?? 0);
        $total = (float) $this->total;
        $paymentCharge = max(0, round($total - ($subtotal + $tax + $shipping - $discountAmount), 2));

        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $this->status,
            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status,
            'transaction_id' => $this->transaction_id,
            'shipping_method' => $this->shipping_method,
            'coupon_code' => $this->coupon_code,
            'discount_amount' => $discountAmount,
            'payment_charge' => $paymentCharge,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'shipping' => $shipping,
            'total' => $total,
            'shipping_name' => $this->shipping_name,
            'shipping_email' => $this->shipping_email,
            'shipping_phone' => $this->shipping_phone,
            'shipping_address' => $this->shipping_address,
            'shipping_division_id' => $this->shipping_division_id,
            'shipping_district_id' => $this->shipping_district_id,
            'shipping_upazila_id' => $this->shipping_upazila_id,
            'shipping_union_id' => $this->shipping_union_id,
            'shipping_city' => $this->shipping_city,
            'shipping_state' => $this->shipping_state,
            'shipping_zip' => $this->shipping_zip,
            'shipping_country' => $this->shipping_country,
            'shipping_division' => $this->whenLoaded('shippingDivision', fn () => $this->shippingDivision?->name),
            'shipping_district' => $this->whenLoaded('shippingDistrict', fn () => $this->shippingDistrict?->name),
            'shipping_upazila' => $this->whenLoaded('shippingUpazila', fn () => $this->shippingUpazila?->name),
            'shipping_union' => $this->whenLoaded('shippingUnion', fn () => $this->shippingUnion?->name),
            'notes' => $this->notes,
            'checkout_fields_payload' => $this->checkout_fields_payload,
            // Tracking info
            'tracking_number' => $this->tracking_number,
            'carrier' => $this->carrier,
            'carrier_tracking_url' => $this->carrier_tracking_url,
            'shipped_at' => $this->shipped_at?->toIso8601String(),
            'estimated_delivery_at' => $this->estimated_delivery_at?->toIso8601String(),
            'delivered_at' => $this->delivered_at?->toIso8601String(),
            'tracking_progress' => $this->tracking_progress,
            'has_tracking' => $this->hasTrackingInfo(),
            // Relations
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'payment' => new PaymentResource($this->whenLoaded('payment')),
            'user' => new UserResource($this->whenLoaded('user')),
            'tracking_history' => OrderTrackingResource::collection($this->whenLoaded('trackingHistory')),
            'can_be_cancelled' => $this->canBeCancelled(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
