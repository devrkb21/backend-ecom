<?php

namespace App\Repositories\Interfaces;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

interface CategoryRepositoryInterface extends BaseRepositoryInterface
{
    public function getActive(): Collection;

    public function getActiveOrdered(): Collection;

    public function getMenuCategories(): Collection;

    public function findBySlug(string $slug): ?Category;

    public function getParentCategories(): Collection;

    public function getChildCategories(int $parentId): Collection;
}
