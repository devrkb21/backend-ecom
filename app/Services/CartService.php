<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Repositories\Interfaces\CartRepositoryInterface;
use App\Repositories\Interfaces\ProductRepositoryInterface;
use Illuminate\Support\Facades\DB;

class CartService
{
    public function __construct(
        protected CartRepositoryInterface $cartRepository,
        protected ProductRepositoryInterface $productRepository
    ) {}

    public function getCart(int $userId): Cart
    {
        return $this->cartRepository->getOrCreateForUser($userId);
    }

    public function addToCart(int $userId, int $productId, int $quantity = 1, ?int $variantId = null): Cart
    {
        return DB::transaction(function () use ($userId, $productId, $quantity, $variantId) {
            $cart = $this->cartRepository->getOrCreateForUser($userId);
            $product = $this->productRepository->findOrFail($productId);

            if (!$product->is_active) {
                throw new \Exception('Product is not available.');
            }

            $variant = $this->resolveVariantContext($product, $variantId);

            $existingItem = $cart->items->first(function ($item) use ($productId, $variantId) {
                return (int) $item->product_id === $productId
                    && (int) ($item->product_variant_id ?? 0) === (int) ($variantId ?? 0);
            });

            $currentQuantity = (int) ($existingItem?->quantity ?? 0);
            $newQuantity = $currentQuantity + $quantity;

            $this->assertStockAvailability($product, $variant, $newQuantity);
            $price = $this->resolveLinePrice($product, $variant, $newQuantity);

            $this->cartRepository->addItem($cart->id, $productId, $quantity, $price, $variant?->id);

            // Recalculate coupon discount
            $cart = $this->cartRepository->getOrCreateForUser($userId);
            $cart->recalculateCoupon();

            return $this->cartRepository->getOrCreateForUser($userId);
        });
    }

    public function updateCartItem(int $userId, int $productId, int $quantity, ?int $variantId = null): Cart
    {
        return DB::transaction(function () use ($userId, $productId, $quantity, $variantId) {
            $cart = $this->cartRepository->getByUserId($userId);

            if (!$cart) {
                throw new \Exception('Cart not found.');
            }

            if ($quantity <= 0) {
                $this->cartRepository->removeItem($cart->id, $productId, $variantId);
            } else {
                $product = $this->productRepository->findOrFail($productId);
                if (!$product->is_active) {
                    throw new \Exception('Product is not available.');
                }

                $variant = $this->resolveVariantContext($product, $variantId);

                $this->assertStockAvailability($product, $variant, $quantity);
                $price = $this->resolveLinePrice($product, $variant, $quantity);

                $this->cartRepository->updateItemQuantity($cart->id, $productId, $quantity, $price, $variant?->id);
            }

            // Recalculate coupon discount
            $cart = $this->cartRepository->getOrCreateForUser($userId);
            $cart->recalculateCoupon();

            return $cart;
        });
    }

    public function removeFromCart(int $userId, int $productId, ?int $variantId = null): Cart
    {
        return DB::transaction(function () use ($userId, $productId, $variantId) {
            $cart = $this->cartRepository->getByUserId($userId);

            if (!$cart) {
                throw new \Exception('Cart not found.');
            }

            $this->cartRepository->removeItem($cart->id, $productId, $variantId);

            // Recalculate coupon discount
            $cart = $this->cartRepository->getOrCreateForUser($userId);
            $cart->recalculateCoupon();

            return $cart;
        });
    }

    public function clearCart(int $userId): void
    {
        $cart = $this->cartRepository->getByUserId($userId);

        if ($cart) {
            $this->cartRepository->clearCart($cart->id);
        }
    }

    protected function resolveVariantContext(Product $product, ?int $variantId = null): ?ProductVariant
    {
        $hasActiveVariants = $product->variants()
            ->where('is_active', true)
            ->exists();

        if ($variantId === null) {
            if ($hasActiveVariants) {
                throw new \Exception('Please select a product variant.');
            }

            return null;
        }

        $variant = $product->variants()->where('id', $variantId)->first();

        if (!$variant) {
            throw new \Exception('Selected variant is invalid for this product.');
        }

        if (!$variant->is_active) {
            throw new \Exception('Selected variant is not available.');
        }

        return $variant;
    }

    protected function assertStockAvailability(Product $product, ?ProductVariant $variant, int $quantity): void
    {
        if (!Product::isStockEnabled()) {
            return;
        }

        if ($variant instanceof ProductVariant) {
            if (!$variant->hasStock($quantity)) {
                throw new \Exception('Insufficient stock available for the selected variant.');
            }

            return;
        }

        if (!$product->hasStock($quantity)) {
            throw new \Exception('Insufficient stock available.');
        }
    }

    protected function resolveLinePrice(Product $product, ?ProductVariant $variant, int $quantity): float
    {
        $basePrice = $product->getPriceForQuantity($quantity);

        if ($variant instanceof ProductVariant) {
            $customDiscountedPrice = $variant->getRawOriginal('discounted_price');
            if ($customDiscountedPrice !== null) {
                return round(max(0, (float) $customDiscountedPrice), 2);
            }

            return round($basePrice + (float) $variant->price_adjustment, 2);
        }

        return $basePrice;
    }
}
