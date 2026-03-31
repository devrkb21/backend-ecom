<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PaymentGateway;
use App\Services\Payment\BkashPaymentService;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class BkashController extends Controller
{
    public function __construct(
        protected BkashPaymentService $bkashService,
        protected OrderService $orderService
    ) {}

    /**
     * Get bKash configuration for frontend
     */
    public function config(): JsonResponse
    {
        $gateway = PaymentGateway::findByCode('bkash');

        if (!$gateway || !$gateway->is_active) {
            return $this->errorResponse('bKash is not available.', 404);
        }

        if (!$this->bkashService->isConfigured()) {
            return $this->errorResponse('bKash is not configured.', 503);
        }

        return $this->successResponse([
            'available' => true,
            'sandbox_mode' => $this->bkashService->isSandboxMode(),
            'currency' => 'BDT',
        ]);
    }

    /**
     * Create bKash payment for an order
     */
    public function createPayment(Request $request): JsonResponse
    {
        $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
        ]);

        try {
            $order = $this->orderService->getOrderById($request->order_id);

            // Verify user owns the order
            if ($order->user_id !== $request->user()->id) {
                return $this->errorResponse('Unauthorized.', 403);
            }

            // Verify order is using bKash payment
            if ($order->payment_method !== 'bkash') {
                return $this->errorResponse('This order is not set for bKash payment.', 400);
            }

            // Verify order is not already paid
            if ($order->payment_status === 'paid') {
                return $this->errorResponse('Order is already paid.', 400);
            }

            $result = $this->bkashService->createPayment($order);

            if ($result['success']) {
                // Save bKash paymentID and set awaiting status
                $order->update([
                    'bkash_payment_id' => $result['payment_id'],
                    'transaction_id' => $result['payment_id'],
                    'payment_status' => 'awaiting',
                ]);

                return $this->successResponse([
                    'payment_id' => $result['payment_id'],
                    'bkash_url' => $result['bkash_url'],
                    'order_id' => $order->id,
                    'amount' => $result['amount'],
                ], 'Redirect to bKash to complete payment.');
            }

            return $this->errorResponse($result['message'], 400);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Handle bKash callback
     */
    public function callback(Request $request): RedirectResponse
    {
        $paymentId = $request->get('paymentID');
        $status = $request->get('status');

        // Find order by transaction_id (which stores bkash paymentID)
        $order = Order::where('transaction_id', $paymentId)->first();

        if (!$order) {
            return redirect()->to(config('app.frontend_url', '/') . '/checkout/failed?error=order_not_found');
        }

        $frontendUrl = config('app.frontend_url', '');

        if ($status === 'success') {
            try {
                $result = $this->bkashService->executePayment($paymentId);

                if ($result['success']) {
                    $order->update([
                        'payment_status' => 'paid',
                        'transaction_id' => $result['transaction_id'],
                    ]);

                    // Update order status to processing
                    if ($order->status === 'pending') {
                        $order->update(['status' => 'processing']);
                    }

                    return redirect()->to($frontendUrl . '/checkout/success?order=' . $order->order_number);
                }

                $order->update(['payment_status' => 'failed']);
                return redirect()->to($frontendUrl . '/checkout/failed?order=' . $order->order_number . '&error=' . urlencode($result['message']));
            } catch (\Exception $e) {
                $order->update(['payment_status' => 'failed']);
                return redirect()->to($frontendUrl . '/checkout/failed?order=' . $order->order_number . '&error=' . urlencode($e->getMessage()));
            }
        } elseif ($status === 'cancel') {
            $order->update(['payment_status' => 'pending']);
            return redirect()->to($frontendUrl . '/checkout/cancelled?order=' . $order->order_number);
        } else {
            $order->update(['payment_status' => 'failed']);
            return redirect()->to($frontendUrl . '/checkout/failed?order=' . $order->order_number);
        }
    }

    /**
     * Check payment status (for polling)
     */
    public function checkStatus(Request $request): JsonResponse
    {
        $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
        ]);

        try {
            $order = $this->orderService->getOrderById($request->order_id);

            // Verify user owns the order
            if ($order->user_id !== $request->user()->id) {
                return $this->errorResponse('Unauthorized.', 403);
            }

            if (!$order->transaction_id) {
                return $this->errorResponse('No payment initiated for this order.', 400);
            }

            // If already paid, return success
            if ($order->payment_status === 'paid') {
                return $this->successResponse([
                    'status' => 'paid',
                    'order_number' => $order->order_number,
                    'transaction_id' => $order->transaction_id,
                ]);
            }

            // Query bKash for current status
            $result = $this->bkashService->queryPayment($order->transaction_id);

            return $this->successResponse([
                'status' => $order->payment_status,
                'bkash_status' => $result['status'],
                'order_number' => $order->order_number,
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Process refund (Admin only)
     */
    public function refund(Request $request): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return $this->errorResponse('Unauthorized.', 403);
        }

        $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'amount' => ['nullable', 'numeric', 'min:1'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $order = $this->orderService->getOrderById($request->order_id);

            if ($order->payment_method !== 'bkash') {
                return $this->errorResponse('This order was not paid via bKash.', 400);
            }

            if ($order->payment_status !== 'paid') {
                return $this->errorResponse('Only paid orders can be refunded.', 400);
            }

            $paymentId = $order->bkash_payment_id;
            $transactionId = $order->transaction_id;

            if (!$paymentId || !$transactionId) {
                return $this->errorResponse('Missing bKash payment or transaction ID for this order.', 400);
            }

            $amount = $request->amount ?? (float) $order->total;
            $reason = $request->reason ?? 'Customer refund request';

            $result = $this->bkashService->refund($paymentId, $transactionId, $amount, $reason);

            if ($result['success']) {
                $order->update([
                    'payment_status' => $amount >= (float) $order->total ? 'refunded' : 'partially_refunded',
                ]);

                return $this->successResponse($result, 'Refund processed successfully.');
            }

            return $this->errorResponse($result['message'] ?? 'Refund failed.', 400);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
}
