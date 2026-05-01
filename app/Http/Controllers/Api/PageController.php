<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\JsonResponse;

class PageController extends Controller
{
    public function show(string $slug): JsonResponse
    {
        $page = Page::where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if (!$page) {
            return response()->json([
                'success' => false,
                'message' => 'Page not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $page
        ]);
    }

    public function index(): JsonResponse
    {
        $pages = Page::where('is_active', true)
            ->select('id', 'title', 'slug')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $pages
        ]);
    }
}
