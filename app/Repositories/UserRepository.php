<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->model->where('email', $email)->first();
    }

    public function getCustomers(): Collection
    {
        return $this->model->where('role', 'customer')->get();
    }

    public function getAdmins(): Collection
    {
        return $this->model->where('role', 'admin')->get();
    }
}
