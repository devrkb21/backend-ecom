<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Services\PaymentService;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Read-only payment lookup. Actual payment creation/processing/refunding
 * happens exclusively through the real gateway integrations
 * (Api\StripeController, Api\BkashController, Admin RefundService) or via
 * COD checkout — never through this controller.
 */
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
}
