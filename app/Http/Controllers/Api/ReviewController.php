<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class ReviewController extends Controller
{
    /**
     * Get reviews for a product
     */
    public function index(Request $request, int $productId): AnonymousResourceCollection
    {
        $product = Product::findOrFail($productId);

        $query = Review::with(['user'])
            ->where('product_id', $productId)
            ->approved();

        // Filter by rating
        if ($rating = $request->input('rating')) {
            $query->where('rating', $rating);
        }

        // Filter by verified purchase
        if ($request->boolean('verified_only')) {
            $query->verified();
        }

        // Filter by has images
        if ($request->boolean('with_images')) {
            $query->whereNotNull('images')->where('images', '!=', '[]');
        }

        // Sort
        $sortBy = $request->input('sort', 'recent');
        switch ($sortBy) {
            case 'helpful':
                $query->orderByDesc('helpful_count');
                break;
            case 'rating_high':
                $query->orderByDesc('rating');
                break;
            case 'rating_low':
                $query->orderBy('rating');
                break;
            case 'recent':
            default:
                $query->latest();
                break;
        }

        $reviews = $query->paginate(10);

        return ReviewResource::collection($reviews);
    }

    /**
     * Get review summary for a product
     */
    public function summary(int $productId): JsonResponse
    {
        $product = Product::findOrFail($productId);

        $reviews = Review::where('product_id', $productId)->approved();
        
        $totalReviews = $reviews->count();
        $averageRating = $reviews->avg('rating') ?? 0;
        
        // Rating distribution
        $distribution = [];
        for ($i = 5; $i >= 1; $i--) {
            $count = Review::where('product_id', $productId)
                ->approved()
                ->where('rating', $i)
                ->count();
            $distribution[$i] = [
                'count' => $count,
                'percentage' => $totalReviews > 0 ? round(($count / $totalReviews) * 100, 1) : 0,
            ];
        }

        $verifiedCount = Review::where('product_id', $productId)
            ->approved()
            ->verified()
            ->count();

        $withImagesCount = Review::where('product_id', $productId)
            ->approved()
            ->whereNotNull('images')
            ->where('images', '!=', '[]')
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_reviews' => $totalReviews,
                'average_rating' => round($averageRating, 1),
                'rating_distribution' => $distribution,
                'verified_count' => $verifiedCount,
                'with_images_count' => $withImagesCount,
            ],
        ]);
    }

    /**
     * Get featured reviews for a product
     */
    public function featured(int $productId): AnonymousResourceCollection
    {
        $reviews = Review::with(['user'])
            ->where('product_id', $productId)
            ->approved()
            ->featured()
            ->orderByDesc('helpful_count')
            ->take(3)
            ->get();

        return ReviewResource::collection($reviews);
    }

    /**
     * Create a review
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'order_id' => 'nullable|exists:orders,id',
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:255',
            'comment' => 'nullable|string|max:5000',
            'pros' => 'nullable|array|max:10',
            'pros.*' => 'string|max:100',
            'cons' => 'nullable|array|max:10',
            'cons.*' => 'string|max:100',
            'images' => 'nullable|array|max:5',
            'images.*' => 'string|max:500', // Media library paths
        ]);

        $userId = $request->user()->id;
        $productId = $validated['product_id'];
        $orderId = $validated['order_id'] ?? null;

        // Check if user already reviewed this product (without order)
        $existingReview = Review::where('user_id', $userId)
            ->where('product_id', $productId)
            ->where('order_id', $orderId)
            ->first();

        if ($existingReview) {
            return response()->json([
                'success' => false,
                'message' => 'You have already reviewed this product',
            ], 409);
        }

        // Strictly require verified purchase (delivered/completed order)
        $hasPurchased = Order::where('user_id', $userId)
            ->whereIn('status', ['delivered', 'completed'])
            ->whereHas('items', function ($q) use ($productId) {
                $q->where('product_id', $productId);
            })
            ->exists();

        if (!$hasPurchased) {
            return response()->json([
                'success' => false,
                'message' => 'You can only review products you have purchased and received.',
            ], 403);
        }

        $isVerifiedPurchase = true;

        $review = Review::create([
            'user_id' => $userId,
            'product_id' => $productId,
            'order_id' => $orderId,
            'rating' => $validated['rating'],
            'title' => $validated['title'] ?? null,
            'comment' => $validated['comment'] ?? null,
            'pros' => $validated['pros'] ?? null,
            'cons' => $validated['cons'] ?? null,
            'images' => $validated['images'] ?? null,
            'is_verified_purchase' => $isVerifiedPurchase,
            'is_approved' => false, // Requires admin approval
        ]);

        $review->load('user');

        return response()->json([
            'success' => true,
            'message' => 'Review submitted successfully. It will be visible after approval.',
            'data' => new ReviewResource($review),
        ], 201);
    }

    /**
     * Update user's own review
     */
    public function update(Request $request, Review $review): JsonResponse
    {
        // Ensure user owns this review
        if ($review->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $validated = $request->validate([
            'rating' => 'sometimes|integer|min:1|max:5',
            'title' => 'nullable|string|max:255',
            'comment' => 'nullable|string|max:5000',
            'pros' => 'nullable|array|max:10',
            'pros.*' => 'string|max:100',
            'cons' => 'nullable|array|max:10',
            'cons.*' => 'string|max:100',
            'images' => 'nullable|array|max:5',
            'images.*' => 'string|max:500',
        ]);

        // Reset approval when edited
        $validated['is_approved'] = false;

        $review->update($validated);
        $review->load('user');

        return response()->json([
            'success' => true,
            'message' => 'Review updated successfully. It will be re-reviewed for approval.',
            'data' => new ReviewResource($review),
        ]);
    }

    /**
     * Delete user's own review
     */
    public function destroy(Request $request, Review $review): JsonResponse
    {
        // Ensure user owns this review
        if ($review->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $review->delete();

        return response()->json([
            'success' => true,
            'message' => 'Review deleted successfully',
        ]);
    }

    /**
     * Vote on a review (helpful/unhelpful)
     */
    public function vote(Request $request, Review $review): JsonResponse
    {
        $validated = $request->validate([
            'is_helpful' => 'required|boolean',
        ]);

        // Can't vote on own review
        if ($review->user_id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot vote on your own review',
            ], 400);
        }

        $review->vote($request->user()->id, $validated['is_helpful']);

        return response()->json([
            'success' => true,
            'message' => 'Vote recorded',
            'data' => [
                'helpful_count' => $review->fresh()->helpful_count,
                'unhelpful_count' => $review->fresh()->unhelpful_count,
            ],
        ]);
    }

    /**
     * Remove vote from a review
     */
    public function removeVote(Request $request, Review $review): JsonResponse
    {
        $review->removeVote($request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Vote removed',
            'data' => [
                'helpful_count' => $review->fresh()->helpful_count,
                'unhelpful_count' => $review->fresh()->unhelpful_count,
            ],
        ]);
    }

    /**
     * Get user's reviews
     */
    public function myReviews(Request $request): AnonymousResourceCollection
    {
        $reviews = Review::with(['product.images', 'product.category'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        return ReviewResource::collection($reviews);
    }

    /**
     * Check if user can review a product
     */
    public function canReview(Request $request, int $productId): JsonResponse
    {
        $userId = $request->user()->id;
        // Match store()'s uniqueness key (user_id, product_id, order_id) —
        // previously this only checked order_id IS NULL, so a user with
        // multiple qualifying orders for the same product would see
        // can_review: true here even after already reviewing that exact
        // order, because store()'s uniqueness check uses the submitted
        // order_id while this one didn't accept it at all.
        $orderId = $request->query('order_id');
        $orderId = $orderId !== null ? (int) $orderId : null;

        // Check if already reviewed
        $existingReview = Review::where('user_id', $userId)
            ->where('product_id', $productId)
            ->where('order_id', $orderId)
            ->first();

        if ($existingReview) {
            return response()->json([
                'success' => true,
                'can_review' => false,
                'reason' => 'already_reviewed',
                'existing_review_id' => $existingReview->id,
            ]);
        }

        // Check if user has purchased this product
        $hasPurchased = Order::where('user_id', $userId)
            ->whereIn('status', ['delivered', 'completed'])
            ->whereHas('items', function ($q) use ($productId) {
                $q->where('product_id', $productId);
            })
            ->exists();

        return response()->json([
            'success' => true,
            'can_review' => $hasPurchased,
            'is_verified_purchase' => $hasPurchased,
            'reason' => $hasPurchased ? null : 'not_purchased',
        ]);
    }
}
