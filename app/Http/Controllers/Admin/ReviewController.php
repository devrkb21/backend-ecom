<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * Display a listing of reviews
     */
    public function index(Request $request)
    {
        $query = Review::with(['user', 'product']);

        // Filter by status
        if ($status = $request->input('status')) {
            switch ($status) {
                case 'pending':
                    $query->where('is_approved', false);
                    break;
                case 'approved':
                    $query->where('is_approved', true);
                    break;
                case 'featured':
                    $query->where('is_featured', true);
                    break;
            }
        }

        // Filter by rating
        if ($rating = $request->input('rating')) {
            $query->where('rating', $rating);
        }

        // Filter by product
        if ($productId = $request->input('product_id')) {
            $query->where('product_id', $productId);
        }

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('comment', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('product', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $perPage = in_array((int) $request->input('per_page'), [20, 50, 100], true) ? (int) $request->input('per_page') : 20;
        $reviews = $query->latest()->paginate($perPage)->withQueryString();
        $products = Product::orderBy('name')->get(['id', 'name']);

        // Stats
        $stats = [
            'total' => Review::count(),
            'pending' => Review::where('is_approved', false)->count(),
            'approved' => Review::where('is_approved', true)->count(),
            'featured' => Review::where('is_featured', true)->count(),
        ];

        return view('admin.reviews.index', compact('reviews', 'products', 'stats'));
    }

    /**
     * Display the specified review
     */
    public function show(Review $review)
    {
        $review->load(['user', 'product.images', 'order']);

        return view('admin.reviews.show', compact('review'));
    }

    /**
     * Approve a review
     */
    public function approve(Review $review)
    {
        $review->update(['is_approved' => true]);

        return back()->with('success', 'Review approved successfully.');
    }

    /**
     * Reject/Unapprove a review
     */
    public function reject(Review $review)
    {
        $review->update(['is_approved' => false]);

        return back()->with('success', 'Review rejected successfully.');
    }

    /**
     * Toggle featured status
     */
    public function toggleFeatured(Review $review)
    {
        $review->update(['is_featured' => ! $review->is_featured]);

        $status = $review->is_featured ? 'featured' : 'unfeatured';

        return back()->with('success', "Review {$status} successfully.");
    }

    /**
     * Add admin reply
     */
    public function reply(Request $request, Review $review)
    {
        $validated = $request->validate([
            'admin_reply' => 'required|string|max:2000',
        ]);

        $review->addAdminReply($validated['admin_reply']);

        return back()->with('success', 'Reply added successfully.');
    }

    /**
     * Remove admin reply
     */
    public function removeReply(Review $review)
    {
        $review->update([
            'admin_reply' => null,
            'admin_replied_at' => null,
        ]);

        return back()->with('success', 'Reply removed successfully.');
    }

    /**
     * Delete a review
     */
    public function destroy(Review $review)
    {
        $review->delete();

        return redirect()->route('admin.reviews.index')
            ->with('success', 'Review deleted successfully.');
    }

    /**
     * Bulk approve reviews
     */
    public function bulkApprove(Request $request)
    {
        $validated = $request->validate([
            'review_ids' => 'required|array',
            'review_ids.*' => 'exists:reviews,id',
        ]);

        Review::whereIn('id', $validated['review_ids'])->update(['is_approved' => true]);

        return back()->with('success', count($validated['review_ids']).' reviews approved.');
    }

    /**
     * Bulk delete reviews
     */
    public function bulkDelete(Request $request)
    {
        $validated = $request->validate([
            'review_ids' => 'required|array',
            'review_ids.*' => 'exists:reviews,id',
        ]);

        Review::whereIn('id', $validated['review_ids'])->delete();

        return back()->with('success', count($validated['review_ids']).' reviews deleted.');
    }
}
