<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Order;
use App\Repositories\Interfaces\PaymentRepositoryInterface;
use App\Repositories\Interfaces\OrderRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentService
{
    public function __construct(
        protected PaymentRepositoryInterface $paymentRepository,
        protected OrderRepositoryInterface $orderRepository
    ) {}

    public function getPaymentByOrderId(int $orderId): ?Payment
    {
        return $this->paymentRepository->getByOrderId($orderId);
    }

    public function getPaymentById(int $paymentId): ?Payment
    {
        return $this->paymentRepository->find($paymentId);
    }

    public function createPayment(int $orderId, string $paymentMethod, array $paymentDetails = []): Payment
    {
        return DB::transaction(function () use ($orderId, $paymentMethod, $paymentDetails) {
            $order = $this->orderRepository->findOrFail($orderId);

            // Check if payment already exists
            $existingPayment = $this->paymentRepository->getByOrderId($orderId);
            if ($existingPayment && $existingPayment->status === 'completed') {
                throw new \Exception('Order has already been paid.');
            }

            if ($existingPayment) {
                // Update existing payment
                return $this->paymentRepository->update($existingPayment->id, [
                    'payment_method' => $paymentMethod,
                    'payment_details' => $paymentDetails,
                    'status' => 'pending',
                ]);
            }

            return $this->paymentRepository->create([
                'order_id' => $orderId,
                'payment_method' => $paymentMethod,
                'amount' => $order->total,
                'currency' => 'BDT',
                'status' => 'pending',
                'payment_details' => $paymentDetails,
            ]);
        });
    }

    public function processPayment(int $paymentId): Payment
    {
        return DB::transaction(function () use ($paymentId) {
            $payment = $this->paymentRepository->findOrFail($paymentId);

            if ($payment->status !== 'pending') {
                throw new \Exception('Payment cannot be processed.');
            }

            // Simulate payment processing
            // In a real application, integrate with payment gateway here
            $success = $this->simulatePaymentGateway($payment);

            if ($success) {
                $transactionId = 'TXN-' . strtoupper(Str::random(16));
                $payment = $this->paymentRepository->updateStatus($paymentId, 'completed', $transactionId);

                // Update order status to processing
                $this->orderRepository->updateStatus($payment->order_id, 'processing');
            } else {
                $payment = $this->paymentRepository->updateStatus($paymentId, 'failed');
            }

            return $payment;
        });
    }

    public function refundPayment(int $paymentId): Payment
    {
        return DB::transaction(function () use ($paymentId) {
            $payment = $this->paymentRepository->findOrFail($paymentId);

            if ($payment->status !== 'completed') {
                throw new \Exception('Only completed payments can be refunded.');
            }

            // Simulate refund processing
            // In a real application, integrate with payment gateway here
            $payment = $this->paymentRepository->updateStatus($paymentId, 'refunded');

            // Cancel the order and restore stock
            $order = $payment->order;
            if ($order->canBeCancelled()) {
                foreach ($order->items as $item) {
                    $item->product->incrementStock($item->quantity);
                }
                $this->orderRepository->updateStatus($order->id, 'cancelled');
            }

            return $payment;
        });
    }

    protected function simulatePaymentGateway(Payment $payment): bool
    {
        // Simulate payment gateway processing
        // In production, integrate with Stripe, PayPal, etc.
        // For MVP, always return success
        return true;
    }
}
