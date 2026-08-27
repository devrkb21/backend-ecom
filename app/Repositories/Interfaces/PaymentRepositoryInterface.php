<?php

namespace App\Repositories\Interfaces;

use App\Models\Payment;

interface PaymentRepositoryInterface extends BaseRepositoryInterface
{
    public function getByOrderId(int $orderId): ?Payment;

    public function findByTransactionId(string $transactionId): ?Payment;

    public function updateStatus(int $paymentId, string $status, ?string $transactionId = null): Payment;
}
