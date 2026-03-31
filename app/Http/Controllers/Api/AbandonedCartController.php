<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AbandonedCart;
use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AbandonedCartController extends Controller
{
    /**
     * Track checkout progress (called from frontend during checkout)
     */
    public function track(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'checkout_step' => 'required|string|in:cart,shipping,payment',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'name' => 'nullable|string|max:255',
            'shipping_address' => 'nullable|string|max:500',
            'shipping_city' => 'nullable|string|max:100',
            'shipping_state' => 'nullable|string|max:100',
            'shipping_zip' => 'nullable|string|max:20',
            'shipping_country' => 'nullable|string|max:100',
            'payment_method' => 'nullable|string|max:50',
            'shipping_method' => 'nullable|string|max:50',
        ]);

        $userId = $request->user()?->id;
        $sessionId = $request->header('X-Session-ID') ?? $request->session()->getId();

        // Get cart data
        $cart = null;
        $cartItems = [];
        $subtotal = 0;
        $total = 0;
        $couponCode = null;
        $discountAmount = 0;

        if ($userId) {
            $cart = Cart::where('user_id', $userId)->with('items.product')->first();
        }

        if ($cart) {
            $cartItems = $cart->items->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product?->name ?? 'Unknown Product',
                    'product_sku' => $item->product?->sku,
                    'product_image' => $item->product?->thumbnail,
                    'variant_id' => $item->product_variant_id,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'subtotal' => $item->quantity * $item->price,
                ];
            })->toArray();

            $subtotal = $cart->subtotal;
            $total = $cart->total;
            $couponCode = $cart->coupon?->code;
            $discountAmount = $cart->discount_amount ?? 0;
        }

        // Track the abandoned cart
        $abandonedCart = AbandonedCart::trackCheckout([
            'checkout_step' => $validated['checkout_step'],
            'email' => $validated['email'] ?? $request->user()?->email,
            'phone' => $validated['phone'] ?? $request->user()?->phone,
            'name' => $validated['name'] ?? $request->user()?->name,
            'shipping_address' => $validated['shipping_address'] ?? null,
            'shipping_city' => $validated['shipping_city'] ?? null,
            'shipping_state' => $validated['shipping_state'] ?? null,
            'shipping_zip' => $validated['shipping_zip'] ?? null,
            'shipping_country' => $validated['shipping_country'] ?? null,
            'payment_method' => $validated['payment_method'] ?? null,
            'shipping_method' => $validated['shipping_method'] ?? null,
            'cart_id' => $cart?->id,
            'cart_items' => $cartItems,
            'subtotal' => $subtotal,
            'total' => $total,
            'coupon_code' => $couponCode,
            'discount_amount' => $discountAmount,
            'user_agent' => $request->userAgent(),
            'ip_address' => $request->ip(),
        ], $userId, $sessionId);

        return response()->json([
            'success' => true,
            'message' => 'Checkout progress tracked',
            'data' => [
                'abandoned_cart_id' => $abandonedCart->id,
            ],
        ]);
    }

    /**
     * Mark abandoned cart as recovered (called after successful order)
     */
    public function markRecovered(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
        ]);

        $userId = $request->user()?->id;
        $sessionId = $request->header('X-Session-ID') ?? $request->session()->getId();

        // Find and mark as recovered
        $abandonedCart = AbandonedCart::where(function ($query) use ($userId, $sessionId) {
            if ($userId) {
                $query->where('user_id', $userId);
            } elseif ($sessionId) {
                $query->where('session_id', $sessionId);
            }
        })
        ->whereIn('status', ['pending', 'follow_up'])
        ->orderBy('created_at', 'desc')
        ->first();

        if ($abandonedCart) {
            $abandonedCart->markAsRecovered($validated['order_id']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Abandoned cart marked as recovered',
        ]);
    }
}
