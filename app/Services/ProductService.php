<?php

namespace App\Services;

use App\Models\Product;
use App\Repositories\Interfaces\ProductRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class ProductService
{
    protected const CACHE_KEY = 'products.v2';
    protected const CACHE_VERSION_KEY = 'products.v2.version';
    protected const CACHE_TTL = 3600; // 1 hour

    public function __construct(
        protected ProductRepositoryInterface $productRepository
    ) {}

    public function getAllProducts(int $perPage = 15): LengthAwarePaginator
    {
        $cacheKey = $this->versionedKey("page.{$perPage}." . request()->get('page', 1));
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($perPage) {
            return $this->productRepository->getActivePaginated($perPage);
        });
    }

    public function getFeaturedProducts(): Collection
    {
        return Cache::remember($this->versionedKey('featured'), self::CACHE_TTL, function () {
            return $this->productRepository->getFeatured();
        });
    }

    public function getProductById(int $id): Product
    {
        return $this->productRepository->findOrFail($id);
    }

    public function getProductWithDetails(int $id): ?Product
    {
        return $this->productRepository->findWithDetails($id);
    }

    public function getProductBySlug(string $slug): ?Product
    {
        return $this->productRepository->findBySlug($slug);
    }

    public function getNewProducts(): Collection
    {
        return Cache::remember($this->versionedKey('new'), self::CACHE_TTL, function () {
            return $this->productRepository->getNew();
        });
    }

    public function getBestsellers(): Collection
    {
        return Cache::remember($this->versionedKey('bestsellers'), self::CACHE_TTL, function () {
            return $this->productRepository->getBestsellers();
        });
    }

    public function getProductsByCategory(int $categoryId, int $perPage = 15): LengthAwarePaginator
    {
        $page = request()->get('page', 1);
        $cacheKey = $this->versionedKey("category.{$categoryId}.{$perPage}.{$page}");
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($categoryId, $perPage) {
            return $this->productRepository->getByCategoryPaginated($categoryId, $perPage);
        });
    }

    public function searchProducts(string $query): Collection
    {
        return $this->productRepository->search($query);
    }

    public function createProduct(array $data): Product
    {
        $product = $this->productRepository->create($data);
        $this->clearCache();
        return $product;
    }

    public function updateProduct(int $id, array $data): Product
    {
        $product = $this->productRepository->update($id, $data);
        $this->clearCache();
        return $product;
    }

    public function deleteProduct(int $id): bool
    {
        $result = $this->productRepository->delete($id);
        $this->clearCache();
        return $result;
    }

    public function updateStock(int $id, int $quantity): Product
    {
        $product = $this->getProductById($id);
        $product->update(['stock_quantity' => $quantity]);
        $this->clearCache();
        return $product->fresh();
    }

    public function clearProductCache(): void
    {
        $this->clearCache();
    }

    protected function clearCache(): void
    {
        if (Cache::has(self::CACHE_VERSION_KEY)) {
            Cache::increment(self::CACHE_VERSION_KEY);
            return;
        }

        Cache::forever(self::CACHE_VERSION_KEY, 2);
    }

    protected function versionedKey(string $suffix): string
    {
        $version = (int) Cache::get(self::CACHE_VERSION_KEY, 1);

        return self::CACHE_KEY . ".v{$version}.{$suffix}";
    }
}
