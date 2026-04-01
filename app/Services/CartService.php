<?php

namespace App\Services;

use App\Models\Cart;
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

    public function addToCart(int $userId, int $productId, int $quantity = 1): Cart
    {
        return DB::transaction(function () use ($userId, $productId, $quantity) {
            $cart = $this->cartRepository->getOrCreateForUser($userId);
            $product = $this->productRepository->findOrFail($productId);

            if (!$product->is_active) {
                throw new \Exception('Product is not available.');
            }

            $existingItem = $cart->items->firstWhere('product_id', $productId);
            $currentQuantity = (int) ($existingItem?->quantity ?? 0);
            $newQuantity = $currentQuantity + $quantity;

            if (!$product->hasStock($newQuantity)) {
                throw new \Exception('Insufficient stock available.');
            }

            $price = $product->getPriceForQuantity($newQuantity);
            $this->cartRepository->addItem($cart->id, $productId, $quantity, $price);

            // Recalculate coupon discount
            $cart = $this->cartRepository->getOrCreateForUser($userId);
            $cart->recalculateCoupon();

            return $this->cartRepository->getOrCreateForUser($userId);
        });
    }

    public function updateCartItem(int $userId, int $productId, int $quantity): Cart
    {
        return DB::transaction(function () use ($userId, $productId, $quantity) {
            $cart = $this->cartRepository->getByUserId($userId);

            if (!$cart) {
                throw new \Exception('Cart not found.');
            }

            if ($quantity <= 0) {
                $this->cartRepository->removeItem($cart->id, $productId);
            } else {
                $product = $this->productRepository->findOrFail($productId);

                if (!$product->hasStock($quantity)) {
                    throw new \Exception('Insufficient stock available.');
                }

                $price = $product->getPriceForQuantity($quantity);
                $this->cartRepository->updateItemQuantity($cart->id, $productId, $quantity, $price);
            }

            // Recalculate coupon discount
            $cart = $this->cartRepository->getOrCreateForUser($userId);
            $cart->recalculateCoupon();

            return $cart;
        });
    }

    public function removeFromCart(int $userId, int $productId): Cart
    {
        return DB::transaction(function () use ($userId, $productId) {
            $cart = $this->cartRepository->getByUserId($userId);

            if (!$cart) {
                throw new \Exception('Cart not found.');
            }

            $this->cartRepository->removeItem($cart->id, $productId);

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
}
