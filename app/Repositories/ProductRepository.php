<?php

namespace App\Repositories;

use App\Models\Product;
use App\Repositories\Interfaces\ProductRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductRepository extends BaseRepository implements ProductRepositoryInterface
{
    public function __construct(Product $model)
    {
        parent::__construct($model);
    }

    public function getActive(): Collection
    {
        return $this->model->active()
            ->with($this->listingRelations())
            ->withCount('variants')
            ->get();
    }

    public function getActivePaginated(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->active()
            ->with($this->listingRelations())
            ->withCount('variants')
            ->paginate($perPage);
    }

    public function getFeatured(): Collection
    {
        return $this->model->active()
            ->featured()
            ->with($this->listingRelations())
            ->withCount('variants')
            ->get();
    }

    public function getNew(): Collection
    {
        return $this->model->active()
            ->where('is_new', true)
            ->with($this->listingRelations())
            ->withCount('variants')
            ->limit(12)
            ->get();
    }

    public function getBestsellers(): Collection
    {
        return $this->model->active()
            ->where('is_bestseller', true)
            ->with($this->listingRelations())
            ->withCount('variants')
            ->limit(12)
            ->get();
    }

    /**
     * Used by the public product-detail endpoints — filters to active
     * products only, matching index(). Admin editing goes through separate
     * repository/controller calls, not this method, so this doesn't affect
     * the admin panel's ability to view/edit inactive products.
     */
    public function findBySlug(string $slug): ?Product
    {
        return $this->model
            ->with(['category', 'images', 'variants.product', 'variants.attributeValues.attribute'])
            ->active()
            ->where('slug', $slug)
            ->first();
    }

    public function findWithDetails(int $id): ?Product
    {
        return $this->model
            ->with(['category', 'images', 'variants.product', 'variants.attributeValues.attribute'])
            ->active()
            ->find($id);
    }

    public function findBySku(string $sku): ?Product
    {
        return $this->model->where('sku', $sku)->first();
    }

    public function getByCategory(int $categoryId): Collection
    {
        return $this->model->active()
            ->where(function ($q) use ($categoryId) {
                $q->where('category_id', $categoryId)
                  ->orWhereHas('categories', function ($q2) use ($categoryId) {
                      $q2->where('categories.id', $categoryId);
                  });
            })
            ->with($this->listingRelations())
            ->withCount('variants')
            ->get();
    }

    public function getByCategoryPaginated(int $categoryId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->active()
            ->where(function ($q) use ($categoryId) {
                $q->where('category_id', $categoryId)
                  ->orWhereHas('categories', function ($q2) use ($categoryId) {
                      $q2->where('categories.id', $categoryId);
                  });
            })
            ->with($this->listingRelations())
            ->withCount('variants')
            ->paginate($perPage);
    }

    public function search(string $query): Collection
    {
        return $this->model
            ->active()
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%")
                    ->orWhere('sku', 'like', "%{$query}%");
            })
            ->with($this->listingRelations())
            ->withCount('variants')
            ->get();
    }

    private function listingRelations(): array
    {
        return [
            'category',
            'images',
            'variants' => function ($query) {
                $query
                    ->where('is_active', true)
                    ->select(['id', 'product_id', 'sku', 'price_adjustment', 'purchase_price', 'regular_price', 'discounted_price', 'stock_quantity', 'is_active', 'image']);
            },
        ];
    }
}
