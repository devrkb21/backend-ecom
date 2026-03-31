<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\StorePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Services\PaymentService;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService,
        protected OrderService $orderService
    ) {}

    public function show(Request $request, int $orderId): JsonResponse
    {
        $order = $this->orderService->getOrderById($orderId);

        // Users can only view payments for their own orders unless admin
        if (!$request->user()->isAdmin() && $order->user_id !== $request->user()->id) {
            return $this->errorResponse('Unauthorized', 403);
        }

        $payment = $this->paymentService->getPaymentByOrderId($orderId);

        if (!$payment) {
            return $this->errorResponse('Payment not found', 404);
        }

        return $this->successResponse(new PaymentResource($payment));
    }

    public function store(StorePaymentRequest $request): JsonResponse
    {
        $order = $this->orderService->getOrderById($request->order_id);

        // Users can only create payments for their own orders
        if ($order->user_id !== $request->user()->id) {
            return $this->errorResponse('Unauthorized', 403);
        }

        try {
            $payment = $this->paymentService->createPayment(
                $request->order_id,
                $request->payment_method,
                $request->payment_details ?? []
            );

            return $this->createdResponse(new PaymentResource($payment), 'Payment created');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function process(Request $request, int $paymentId): JsonResponse
    {
        try {
            $payment = $this->paymentService->getPaymentById($paymentId);

            if (!$payment) {
                return $this->errorResponse('Payment not found', 404);
            }

            $order = $payment->order;

            // Users can only process their own payments unless admin
            if (!$request->user()->isAdmin() && $order->user_id !== $request->user()->id) {
                return $this->errorResponse('Unauthorized', 403);
            }

            $payment = $this->paymentService->processPayment($paymentId);

            return $this->successResponse(new PaymentResource($payment), 'Payment processed');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function refund(Request $request, int $paymentId): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return $this->errorResponse('Unauthorized', 403);
        }

        try {
            $payment = $this->paymentService->refundPayment($paymentId);

            return $this->successResponse(new PaymentResource($payment), 'Payment refunded');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
}
