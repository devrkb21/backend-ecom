<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AbandonedCart;
use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AbandonedCartController extends Controller
{
    private function resolveSessionId(Request $request): ?string
    {
        $headerSessionId = trim((string) $request->header('X-Session-ID', ''));
        if ($headerSessionId !== '') {
            return $headerSessionId;
        }

        if ($request->hasSession()) {
            $sessionId = trim((string) $request->session()->getId());
            if ($sessionId !== '') {
                return $sessionId;
            }
        }

        return null;
    }

    /**
     * Keep only safe scalar checkout field values for abandoned cart snapshots.
     *
     * @param array<mixed> $checkoutFields
     * @return array<string, string|int|float|bool>
     */
    private function sanitizeCheckoutFieldsPayload(array $checkoutFields): array
    {
        $sanitized = [];

        foreach ($checkoutFields as $key => $value) {
            if (count($sanitized) >= 200) {
                break;
            }

            $normalizedKey = strtolower(trim((string) $key));
            if ($normalizedKey === '' || !preg_match('/^[a-z0-9_]{1,80}$/', $normalizedKey)) {
                continue;
            }

            if (is_bool($value)) {
                $sanitized[$normalizedKey] = $value;
                continue;
            }

            if (is_int($value) || is_float($value)) {
                $sanitized[$normalizedKey] = $value;
                continue;
            }

            if (is_string($value)) {
                $trimmed = trim($value);
                if ($trimmed === '') {
                    continue;
                }

                $sanitized[$normalizedKey] = substr($trimmed, 0, 1000);
            }
        }

        return $sanitized;
    }

    /**
     * Track checkout progress (called from frontend during checkout)
     */
    public function track(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'checkout_step' => 'required|string|in:cart,shipping,payment',
            'landing_page_slug' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'name' => 'nullable|string|max:255',
            'checkout_fields' => 'nullable|array|max:200',
            'shipping_address' => 'nullable|string|max:500',
            'shipping_location_text' => 'nullable|string|max:1000',
            'shipping_area' => 'nullable|string|max:255',
            'shipping_division' => 'nullable|string|max:120',
            'shipping_district' => 'nullable|string|max:120',
            'shipping_upazila' => 'nullable|string|max:120',
            'shipping_union' => 'nullable|string|max:120',
            'shipping_city' => 'nullable|string|max:100',
            'shipping_state' => 'nullable|string|max:100',
            'shipping_zip' => 'nullable|string|max:20',
            'shipping_country' => 'nullable|string|max:100',
            'payment_method' => 'nullable|string|max:50',
            'shipping_method' => 'nullable|string|max:50',
            'cart_items' => 'nullable|array',
            'cart_items.*.product_id' => 'required_with:cart_items|integer|min:1',
            'cart_items.*.product_name' => 'nullable|string|max:255',
            'cart_items.*.product_sku' => 'nullable|string|max:150',
            'cart_items.*.product_image' => 'nullable|string|max:2048',
            'cart_items.*.variant_id' => 'nullable|integer|min:1',
            'cart_items.*.variant_name' => 'nullable|string|max:255',
            'cart_items.*.variant_sku' => 'nullable|string|max:150',
            'cart_items.*.variant_attributes' => 'nullable|string|max:1000',
            'cart_items.*.quantity' => 'required_with:cart_items|integer|min:1|max:100',
            'cart_items.*.price' => 'nullable|numeric|min:0',
            'cart_items.*.subtotal' => 'nullable|numeric|min:0',
            'subtotal' => 'nullable|numeric|min:0',
            'total' => 'nullable|numeric|min:0',
            'coupon_code' => 'nullable|string|max:50',
            'discount_amount' => 'nullable|numeric|min:0',
        ]);

        $userId = $request->user()?->id;
        $sessionId = $this->resolveSessionId($request);
        $checkoutFieldsPayload = $this->sanitizeCheckoutFieldsPayload((array) ($validated['checkout_fields'] ?? []));

        // Get cart data
        $cart = null;
        $cartItems = [];
        $subtotal = 0;
        $total = 0;
        $couponCode = $validated['coupon_code'] ?? null;
        $discountAmount = 0;

        $providedCartItems = collect($validated['cart_items'] ?? [])->map(function (array $item) {
            $quantity = (int) ($item['quantity'] ?? 0);
            $price = (float) ($item['price'] ?? 0);

            return [
                'product_id' => (int) ($item['product_id'] ?? 0),
                'product_name' => $item['product_name'] ?? null,
                'product_sku' => $item['product_sku'] ?? null,
                'product_image' => $item['product_image'] ?? null,
                'variant_id' => $item['variant_id'] ?? null,
                'variant_name' => $item['variant_name'] ?? null,
                'variant_sku' => $item['variant_sku'] ?? null,
                'variant_attributes' => $item['variant_attributes'] ?? null,
                'quantity' => $quantity,
                'price' => $price,
                'subtotal' => isset($item['subtotal']) ? (float) $item['subtotal'] : ($quantity * $price),
            ];
        })->values();

        if ($userId) {
            $cart = Cart::where('user_id', $userId)
                ->with(['items.product', 'items.variant.attributeValues.attribute'])
                ->first();
        }

        if ($cart) {
            $cartItems = $cart->items->map(function ($item) {
                $variant = $item->variant;
                $variantName = $variant ? trim((string) $variant->name) : '';
                $variantSku = $variant ? trim((string) ($variant->sku ?? '')) : '';

                $variantAttributes = '';
                if ($variant && $variant->relationLoaded('attributeValues')) {
                    $variantAttributes = $variant->attributeValues
                        ->map(function ($attributeValue) {
                            $attributeName = trim((string) ($attributeValue->attribute?->name ?? ''));
                            $value = trim((string) ($attributeValue->value ?? ''));

                            if ($value === '') {
                                return null;
                            }

                            return $attributeName !== '' ? "{$attributeName}: {$value}" : $value;
                        })
                        ->filter()
                        ->implode(', ');
                }

                return [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product?->name ?? 'Unknown Product',
                    'product_sku' => $item->product?->sku,
                    'product_image' => $item->product?->primary_image_url,
                    'variant_id' => $item->product_variant_id,
                    'variant_name' => $variantName !== '' ? $variantName : null,
                    'variant_sku' => $variantSku !== '' ? $variantSku : null,
                    'variant_attributes' => $variantAttributes !== '' ? $variantAttributes : null,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'subtotal' => $item->quantity * $item->price,
                ];
            })->toArray();

            $subtotal = $cart->subtotal;
            $total = $cart->total;
            $couponCode = $cart->coupon?->code;
            $discountAmount = $cart->discount_amount ?? 0;
        } elseif ($providedCartItems->isNotEmpty()) {
            $cartItems = $providedCartItems->all();
            $subtotal = isset($validated['subtotal'])
                ? (float) $validated['subtotal']
                : (float) $providedCartItems->sum('subtotal');
            $discountAmount = (float) ($validated['discount_amount'] ?? 0);
            $total = isset($validated['total'])
                ? (float) $validated['total']
                : max(0, $subtotal - $discountAmount);
        }

        // Track the abandoned cart
        $abandonedCart = AbandonedCart::trackCheckout([
            'checkout_step' => $validated['checkout_step'],
            'landing_page_slug' => $validated['landing_page_slug'] ?? null,
            'email' => $validated['email'] ?? $request->user()?->email,
            'phone' => $validated['phone'] ?? $request->user()?->phone,
            'name' => $validated['name'] ?? $request->user()?->name,
            'shipping_address' => $validated['shipping_address'] ?? null,
            'shipping_location_text' => $validated['shipping_location_text'] ?? null,
            'shipping_area' => $validated['shipping_area'] ?? null,
            'shipping_division' => $validated['shipping_division'] ?? null,
            'shipping_district' => $validated['shipping_district'] ?? null,
            'shipping_upazila' => $validated['shipping_upazila'] ?? null,
            'shipping_union' => $validated['shipping_union'] ?? null,
            'shipping_city' => $validated['shipping_city'] ?? null,
            'shipping_state' => $validated['shipping_state'] ?? null,
            'shipping_zip' => $validated['shipping_zip'] ?? null,
            'shipping_country' => $validated['shipping_country'] ?? null,
            'payment_method' => $validated['payment_method'] ?? null,
            'shipping_method' => $validated['shipping_method'] ?? null,
            'checkout_fields_payload' => $checkoutFieldsPayload,
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
     * Remove matching abandoned-cart records after successful checkout.
     */
    public function markRecovered(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        $userId = $request->user()?->id;
        $sessionId = $this->resolveSessionId($request);

        // Find and remove matching open abandoned carts.
        $baseQuery = AbandonedCart::query()
            ->whereIn('status', ['pending', 'follow_up'])
            ->where('created_at', '>=', now()->subDays(7));

        $matchedIds = collect();

        if ($userId || $sessionId) {
            $matchedIds = $matchedIds->merge((clone $baseQuery)->where(function ($query) use ($userId, $sessionId) {
                if ($userId) {
                    $query->where('user_id', $userId);
                } elseif ($sessionId) {
                    $query->where('session_id', $sessionId);
                }
            })
            ->pluck('id'));
        }

        $email = strtolower(trim((string) ($validated['email'] ?? '')));
        $phone = preg_replace('/\D+/', '', (string) ($validated['phone'] ?? ''));
        $hasEmail = $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
        $hasPhone = is_string($phone) && $phone !== '';

        if ($hasEmail || $hasPhone) {
            $matchedIds = $matchedIds->merge((clone $baseQuery)
                ->where(function ($query) use ($hasEmail, $hasPhone, $email, $phone) {
                    $hasPrimaryCondition = false;

                    if ($hasEmail) {
                        $query->whereRaw('LOWER(email) = ?', [$email]);
                        $hasPrimaryCondition = true;
                    }

                    if ($hasPhone) {
                        $rawPhoneSql = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '(', ''), ')', ''), '+', '') = ?";

                        if ($hasPrimaryCondition) {
                            $query->orWhereRaw($rawPhoneSql, [$phone]);
                        } else {
                            $query->whereRaw($rawPhoneSql, [$phone]);
                        }
                    }
                })
                ->pluck('id'));
        }

        $idsToDelete = $matchedIds
            ->filter(fn ($id) => is_numeric($id) && (int) $id > 0)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($idsToDelete->isNotEmpty()) {
            AbandonedCart::query()
                ->whereIn('id', $idsToDelete->all())
                ->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Completed checkout removed from abandoned carts.',
        ]);
    }
}
