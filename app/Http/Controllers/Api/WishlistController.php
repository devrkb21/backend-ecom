<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WishlistResource;
use App\Models\Product;
use App\Models\Wishlist;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WishlistController extends Controller
{
    /**
     * Get user's wishlist
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $wishlist = Wishlist::with(['product.images', 'product.category', 'variant'])
            ->forUser($request->user()->id)
            ->latest()
            ->paginate(20);

        return WishlistResource::collection($wishlist);
    }

    /**
     * Add product to wishlist
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'product_variant_id' => 'nullable|exists:product_variants,id',
        ]);

        $userId = $request->user()->id;
        $productId = $validated['product_id'];
        $variantId = $validated['product_variant_id'] ?? null;

        // Check if already in wishlist
        if (Wishlist::isInWishlist($userId, $productId, $variantId)) {
            return response()->json([
                'success' => false,
                'message' => 'Product is already in your wishlist',
            ], 409);
        }

        $wishlist = Wishlist::create([
            'user_id' => $userId,
            'product_id' => $productId,
            'product_variant_id' => $variantId,
        ]);

        $wishlist->load(['product.images', 'product.category', 'variant']);

        return response()->json([
            'success' => true,
            'message' => 'Product added to wishlist',
            'data' => new WishlistResource($wishlist),
        ], 201);
    }

    /**
     * Toggle product in wishlist
     */
    public function toggle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'product_variant_id' => 'nullable|exists:product_variants,id',
        ]);

        $result = Wishlist::toggle(
            $request->user()->id,
            $validated['product_id'],
            $validated['product_variant_id'] ?? null
        );

        return response()->json([
            'success' => true,
            'added' => $result['added'],
            'message' => $result['message'],
        ]);
    }

    /**
     * Remove product from wishlist
     */
    public function destroy(Request $request, Wishlist $wishlist): JsonResponse
    {
        // Ensure user owns this wishlist item
        if ($wishlist->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $wishlist->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product removed from wishlist',
        ]);
    }

    /**
     * Remove by product ID
     */
    public function removeByProduct(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'product_variant_id' => 'nullable|exists:product_variants,id',
        ]);

        $deleted = Wishlist::where('user_id', $request->user()->id)
            ->where('product_id', $validated['product_id'])
            ->where('product_variant_id', $validated['product_variant_id'] ?? null)
            ->delete();

        if (! $deleted) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found in wishlist',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Product removed from wishlist',
        ]);
    }

    /**
     * Check if product is in wishlist
     */
    public function check(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'product_variant_id' => 'nullable|exists:product_variants,id',
        ]);

        $isInWishlist = Wishlist::isInWishlist(
            $request->user()->id,
            $validated['product_id'],
            $validated['product_variant_id'] ?? null
        );

        return response()->json([
            'success' => true,
            'in_wishlist' => $isInWishlist,
        ]);
    }

    /**
     * Clear entire wishlist
     */
    public function clear(Request $request): JsonResponse
    {
        Wishlist::where('user_id', $request->user()->id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Wishlist cleared',
        ]);
    }

    /**
     * Move item from wishlist to cart
     */
    public function moveToCart(Request $request, Wishlist $wishlist): JsonResponse
    {
        // Ensure user owns this wishlist item
        if ($wishlist->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        // Check if product is available
        $product = $wishlist->product;
        if (! $product || ! $product->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Product is no longer available',
            ], 400);
        }

        // Add to cart using cart service
        $cartService = app(CartService::class);

        try {
            $cartService->addToCart(
                $request->user()->id,
                $wishlist->product_id,
                1,
                $wishlist->product_variant_id
            );

            // Remove from wishlist
            $wishlist->delete();

            return response()->json([
                'success' => true,
                'message' => 'Product moved to cart',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get wishlist count
     */
    public function count(Request $request): JsonResponse
    {
        $count = Wishlist::where('user_id', $request->user()->id)->count();

        return response()->json([
            'success' => true,
            'count' => $count,
        ]);
    }
}
