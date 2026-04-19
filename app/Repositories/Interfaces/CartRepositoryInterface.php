<?php

namespace App\Repositories\Interfaces;

use App\Models\Cart;

interface CartRepositoryInterface extends BaseRepositoryInterface
{
    public function getByUserId(int $userId): ?Cart;

    public function getOrCreateForUser(int $userId): Cart;

    public function addItem(int $cartId, int $productId, int $quantity, float $price, ?int $variantId = null): void;

    public function updateItemQuantity(int $cartId, int $productId, int $quantity, ?float $price = null, ?int $variantId = null): void;

    public function removeItem(int $cartId, int $productId, ?int $variantId = null): void;

    public function clearCart(int $cartId): void;
}
