<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\AddToCartRequest;
use App\Http\Requests\Cart\UpdateCartItemRequest;
use App\Http\Resources\CartResource;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(
        protected CartService $cartService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $cart = $this->cartService->getCart($request->user()->id);

        return $this->successResponse(new CartResource($cart));
    }

    public function addItem(AddToCartRequest $request): JsonResponse
    {
        try {
            $cart = $this->cartService->addToCart(
                $request->user()->id,
                $request->product_id,
                $request->quantity
            );

            return $this->successResponse(new CartResource($cart), 'Item added to cart');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function updateItem(UpdateCartItemRequest $request, int $productId): JsonResponse
    {
        try {
            $cart = $this->cartService->updateCartItem(
                $request->user()->id,
                $productId,
                $request->quantity
            );

            return $this->successResponse(new CartResource($cart), 'Cart updated');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function removeItem(Request $request, int $productId): JsonResponse
    {
        try {
            $cart = $this->cartService->removeFromCart($request->user()->id, $productId);

            return $this->successResponse(new CartResource($cart), 'Item removed from cart');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function clear(Request $request): JsonResponse
    {
        $this->cartService->clearCart($request->user()->id);

        return $this->successResponse(null, 'Cart cleared');
    }
}
