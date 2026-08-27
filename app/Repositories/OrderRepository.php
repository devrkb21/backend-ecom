<?php

namespace App\Repositories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Repositories\Interfaces\OrderRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class OrderRepository extends BaseRepository implements OrderRepositoryInterface
{
    public function __construct(Order $model)
    {
        parent::__construct($model);
    }

    public function getByUserId(int $userId): Collection
    {
        return $this->model
            ->with(['items.product', 'items.variant.attributeValues.attribute', 'payment'])
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->get();
    }

    public function getByUserIdPaginated(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->with(['items.product', 'items.variant.attributeValues.attribute', 'payment'])
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function paginateCreatedBefore(\DateTimeInterface $before, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->where('created_at', '<=', $before)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function findByOrderNumber(string $orderNumber): ?Order
    {
        $normalized = strtolower(trim($orderNumber));

        return $this->model
            ->with(['items.product', 'items.variant.attributeValues.attribute', 'payment'])
            ->whereRaw('LOWER(order_number) = ?', [$normalized])
            ->first();
    }

    public function getByStatus(string $status): Collection
    {
        return $this->model
            ->with(['items.product', 'items.variant.attributeValues.attribute', 'payment', 'user'])
            ->byStatus($status)
            ->orderByDesc('created_at')
            ->get();
    }

    public function updateStatus(int $orderId, string $status): Order
    {
        $order = $this->findOrFail($orderId);
        $order->update(['status' => $status]);

        return $order->fresh(['items.product', 'items.variant.attributeValues.attribute', 'payment']);
    }

    public function createWithItems(array $orderData, array $items): Order
    {
        return DB::transaction(function () use ($orderData, $items) {
            $order = $this->model->create($orderData);

            foreach ($items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $item['product_variant_id'] ?? null,
                    'product_name' => $item['product_name'],
                    'product_sku' => $item['product_sku'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'total' => $item['price'] * $item['quantity'],
                ]);
            }

            return $order->load(['items.product', 'items.variant.attributeValues.attribute', 'payment']);
        });
    }
}
