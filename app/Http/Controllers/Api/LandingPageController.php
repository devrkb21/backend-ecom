<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LandingPage;
use Illuminate\Http\JsonResponse;

class LandingPageController extends Controller
{
    public function showBySlug(string $slug): JsonResponse
    {
        $landingPage = LandingPage::where('slug', $slug)
            ->where('is_active', true)
            ->with([
                'product' => function ($query) {
                    $query->with(['variants.attributeValues', 'images']);
                }
            ])
            ->first();

        if (!$landingPage) {
            return response()->json([
                'success' => false,
                'message' => 'Landing page not found'
            ], 404);
        }

        // Increment click count asynchronously/silently
        $landingPage->increment('views_count');

        return response()->json([
            'success' => true,
            'data' => $landingPage
        ]);
    }
}
