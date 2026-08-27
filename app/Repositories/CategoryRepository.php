<?php

namespace App\Repositories;

use App\Models\Category;
use App\Repositories\Interfaces\CategoryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class CategoryRepository extends BaseRepository implements CategoryRepositoryInterface
{
    public function __construct(Category $model)
    {
        parent::__construct($model);
    }

    public function getActive(): Collection
    {
        return $this->model->active()->get();
    }

    public function getActiveOrdered(): Collection
    {
        return $this->model->active()->ordered()->get();
    }

    public function getMenuCategories(): Collection
    {
        return $this->model->with('children')->active()->menu()->ordered()->get();
    }

    public function findBySlug(string $slug): ?Category
    {
        return $this->model->where('slug', $slug)->first();
    }

    public function getParentCategories(): Collection
    {
        return $this->model->whereNull('parent_id')->active()->ordered()->get();
    }

    public function getChildCategories(int $parentId): Collection
    {
        return $this->model->where('parent_id', $parentId)->active()->ordered()->get();
    }
}
