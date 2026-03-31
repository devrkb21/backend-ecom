<?php

namespace App\Repositories\Interfaces;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface ProductRepositoryInterface extends BaseRepositoryInterface
{
    public function getActive(): Collection;

    public function getActivePaginated(int $perPage = 15): LengthAwarePaginator;

    public function getFeatured(): Collection;

    public function getNew(): Collection;

    public function getBestsellers(): Collection;

    public function findBySlug(string $slug): ?Product;

    public function findBySku(string $sku): ?Product;

    public function findWithDetails(int $id): ?Product;

    public function getByCategory(int $categoryId): Collection;

    public function getByCategoryPaginated(int $categoryId, int $perPage = 15): LengthAwarePaginator;

    public function search(string $query): Collection;
}
