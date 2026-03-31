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
        return $this->model->active()->with(['category', 'images'])->get();
    }

    public function getActivePaginated(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->active()->with(['category', 'images'])->paginate($perPage);
    }

    public function getFeatured(): Collection
    {
        return $this->model->active()->featured()->with(['category', 'images'])->get();
    }

    public function getNew(): Collection
    {
        return $this->model->active()->where('is_new', true)->with(['category', 'images'])->limit(12)->get();
    }

    public function getBestsellers(): Collection
    {
        return $this->model->active()->where('is_bestseller', true)->with(['category', 'images'])->limit(12)->get();
    }

    public function findBySlug(string $slug): ?Product
    {
        return $this->model->with(['category', 'images', 'variants.attributeValues.attribute'])->where('slug', $slug)->first();
    }

    public function findWithDetails(int $id): ?Product
    {
        return $this->model->with(['category', 'images', 'variants.attributeValues.attribute'])->find($id);
    }

    public function findBySku(string $sku): ?Product
    {
        return $this->model->where('sku', $sku)->first();
    }

    public function getByCategory(int $categoryId): Collection
    {
        return $this->model->active()->where('category_id', $categoryId)->with(['category', 'images'])->get();
    }

    public function getByCategoryPaginated(int $categoryId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->active()
            ->where('category_id', $categoryId)
            ->with(['category', 'images'])
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
            ->with(['category', 'images'])
            ->get();
    }
}
