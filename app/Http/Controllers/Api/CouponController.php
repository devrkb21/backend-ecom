<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Coupon;
use App\Services\CouponService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    protected CouponService $couponService;

    public function __construct(CouponService $couponService)
    {
        $this->couponService = $couponService;
    }

    /**
     * Apply a coupon to the cart
     */
    public function apply(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|max:50',
            'items' => 'nullable|array|min:1',
            'items.*.product_id' => 'required_with:items|integer|exists:products,id',
            'items.*.quantity' => 'required_with:items|integer|min:1|max:100',
        ]);

        $user = $request->user();

        if (! $user) {
            $result = $this->couponService->applyToGuestItems($request->code, $request->input('items', []));

            if (! $result['success']) {
                return response()->json($result, 400);
            }

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => [
                    'coupon' => $result['coupon'],
                    'discount' => $result['discount'],
                ],
            ]);
        }

        $cart = Cart::with(['items.product', 'items.variant.attributeValues.attribute'])->where('user_id', $user->id)->first();

        if (! $cart || $cart->items->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Your cart is empty.',
            ], 400);
        }

        $result = $this->couponService->applyToCart($cart, $request->code);

        if (! $result['success']) {
            return response()->json($result, 400);
        }

        // Reload cart with updated data
        $cart->refresh();
        $cart->load(['items.product', 'items.variant.attributeValues.attribute', 'coupon']);

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => [
                'coupon' => $result['coupon'],
                'discount' => $result['discount'],
                'cart' => $this->formatCartResponse($cart),
            ],
        ]);
    }

    /**
     * Remove coupon from cart
     */
    public function remove(Request $request): JsonResponse
    {
        $user = $request->user();
        $cart = Cart::where('user_id', $user->id)->first();

        if (! $cart) {
            return response()->json([
                'success' => false,
                'message' => 'Cart not found.',
            ], 404);
        }

        $result = $this->couponService->removeFromCart($cart);

        $cart->refresh();
        $cart->load(['items.product', 'items.variant.attributeValues.attribute']);

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => [
                'cart' => $this->formatCartResponse($cart),
            ],
        ]);
    }

    /**
     * Validate a coupon code
     */
    public function validate(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|max:50',
            'order_total' => 'nullable|numeric|min:0',
        ]);

        $user = $request->user();
        $orderTotal = $request->input('order_total');

        // If no order total provided, calculate from cart
        if ($orderTotal === null && $user) {
            $cart = Cart::with('items')->where('user_id', $user->id)->first();
            if ($cart) {
                $orderTotal = $cart->items->sum(fn ($item) => $item->price * $item->quantity);
            }
        }

        $result = $this->couponService->validate($request->code, $user, $orderTotal);

        if (! $result['valid']) {
            return response()->json([
                'valid' => false,
                'message' => $result['message'],
            ], 400);
        }

        return response()->json([
            'valid' => true,
            'data' => [
                'coupon' => $result['coupon'],
                'discount' => $result['discount'],
            ],
        ]);
    }

    /**
     * Get available coupons for the user
     */
    public function available(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get cart total
        $cart = Cart::with('items')->where('user_id', $user->id)->first();
        $cartTotal = $cart ? $cart->items->sum(fn ($item) => $item->price * $item->quantity) : 0;

        $coupons = $this->couponService->getApplicableCoupons($user, $cartTotal);

        return response()->json([
            'success' => true,
            'data' => [
                'coupons' => $coupons,
                'cart_total' => $cartTotal,
            ],
        ]);
    }

    /**
     * Format cart response with totals
     */
    protected function formatCartResponse(Cart $cart): array
    {
        $subtotal = $cart->items->sum(fn ($item) => $item->price * $item->quantity);
        $discount = $cart->discount_amount ?? 0;
        $total = max(0, $subtotal - $discount);

        return [
            'id' => $cart->id,
            'items_count' => $cart->items->sum('quantity'),
            'subtotal' => round($subtotal, 2),
            'discount' => round($discount, 2),
            'total' => round($total, 2),
            'coupon' => $cart->coupon ? [
                'id' => $cart->coupon->id,
                'code' => $cart->coupon->code,
                'name' => $cart->coupon->name,
                'type' => $cart->coupon->type,
                'formatted_value' => $cart->coupon->formatted_value,
                'free_shipping' => $cart->coupon->free_shipping,
            ] : null,
            'items' => $cart->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product?->name,
                    'product_image' => $item->product?->primary_image_url,
                    'variant_id' => $item->product_variant_id,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'subtotal' => round($item->price * $item->quantity, 2),
                ];
            }),
        ];
    }
}
