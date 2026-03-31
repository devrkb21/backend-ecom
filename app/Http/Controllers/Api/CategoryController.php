<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function __construct(
        protected CategoryService $categoryService
    ) {}

    public function index(): JsonResponse
    {
        $categories = $this->categoryService->getAllCategories();

        return $this->successResponse(CategoryResource::collection($categories));
    }

    public function menu(): JsonResponse
    {
        $categories = $this->categoryService->getMenuCategories();

        return $this->successResponse(CategoryResource::collection($categories));
    }

    public function show(int $id): JsonResponse
    {
        $category = $this->categoryService->getCategoryById($id);

        return $this->successResponse(new CategoryResource($category));
    }

    public function showBySlug(string $slug): JsonResponse
    {
        $category = $this->categoryService->getCategoryBySlug($slug);

        if (!$category) {
            return $this->errorResponse('Category not found', 404);
        }

        return $this->successResponse(new CategoryResource($category));
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = $this->categoryService->createCategory($request->validated());

        return $this->createdResponse(new CategoryResource($category), 'Category created successfully');
    }

    public function update(UpdateCategoryRequest $request, int $id): JsonResponse
    {
        $category = $this->categoryService->updateCategory($id, $request->validated());

        return $this->successResponse(new CategoryResource($category), 'Category updated successfully');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return $this->errorResponse('Unauthorized', 403);
        }

        $this->categoryService->deleteCategory($id);

        return $this->successResponse(null, 'Category deleted successfully');
    }

    public function children(int $id): JsonResponse
    {
        $children = $this->categoryService->getChildCategories($id);

        return $this->successResponse(CategoryResource::collection($children));
    }
}
