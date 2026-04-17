<?php

namespace App\Repositories;

use App\Models\Cart;
use App\Models\CartItem;
use App\Repositories\Interfaces\CartRepositoryInterface;

class CartRepository extends BaseRepository implements CartRepositoryInterface
{
    public function __construct(Cart $model)
    {
        parent::__construct($model);
    }

    public function getByUserId(int $userId): ?Cart
    {
        return $this->model->with(['items.product'])->where('user_id', $userId)->first();
    }

    public function getOrCreateForUser(int $userId): Cart
    {
        return $this->model->with(['items.product'])->firstOrCreate(['user_id' => $userId]);
    }

    public function addItem(int $cartId, int $productId, int $quantity, float $price): void
    {
        $existingItem = CartItem::where('cart_id', $cartId)
            ->where('product_id', $productId)
            ->first();

        if ($existingItem) {
            $existingItem->update([
                'quantity' => $existingItem->quantity + $quantity,
                'price' => $price,
            ]);
        } else {
            CartItem::create([
                'cart_id' => $cartId,
                'product_id' => $productId,
                'quantity' => $quantity,
                'price' => $price,
            ]);
        }
    }

    public function updateItemQuantity(int $cartId, int $productId, int $quantity, ?float $price = null): void
    {
        $updateData = ['quantity' => $quantity];

        if ($price !== null) {
            $updateData['price'] = $price;
        }

        CartItem::where('cart_id', $cartId)
            ->where('product_id', $productId)
            ->update($updateData);
    }

    public function removeItem(int $cartId, int $productId): void
    {
        CartItem::where('cart_id', $cartId)
            ->where('product_id', $productId)
            ->delete();
    }

    public function clearCart(int $cartId): void
    {
        $cart = Cart::query()->find($cartId);

        if (!$cart) {
            return;
        }

        $cart->clear();
    }
}
