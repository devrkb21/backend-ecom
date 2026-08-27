<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LandingPage;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class LandingPageController extends Controller
{
    public function showBySlug(string $slug): JsonResponse
    {
        $landingPage = LandingPage::where('slug', $slug)
            ->where('is_active', true)
            ->with([
                'product' => function ($query) {
                    $query->with(['variants.attributeValues.attribute', 'images']);
                },
            ])
            ->first();

        if (! $landingPage) {
            return response()->json([
                'success' => false,
                'message' => 'Landing page not found',
            ], 404);
        }

        // Increment view count
        $landingPage->increment('views_count');

        // Load all linked products (via product_ids JSON array)
        $productIds = $landingPage->product_ids ?? ($landingPage->product_id ? [$landingPage->product_id] : []);
        $linkedProducts = [];
        if (! empty($productIds)) {
            $linkedProducts = Product::whereIn('id', $productIds)
                ->where('is_active', true)
                ->with(['variants.attributeValues.attribute', 'images'])
                ->get()
                ->map(function ($p) use ($productIds) {
                    // Preserve the order of selection
                    $p->setAttribute('_sort_order', array_search($p->id, $productIds));

                    return $p;
                })
                ->sortBy('_sort_order')
                ->values()
                ->toArray();
        }

        $data = $landingPage->toArray();
        $data['linked_products'] = $linkedProducts;

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
