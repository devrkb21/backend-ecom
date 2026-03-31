<?php

namespace App\Services;

use App\Models\Category;
use App\Repositories\Interfaces\CategoryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class CategoryService
{
    protected const CACHE_KEY = 'categories';
    protected const CACHE_TTL = 3600; // 1 hour

    public function __construct(
        protected CategoryRepositoryInterface $categoryRepository
    ) {}

    public function getAllCategories(): Collection
    {
        return Cache::remember(self::CACHE_KEY . '.all', self::CACHE_TTL, function () {
            return $this->categoryRepository->getActiveOrdered();
        });
    }

    public function getMenuCategories(): Collection
    {
        return Cache::remember(self::CACHE_KEY . '.menu', self::CACHE_TTL, function () {
            return $this->categoryRepository->getMenuCategories();
        });
    }

    public function getParentCategories(): Collection
    {
        return Cache::remember(self::CACHE_KEY . '.parents', self::CACHE_TTL, function () {
            return $this->categoryRepository->getParentCategories();
        });
    }

    public function getCategoryById(int $id): Category
    {
        return $this->categoryRepository->findOrFail($id);
    }

    public function getCategoryBySlug(string $slug): ?Category
    {
        return $this->categoryRepository->findBySlug($slug);
    }

    public function getChildCategories(int $parentId): Collection
    {
        return Cache::remember(self::CACHE_KEY . ".children.{$parentId}", self::CACHE_TTL, function () use ($parentId) {
            return $this->categoryRepository->getChildCategories($parentId);
        });
    }

    public function createCategory(array $data): Category
    {
        $category = $this->categoryRepository->create($data);
        $this->clearCache();
        return $category;
    }

    public function updateCategory(int $id, array $data): Category
    {
        $category = $this->categoryRepository->update($id, $data);
        $this->clearCache();
        return $category;
    }

    public function deleteCategory(int $id): bool
    {
        $result = $this->categoryRepository->delete($id);
        $this->clearCache();
        return $result;
    }

    protected function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY . '.all');
        Cache::forget(self::CACHE_KEY . '.menu');
        Cache::forget(self::CACHE_KEY . '.parents');
        // Clear child category caches
        $categories = $this->categoryRepository->all();
        foreach ($categories as $category) {
            Cache::forget(self::CACHE_KEY . ".children.{$category->id}");
        }
    }
}
