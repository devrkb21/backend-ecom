<?php

namespace App\Services;

use App\Models\Payment;
use App\Repositories\Interfaces\OrderRepositoryInterface;
use App\Repositories\Interfaces\PaymentRepositoryInterface;

/**
 * Read-only lookup for payment records created by the real gateway
 * integrations (App\Services\Payment\StripePaymentService,
 * App\Services\Payment\BkashPaymentService) or COD checkout.
 *
 * This service previously also exposed createPayment()/processPayment()/
 * refundPayment() backed by a fake "simulatePaymentGateway()" that always
 * returned true — a live, authenticated endpoint that let any user mark
 * their own order paid without any money moving. That path has been
 * removed entirely; it was never used by the real checkout flow (which
 * only ever creates payments via Stripe/bKash/COD) or by the frontend.
 */
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
}
