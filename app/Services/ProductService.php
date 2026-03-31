<?php

namespace App\Services;

use App\Models\Product;
use App\Repositories\Interfaces\ProductRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class ProductService
{
    protected const CACHE_KEY = 'products';
    protected const CACHE_TTL = 3600; // 1 hour

    public function __construct(
        protected ProductRepositoryInterface $productRepository
    ) {}

    public function getAllProducts(int $perPage = 15): LengthAwarePaginator
    {
        $cacheKey = self::CACHE_KEY . ".page.{$perPage}." . request()->get('page', 1);
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($perPage) {
            return $this->productRepository->getActivePaginated($perPage);
        });
    }

    public function getFeaturedProducts(): Collection
    {
        return Cache::remember(self::CACHE_KEY . '.featured', self::CACHE_TTL, function () {
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
        return Cache::remember(self::CACHE_KEY . '.new', self::CACHE_TTL, function () {
            return $this->productRepository->getNew();
        });
    }

    public function getBestsellers(): Collection
    {
        return Cache::remember(self::CACHE_KEY . '.bestsellers', self::CACHE_TTL, function () {
            return $this->productRepository->getBestsellers();
        });
    }

    public function getProductsByCategory(int $categoryId, int $perPage = 15): LengthAwarePaginator
    {
        $page = request()->get('page', 1);
        $cacheKey = self::CACHE_KEY . ".category.{$categoryId}.{$perPage}.{$page}";
        
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
        // Clear all product-related cache
        $keys = Cache::get(self::CACHE_KEY . '.keys', []);
        foreach ($keys as $key) {
            Cache::forget($key);
        }
        Cache::forget(self::CACHE_KEY . '.featured');
        
        // Use Redis KEYS command to clear paginated caches (if using Redis)
        try {
            $redis = Cache::getRedis();
            $pattern = config('cache.prefix') . ':' . self::CACHE_KEY . '*';
            $keys = $redis->keys($pattern);
            foreach ($keys as $key) {
                $redis->del($key);
            }
        } catch (\Exception $e) {
            // Fallback: Cache will expire naturally
        }
    }
}
