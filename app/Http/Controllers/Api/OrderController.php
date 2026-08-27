<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Requests\Order\UpdateOrderStatusRequest;
use App\Http\Resources\OrderResource;
use App\Jobs\CheckCourierHistoryJob;
use App\Models\Order;
use App\Services\FraudDetectionService;
use App\Services\LicenseService;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class OrderController extends Controller
{
    public function __construct(
        protected OrderService $orderService,
        protected FraudDetectionService $fraudDetectionService,
        protected LicenseService $licenseService
    ) {}

    protected function licenseExpiredResponse(): JsonResponse
    {
        return $this->errorResponse(
            'This order was placed after your license expired. Renew your license to manage new orders.',
            403,
        );
    }

    public function index(Request $request): JsonResponse
    {
        if ($request->user()->isAdmin()) {
            // Orders placed after the license expired stay invisible to
            // admin (storefront checkout is unaffected) until renewal.
            $orders = $this->orderService->getAllOrders($this->perPage(), $this->licenseService->expiredSince());
        } else {
            $orders = $this->orderService->getUserOrders($request->user()->id, $this->perPage());
        }

        return $this->successResponse(OrderResource::collection($orders)->response()->getData(true));
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $order = $this->orderService->getOrderById($id);
        $isAdmin = $request->user()->isAdmin();

        // Users can only view their own orders unless admin
        if (!$isAdmin && $order->user_id !== $request->user()->id) {
            return $this->errorResponse('Unauthorized', 403);
        }

        if ($isAdmin && $this->licenseService->isOrderLocked($order)) {
            return $this->licenseExpiredResponse();
        }

        $order->loadMissing([
            'items.product',
            'items.variant.attributeValues.attribute',
            'payment',
            'user',
            'trackingHistory',
            'shippingDivision',
            'shippingDistrict',
            'shippingUpazila',
            'shippingUnion',
        ]);

        return $this->successResponse(new OrderResource($order));
    }

    public function showByNumber(Request $request, string $orderNumber): JsonResponse
    {
        $normalizedOrderNumber = $this->normalizeOrderNumber($orderNumber);

        if (!$this->isValidOrderNumber($normalizedOrderNumber)) {
            return $this->errorResponse('Order not found', 404);
        }

        $order = $this->orderService->getOrderByNumber($normalizedOrderNumber);

        if (!$order) {
            return $this->errorResponse('Order not found', 404);
        }

        $requestUser = $request->user('sanctum') ?? $request->user();

        if (!$requestUser) {
            return $this->successResponse($this->buildOrderSummary($order));
        }

        if (!$requestUser->isAdmin() && $order->user_id !== $requestUser->id) {
            return $this->errorResponse('Unauthorized', 403);
        }

        $order->loadMissing([
            'items.product',
            'items.variant.attributeValues.attribute',
            'payment',
            'trackingHistory',
            'shippingDivision',
            'shippingDistrict',
            'shippingUpazila',
            'shippingUnion',
        ]);

        return $this->successResponse(new OrderResource($order));
    }

    public function showByNumberForGuest(Request $request, string $orderNumber): JsonResponse
    {
        $normalizedOrderNumber = $this->normalizeOrderNumber($orderNumber);

        if (!$this->isValidOrderNumber($normalizedOrderNumber)) {
            return $this->errorResponse('Order not found', 404);
        }

        $order = $this->orderService->getOrderByNumber($normalizedOrderNumber);

        if (!$order) {
            return $this->errorResponse('Order not found', 404);
        }

        $guestToken = trim((string) $request->query('guest_token', ''));

        if (!$order->hasValidGuestAccessToken($guestToken)) {
            return $this->errorResponse('Unauthorized', 403);
        }

        $order->loadMissing([
            'items.product',
            'items.variant.attributeValues.attribute',
            'payment',
            'trackingHistory',
            'shippingDivision',
            'shippingDistrict',
            'shippingUpazila',
            'shippingUnion',
        ]);

        return $this->successResponse(new OrderResource($order));
    }

    public function paymentSummary(Request $request, int $id): JsonResponse
    {
        try {
            $order = $this->orderService->getOrderById($id);
            $requestUser = $request->user('sanctum') ?? $request->user();

            if ($requestUser) {
                if (!$requestUser->isAdmin() && $order->user_id !== $requestUser->id) {
                    return $this->errorResponse('Unauthorized', 403);
                }
            } else {
                $guestToken = trim((string) $request->query('guest_token', ''));
                if (!$order->hasValidGuestAccessToken($guestToken)) {
                    return $this->errorResponse('Unauthorized', 403);
                }
            }

            $order->loadMissing([
                'items.product',
                'items.variant.attributeValues.attribute',
                'payment',
                'user',
                'trackingHistory',
                'shippingDivision',
                'shippingDistrict',
                'shippingUpazila',
                'shippingUnion',
            ]);

            return $this->successResponse(new OrderResource($order));
        } catch (ModelNotFoundException) {
            return $this->errorResponse('Order not found', 404);
        }
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        try {
            $user = $request->user('sanctum') ?? auth()->user();
            $checkoutSessionId = trim((string) $request->header('X-Session-ID', ''));

            // Fraud block check
            $fraudPhone = trim((string) ($request->input('shipping_phone') ?? ''));
            $fraudEmail = trim((string) ($request->input('shipping_email') ?? $user?->email ?? ''));
            $fraudIp = $request->ip();
            $fraudDevice = $request->userAgent();

            $fraudResult = $this->fraudDetectionService->checkBlocklist($fraudPhone, $fraudEmail, $fraudIp, $fraudDevice);
            if ($fraudResult['blocked']) {
                $defaultMessage = 'Your order could not be processed. Please contact support.';
                $message = $fraudResult['message'] ?? $defaultMessage;

                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'fraud_blocked' => true,
                    'fraud_message' => $message,
                ], 403);
            }

            $velocityResult = $this->fraudDetectionService->checkVelocity($fraudPhone, $fraudIp, $fraudDevice);
            if ($velocityResult['exceeded']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Too many orders placed recently. Please try again later or contact support.',
                    'fraud_blocked' => true,
                    'fraud_message' => 'Too many orders placed recently. Please try again later or contact support.',
                ], 429);
            }

            $validatedData = $request->validated();
            $validatedData['checkout_fields'] = $validatedData['checkout_fields'] ?? [];
            $validatedData['checkout_fields']['device_ip'] = $fraudIp;
            $validatedData['checkout_fields']['device_user_agent'] = $fraudDevice;

            $order = $this->orderService->createOrderFromCart(
                $user?->id,
                $validatedData,
                $checkoutSessionId !== '' ? $checkoutSessionId : null
            );

            $this->fraudDetectionService->tagOrder($order, $fraudIp, $fraudDevice, $fraudPhone);
            CheckCourierHistoryJob::dispatch($order->id);

            return $this->createdResponse([
                'id' => $order->id,
                'order_number' => $order->order_number,
                'guest_access_token' => $user ? null : $order->plainGuestAccessToken,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'payment_method' => $order->payment_method,
                'total' => (float) $order->total,
            ], 'Order created successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function updateStatus(UpdateOrderStatusRequest $request, int $id): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return $this->errorResponse('Unauthorized', 403);
        }

        if ($this->licenseService->isOrderLocked($this->orderService->getOrderById($id))) {
            return $this->licenseExpiredResponse();
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

        $orders = $this->orderService->getOrdersByStatus($status)
            ->reject(fn (Order $order) => $this->licenseService->isOrderLocked($order))
            ->values();

        return $this->successResponse(OrderResource::collection($orders));
    }

    protected function normalizeOrderNumber(string $orderNumber): string
    {
        return strtoupper(trim($orderNumber));
    }

    protected function isValidOrderNumber(string $orderNumber): bool
    {
        if ($orderNumber === '' || strlen($orderNumber) > 64) {
            return false;
        }

        return preg_match('/^[A-Z0-9][A-Z0-9._-]*$/', $orderNumber) === 1;
    }

    protected function buildOrderSummary(Order $order): array
    {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'payment_method' => $order->payment_method,
            'total' => (float) $order->total,
        ];
    }
}
