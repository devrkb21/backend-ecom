<?php

namespace App\Repositories;

use App\Models\Cart;
use App\Models\CartItem;
use App\Repositories\Interfaces\CartRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

class CartRepository extends BaseRepository implements CartRepositoryInterface
{
    protected array $cartRelations = [
        'items.product.images',
        'items.variant.attributeValues.attribute',
        'items.variant.product',
    ];

    public function __construct(Cart $model)
    {
        parent::__construct($model);
    }

    public function getByUserId(int $userId): ?Cart
    {
        return $this->model->with($this->cartRelations)->where('user_id', $userId)->first();
    }

    public function getOrCreateForUser(int $userId): Cart
    {
        return $this->model->with($this->cartRelations)->firstOrCreate(['user_id' => $userId]);
    }

    public function addItem(int $cartId, int $productId, int $quantity, float $price, ?int $variantId = null): void
    {
        $existingItem = $this->cartItemQuery($cartId, $productId, $variantId)->first();

        if ($existingItem) {
            $existingItem->update([
                'quantity' => $existingItem->quantity + $quantity,
                'price' => $price,
            ]);
        } else {
            CartItem::create([
                'cart_id' => $cartId,
                'product_id' => $productId,
                'product_variant_id' => $variantId,
                'quantity' => $quantity,
                'price' => $price,
            ]);
        }
    }

    public function updateItemQuantity(int $cartId, int $productId, int $quantity, ?float $price = null, ?int $variantId = null): void
    {
        $updateData = ['quantity' => $quantity];

        if ($price !== null) {
            $updateData['price'] = $price;
        }

        $this->cartItemQuery($cartId, $productId, $variantId)
            ->update($updateData);
    }

    public function removeItem(int $cartId, int $productId, ?int $variantId = null): void
    {
        $this->cartItemQuery($cartId, $productId, $variantId)
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

    protected function cartItemQuery(int $cartId, int $productId, ?int $variantId = null): Builder
    {
        $query = CartItem::query()
            ->where('cart_id', $cartId)
            ->where('product_id', $productId);

        if ($variantId === null) {
            $query->whereNull('product_variant_id');
        } else {
            $query->where('product_variant_id', $variantId);
        }

        return $query;
    }
}
