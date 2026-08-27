<?php

namespace App\Repositories\Interfaces;

use App\Models\Order;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface OrderRepositoryInterface extends BaseRepositoryInterface
{
    public function getByUserId(int $userId): Collection;

    public function getByUserIdPaginated(int $userId, int $perPage = 15): LengthAwarePaginator;

    public function paginateCreatedBefore(\DateTimeInterface $before, int $perPage = 15): LengthAwarePaginator;

    public function findByOrderNumber(string $orderNumber): ?Order;

    public function getByStatus(string $status): Collection;

    public function updateStatus(int $orderId, string $status): Order;

    public function createWithItems(array $orderData, array $items): Order;
}
