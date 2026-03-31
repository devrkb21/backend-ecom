<?php

namespace App\Repositories;

use App\Models\Payment;
use App\Repositories\Interfaces\PaymentRepositoryInterface;

class PaymentRepository extends BaseRepository implements PaymentRepositoryInterface
{
    public function __construct(Payment $model)
    {
        parent::__construct($model);
    }

    public function getByOrderId(int $orderId): ?Payment
    {
        return $this->model->where('order_id', $orderId)->first();
    }

    public function findByTransactionId(string $transactionId): ?Payment
    {
        return $this->model->where('transaction_id', $transactionId)->first();
    }

    public function updateStatus(int $paymentId, string $status, ?string $transactionId = null): Payment
    {
        $payment = $this->findOrFail($paymentId);
        
        $updateData = ['status' => $status];
        
        if ($transactionId) {
            $updateData['transaction_id'] = $transactionId;
        }
        
        if ($status === 'completed') {
            $updateData['paid_at'] = now();
        }
        
        $payment->update($updateData);
        
        return $payment->fresh();
    }
}
