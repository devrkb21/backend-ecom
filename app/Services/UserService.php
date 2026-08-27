<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function __construct(
        protected UserRepositoryInterface $userRepository
    ) {}

    public function getAllUsers(): Collection
    {
        return $this->userRepository->all();
    }

    public function getPaginatedUsers(int $perPage = 15): LengthAwarePaginator
    {
        return $this->userRepository->paginate($perPage);
    }

    public function getUserById(int $id): User
    {
        return $this->userRepository->findOrFail($id);
    }

    public function updateUser(int $id, array $data): User
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        return $this->userRepository->update($id, $data);
    }

    public function deleteUser(int $id): bool
    {
        return $this->userRepository->delete($id);
    }

    public function getCustomers(): Collection
    {
        return $this->userRepository->getCustomers();
    }

    public function createUser(array $data): User
    {
        $data['password'] = Hash::make($data['password']);
        $data['role'] = $data['role'] ?? 'customer';

        return $this->userRepository->create($data);
    }

    public function toggleUserStatus(int $id): User
    {
        $user = User::withTrashed()->findOrFail($id);

        if ($user->trashed()) {
            $user->restore();
        } else {
            $user->delete();
        }

        return $user->fresh();
    }
}
