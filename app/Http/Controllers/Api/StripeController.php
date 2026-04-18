<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PaymentGateway;
use App\Models\User;
use App\Services\Payment\StripePaymentService;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StripeController extends Controller
{
    public function __construct(
        protected StripePaymentService $stripeService,
        protected OrderService $orderService
    ) {}

    /**
     * Get Stripe configuration for frontend
     */
    public function config(): JsonResponse
    {
        $gateway = PaymentGateway::findByCode('stripe');

        if (!$gateway || !$gateway->is_active) {
            return $this->errorResponse('Stripe is not available.', 404);
        }

        if (!$this->stripeService->isConfigured()) {
            return $this->errorResponse('Stripe is not configured.', 503);
        }

        return $this->successResponse([
            'public_key' => $this->stripeService->getPublicKey(),
            'test_mode' => $this->stripeService->isTestMode(),
        ]);
    }

    /**
     * Create a PaymentIntent for an order
     */
    public function createPaymentIntent(Request $request): JsonResponse
    {
        $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'guest_token' => ['nullable', 'string', 'max:255'],
            'save_payment_method' => ['nullable', 'boolean'],
        ]);

        try {
            $order = $this->orderService->getOrderById($request->order_id);
            $requestUser = $this->resolveApiUser($request);

            if (!$this->canAccessOrder($order, $requestUser, $request->input('guest_token'))) {
                return $this->errorResponse('Unauthorized.', 403);
            }

            // Verify order is using Stripe payment
            if ($order->payment_method !== 'stripe') {
                return $this->errorResponse('This order is not set for Stripe payment.', 400);
            }

            // Verify order is not already paid
            if ($order->payment_status === 'paid') {
                return $this->errorResponse('Order is already paid.', 400);
            }

            $savePaymentMethod = $requestUser ? (bool) $request->boolean('save_payment_method') : false;

            $stripeOptions = [
                'currency' => 'usd',
                'metadata' => [
                    'order_id' => (string) $order->id,
                    'save_payment_method_requested' => $savePaymentMethod ? '1' : '0',
                ],
            ];

            if ($requestUser) {
                $hasSavedMethods = $requestUser->savedPaymentMethods()->active()->exists();

                if ($savePaymentMethod || $hasSavedMethods || !empty($requestUser->stripe_customer_id)) {
                    $stripeOptions['customer_id'] = $this->stripeService->getOrCreateCustomerForUser($requestUser);
                }

                if ($savePaymentMethod) {
                    $stripeOptions['setup_future_usage'] = 'off_session';
                }
            }

            $result = $this->stripeService->createPaymentIntent($order, [
                ...$stripeOptions,
            ]);

            // Save payment intent ID to order
            $order->update([
                'transaction_id' => $result['payment_intent_id'],
                'payment_status' => 'awaiting',
            ]);

            return $this->successResponse($result, 'Payment intent created.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Confirm payment after frontend completes payment
     */
    public function confirmPayment(Request $request): JsonResponse
    {
        $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'payment_intent_id' => ['required', 'string'],
            'guest_token' => ['nullable', 'string', 'max:255'],
            'save_payment_method' => ['nullable', 'boolean'],
        ]);

        try {
            $order = $this->orderService->getOrderById($request->order_id);
            $requestUser = $this->resolveApiUser($request);

            if (!$this->canAccessOrder($order, $requestUser, $request->input('guest_token'))) {
                return $this->errorResponse('Unauthorized.', 403);
            }

            $result = $this->stripeService->confirmPayment($request->payment_intent_id);

            if ($result['succeeded']) {
                // Verify payment intent matches the order
                if ($order->transaction_id && $order->transaction_id !== $request->payment_intent_id) {
                    return $this->errorResponse('Payment intent does not match this order.', 400);
                }

                $order->update([
                    'payment_status' => 'paid',
                    'transaction_id' => $result['id'],
                ]);

                if ($requestUser && !empty($result['payment_method'])) {
                    try {
                        if ($request->boolean('save_payment_method')) {
                            $this->stripeService->savePaymentMethodForUser($requestUser, (string) $result['payment_method']);
                        } else {
                            $this->stripeService->markSavedPaymentMethodUsed($requestUser, (string) $result['payment_method']);
                        }
                    } catch (\Exception $saveError) {
                        Log::warning('Unable to persist Stripe saved payment method.', [
                            'order_id' => $order->id,
                            'user_id' => $requestUser->id,
                            'payment_method' => $result['payment_method'],
                            'error' => $saveError->getMessage(),
                        ]);
                    }
                }

                // Update order status to processing
                $this->orderService->updateOrderStatus($order->id, 'processing');

                return $this->successResponse([
                    'status' => 'succeeded',
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'saved_payment_method' => $requestUser ? (bool) $request->boolean('save_payment_method') : false,
                ], 'Payment successful!');
            }

            return $this->successResponse([
                'status' => $result['status'],
                'message' => 'Payment is ' . $result['status'],
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Handle Stripe webhooks
     */
    public function webhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        try {
            $event = $this->stripeService->verifyWebhookSignature($payload, $sigHeader);

            switch ($event->type) {
                case 'payment_intent.succeeded':
                    $this->handlePaymentIntentSucceeded($event->data->object);
                    break;

                case 'payment_intent.payment_failed':
                    $this->handlePaymentIntentFailed($event->data->object);
                    break;

                case 'charge.refunded':
                    $this->handleChargeRefunded($event->data->object);
                    break;
            }

            return response()->json(['status' => 'success']);
        } catch (\UnexpectedValueException $e) {
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return response()->json(['error' => 'Invalid signature'], 400);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Handle successful payment
     */
    protected function handlePaymentIntentSucceeded($paymentIntent): void
    {
        $orderId = $paymentIntent->metadata->order_id ?? null;

        if ($orderId) {
            $order = Order::find($orderId);
            if ($order && $order->payment_status !== 'paid') {
                $order->update([
                    'payment_status' => 'paid',
                    'transaction_id' => $paymentIntent->id,
                ]);

                if ($order->status === 'pending') {
                    $order->update(['status' => 'processing']);
                }
            }
        }
    }

    /**
     * Handle failed payment
     */
    protected function handlePaymentIntentFailed($paymentIntent): void
    {
        $orderId = $paymentIntent->metadata->order_id ?? null;

        if ($orderId) {
            $order = Order::find($orderId);
            if ($order) {
                $order->update(['payment_status' => 'failed']);
            }
        }
    }

    /**
     * Handle refund
     */
    protected function handleChargeRefunded($charge): void
    {
        $paymentIntentId = $charge->payment_intent;

        if ($paymentIntentId) {
            $order = Order::where('transaction_id', $paymentIntentId)->first();
            if ($order) {
                $order->update(['payment_status' => 'refunded']);
            }
        }
    }

    protected function resolveApiUser(Request $request): ?User
    {
        return $request->user('sanctum') ?? $request->user();
    }

    protected function canAccessOrder(Order $order, ?User $requestUser, ?string $guestToken): bool
    {
        if ($requestUser) {
            return $requestUser->isAdmin() || (int) $order->user_id === (int) $requestUser->id;
        }

        return $order->hasValidGuestAccessToken($guestToken);
    }
}
