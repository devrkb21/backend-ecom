<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Product;
use App\Models\User;
use InvalidArgumentException;

class CouponService
{
    /**
     * Apply coupon to cart
     */
    public function applyToCart(Cart $cart, string $code): array
    {
        $coupon = Coupon::findByCode($code);

        if (!$coupon) {
            return [
                'success' => false,
                'message' => 'Invalid coupon code.',
            ];
        }

        // Validate coupon for cart
        $errors = $coupon->validateForCart($cart);
        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => $errors[0],
                'errors' => $errors,
            ];
        }

        // Check user limit
        if ($cart->user_id && $coupon->usage_limit_per_user) {
            $userUsageCount = CouponUsage::where('coupon_id', $coupon->id)
                ->where('user_id', $cart->user_id)
                ->count();
            
            if ($userUsageCount >= $coupon->usage_limit_per_user) {
                return [
                    'success' => false,
                    'message' => 'You have already used this coupon the maximum number of times.',
                ];
            }
        }

        // Calculate discount
        $discount = $coupon->calculateDiscount($cart);

        // Update cart with coupon
        $cart->update([
            'coupon_id' => $coupon->id,
            'discount_amount' => $discount,
        ]);

        return [
            'success' => true,
            'message' => 'Coupon applied successfully!',
            'coupon' => $this->formatCouponPayload($coupon),
            'discount' => $discount,
        ];
    }

    /**
     * Validate and apply coupon against guest-provided cart items (without persisting cart state).
     */
    public function applyToGuestItems(string $code, array $items): array
    {
        $coupon = Coupon::findByCode($code);

        if (!$coupon) {
            return [
                'success' => false,
                'message' => 'Invalid coupon code.',
            ];
        }

        if (!$coupon->allow_guest_checkout) {
            return [
                'success' => false,
                'message' => 'Please login to use this coupon.',
            ];
        }

        try {
            $previewCart = $this->buildGuestPreviewCart($items);
        } catch (InvalidArgumentException $exception) {
            return [
                'success' => false,
                'message' => $exception->getMessage(),
            ];
        }

        $errors = $coupon->validateForCart($previewCart);
        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => $errors[0],
                'errors' => $errors,
            ];
        }

        $discount = $coupon->calculateDiscount($previewCart);

        return [
            'success' => true,
            'message' => 'Coupon applied successfully!',
            'coupon' => $this->formatCouponPayload($coupon),
            'discount' => $discount,
        ];
    }

    /**
     * Remove coupon from cart
     */
    public function removeFromCart(Cart $cart): array
    {
        $cart->update([
            'coupon_id' => null,
            'discount_amount' => 0,
        ]);

        return [
            'success' => true,
            'message' => 'Coupon removed.',
        ];
    }

    /**
     * Validate coupon code
     */
    public function validate(string $code, ?User $user = null, ?float $orderTotal = null): array
    {
        $coupon = Coupon::findByCode($code);

        if (!$coupon) {
            return [
                'valid' => false,
                'message' => 'Invalid coupon code.',
            ];
        }

        if (!$coupon->isValid()) {
            $status = $coupon->status;
            $messages = [
                'inactive' => 'This coupon is no longer active.',
                'expired' => 'This coupon has expired.',
                'scheduled' => 'This coupon is not yet valid.',
                'exhausted' => 'This coupon has reached its usage limit.',
            ];
            
            return [
                'valid' => false,
                'message' => $messages[$status] ?? 'This coupon is not valid.',
            ];
        }

        if ($user && !$coupon->isValidForUser($user)) {
            return [
                'valid' => false,
                'message' => 'You have already used this coupon the maximum number of times.',
            ];
        }

        if (!$user && !$coupon->allow_guest_checkout) {
            return [
                'valid' => false,
                'message' => 'Please login to use this coupon.',
            ];
        }

        if ($orderTotal !== null && $coupon->minimum_order_amount) {
            if ($orderTotal < $coupon->minimum_order_amount) {
                return [
                    'valid' => false,
                    'message' => "Minimum order amount of ৳{$coupon->minimum_order_amount} required.",
                ];
            }
        }

        $discount = $orderTotal !== null ? $coupon->calculateDiscountForAmount($orderTotal) : null;

        return [
            'valid' => true,
            'coupon' => $this->formatCouponPayload($coupon),
            'discount' => $discount,
        ];
    }

    /**
     * Get applicable coupons for a user
     */
    public function getApplicableCoupons(?User $user, float $cartTotal): array
    {
        $coupons = Coupon::active()
            ->where(function ($query) use ($cartTotal) {
                $query->whereNull('minimum_order_amount')
                    ->orWhere('minimum_order_amount', '<=', $cartTotal);
            })
            ->get();

        $applicable = [];

        foreach ($coupons as $coupon) {
            /** @var Coupon $coupon */
            if (!$user && !$coupon->allow_guest_checkout) {
                continue;
            }

            if ($user && !$coupon->isValidForUser($user)) {
                continue;
            }

            $discount = $coupon->calculateDiscountForAmount($cartTotal);

            $applicable[] = [
                'id' => $coupon->id,
                'code' => $coupon->code,
                'name' => $coupon->name,
                'description' => $coupon->description,
                'type' => $coupon->type,
                'formatted_value' => $coupon->formatted_value,
                'potential_discount' => $discount,
                'minimum_order_amount' => $coupon->minimum_order_amount,
                'expires_at' => $coupon->expires_at?->format('M d, Y'),
                'free_shipping' => $coupon->free_shipping,
            ];
        }

        // Sort by potential discount (highest first)
        usort($applicable, fn($a, $b) => $b['potential_discount'] <=> $a['potential_discount']);

        return $applicable;
    }

    private function formatCouponPayload(Coupon $coupon): array
    {
        return [
            'id' => $coupon->id,
            'code' => $coupon->code,
            'name' => $coupon->name,
            'type' => $coupon->type,
            'value' => $coupon->value,
            'formatted_value' => $coupon->formatted_value,
            'minimum_order_amount' => $coupon->minimum_order_amount,
            'maximum_discount' => $coupon->maximum_discount,
            'free_shipping' => $coupon->free_shipping,
            'allow_guest_checkout' => $coupon->allow_guest_checkout,
        ];
    }

    private function buildGuestPreviewCart(array $items): Cart
    {
        if (empty($items)) {
            throw new InvalidArgumentException('Your cart is empty.');
        }

        $normalizedItems = collect($items)
            ->map(function (array $item) {
                $productId = (int) ($item['product_id'] ?? 0);
                $quantity = (int) ($item['quantity'] ?? 0);

                if ($productId <= 0) {
                    throw new InvalidArgumentException('Invalid product provided for coupon validation.');
                }

                if ($quantity <= 0) {
                    throw new InvalidArgumentException('Invalid quantity provided for coupon validation.');
                }

                return [
                    'product_id' => $productId,
                    'quantity' => $quantity,
                ];
            })
            ->groupBy('product_id')
            ->map(function ($group, int $productId) {
                return [
                    'product_id' => $productId,
                    'quantity' => (int) $group->sum('quantity'),
                ];
            })
            ->values();

        $products = Product::query()
            ->whereIn('id', $normalizedItems->pluck('product_id')->all())
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        $previewItems = $normalizedItems->map(function (array $item) use ($products) {
            /** @var Product|null $product */
            $product = $products->get($item['product_id']);

            if (!$product) {
                throw new InvalidArgumentException('One or more cart items are no longer available.');
            }

            return (object) [
                'product_id' => (int) $item['product_id'],
                'quantity' => (int) $item['quantity'],
                'price' => (float) $product->current_price,
                'product' => $product,
            ];
        });

        $cart = new Cart();
        $cart->setAttribute('user_id', null);
        $cart->setRelation('items', $previewItems);

        return $cart;
    }
}
