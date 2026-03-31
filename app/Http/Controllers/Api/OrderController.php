<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Requests\Order\UpdateOrderStatusRequest;
use App\Http\Resources\OrderResource;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        protected OrderService $orderService
    ) {}

    public function index(Request $request): JsonResponse
    {
        if ($request->user()->isAdmin()) {
            $orders = $this->orderService->getAllOrders($this->perPage());
        } else {
            $orders = $this->orderService->getUserOrders($request->user()->id, $this->perPage());
        }

        return $this->successResponse(OrderResource::collection($orders)->response()->getData(true));
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $order = $this->orderService->getOrderById($id);

        // Users can only view their own orders unless admin
        if (!$request->user()->isAdmin() && $order->user_id !== $request->user()->id) {
            return $this->errorResponse('Unauthorized', 403);
        }

        return $this->successResponse(new OrderResource($order));
    }

    public function showByNumber(Request $request, string $orderNumber): JsonResponse
    {
        $order = $this->orderService->getOrderByNumber($orderNumber);

        if (!$order) {
            return $this->errorResponse('Order not found', 404);
        }

        // Users can only view their own orders unless admin
        if (!$request->user()->isAdmin() && $order->user_id !== $request->user()->id) {
            return $this->errorResponse('Unauthorized', 403);
        }

        return $this->successResponse(new OrderResource($order));
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        try {
            $order = $this->orderService->createOrderFromCart(
                $request->user()->id,
                $request->validated()
            );

            return $this->createdResponse(new OrderResource($order), 'Order created successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function updateStatus(UpdateOrderStatusRequest $request, int $id): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return $this->errorResponse('Unauthorized', 403);
        }

        try {
            $order = $this->orderService->updateOrderStatus($id, $request->status);

            return $this->successResponse(new OrderResource($order), 'Order status updated');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        try {
            $order = $this->orderService->cancelOrder($id, $request->user()->id);

            return $this->successResponse(new OrderResource($order), 'Order cancelled');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function byStatus(Request $request, string $status): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return $this->errorResponse('Unauthorized', 403);
        }

        $orders = $this->orderService->getOrdersByStatus($status);

        return $this->successResponse(OrderResource::collection($orders));
    }
}
