<?php

namespace App\Services;

use App\Models\ReturnRequest;
use App\Services\Payment\BkashPaymentService;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class RefundService
{
    public function __construct(
        protected BkashPaymentService $bkashService
    ) {}

    /**
     * Process refund for a return request
     */
    public function processRefund(ReturnRequest $returnRequest): array
    {
        if (! $returnRequest->canProcessRefund()) {
            return [
                'success' => false,
                'message' => 'Return request is not eligible for refund processing.',
            ];
        }

        $paymentMethod = $returnRequest->getPaymentMethod();
        $originalTransactionId = $returnRequest->getOriginalTransactionId();
        $amount = (float) ($returnRequest->final_refund_amount ?? 0);

        if ($amount <= 0) {
            $amount = $returnRequest->calculateRefundAmount();
        }

        if ($amount <= 0) {
            return [
                'success' => false,
                'message' => 'Refund amount must be greater than 0.',
            ];
        }

        // Mark as processing
        $returnRequest->markRefundProcessing();

        try {
            $result = match ($paymentMethod) {
                'stripe' => $this->processStripeRefund($returnRequest, $originalTransactionId, $amount),
                'bkash' => $this->processBkashRefund($returnRequest, $originalTransactionId, $amount),
                'cod' => $this->processCodRefund($returnRequest, $amount),
                default => $this->processManualRefund($returnRequest, $amount),
            };

            if ($result['success']) {
                $returnRequest->markRefundCompleted($result['transaction_id'] ?? 'MANUAL');

                // Update order payment status
                $returnRequest->order->update(['payment_status' => 'refunded']);

                Log::info('Refund processed successfully', [
                    'return_id' => $returnRequest->id,
                    'amount' => $amount,
                    'method' => $paymentMethod,
                    'transaction_id' => $result['transaction_id'] ?? null,
                ]);
            } else {
                $returnRequest->markRefundFailed($result['message']);

                Log::error('Refund failed', [
                    'return_id' => $returnRequest->id,
                    'error' => $result['message'],
                ]);
            }

            return $result;

        } catch (\Exception $e) {
            $returnRequest->markRefundFailed($e->getMessage());

            Log::error('Refund exception', [
                'return_id' => $returnRequest->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Refund processing failed: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Process Stripe refund
     */
    protected function processStripeRefund(ReturnRequest $returnRequest, ?string $paymentIntentId, float $amount): array
    {
        if (empty($paymentIntentId)) {
            return [
                'success' => false,
                'message' => 'Missing original Stripe transaction ID.',
            ];
        }

        try {
            $stripe = new StripeClient(config('services.stripe.secret'));

            $refund = $stripe->refunds->create([
                'payment_intent' => $paymentIntentId,
                'amount' => (int) ($amount * 100), // Convert to cents
                'reason' => 'requested_by_customer',
                'metadata' => [
                    'return_id' => $returnRequest->id,
                    'return_number' => $returnRequest->return_number,
                    'order_id' => $returnRequest->order_id,
                    'reason' => $returnRequest->reason,
                ],
            ]);

            if ($refund->status === 'succeeded') {
                return [
                    'success' => true,
                    'transaction_id' => $refund->id,
                    'message' => 'Stripe refund processed successfully.',
                ];
            }

            return [
                'success' => false,
                'message' => 'Stripe refund status: '.$refund->status,
            ];

        } catch (ApiErrorException $e) {
            return [
                'success' => false,
                'message' => 'Stripe API error: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Process bKash refund
     */
    protected function processBkashRefund(ReturnRequest $returnRequest, ?string $paymentId, float $amount): array
    {
        if (empty($paymentId)) {
            return [
                'success' => false,
                'message' => 'Missing original bKash payment ID.',
            ];
        }

        try {
            // First query the original payment to get trxID
            $queryResult = $this->bkashService->queryPayment($paymentId);

            if (! $queryResult || empty($queryResult['transaction_id'])) {
                // Try to search by transaction ID directly if paymentId is actually a trxID
                $searchResult = $this->bkashService->searchTransaction($paymentId);

                if ($searchResult && isset($searchResult['trxID'])) {
                    $trxId = $searchResult['trxID'];
                    $actualPaymentId = $searchResult['paymentID'] ?? $paymentId;
                } else {
                    return [
                        'success' => false,
                        'message' => 'Could not find original bKash transaction.',
                    ];
                }
            } else {
                $trxId = $queryResult['transaction_id'];
                $actualPaymentId = $queryResult['payment_id'] ?? $paymentId;
            }

            // Process the refund
            $refundResult = $this->bkashService->refund(
                $actualPaymentId,
                $trxId,
                $amount,
                "Refund for return #{$returnRequest->return_number}: {$returnRequest->reason_label}"
            );

            if ($refundResult['success']) {
                return [
                    'success' => true,
                    'transaction_id' => $refundResult['refund_transaction_id'],
                    'message' => 'bKash refund processed successfully.',
                ];
            }

            return [
                'success' => false,
                'message' => 'bKash refund failed: '.($refundResult['message'] ?? 'Unknown error'),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'bKash refund error: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Process COD refund (manual bank transfer)
     */
    protected function processCodRefund(ReturnRequest $returnRequest, float $amount): array
    {
        // COD refunds are typically done manually via bank transfer
        // Just mark it for manual processing
        return [
            'success' => true,
            'transaction_id' => 'COD-MANUAL-'.$returnRequest->return_number,
            'message' => 'COD refund marked for manual bank transfer. Amount: ৳'.number_format($amount, 2),
            'requires_manual_action' => true,
        ];
    }

    /**
     * Process manual refund for other payment methods
     */
    protected function processManualRefund(ReturnRequest $returnRequest, float $amount): array
    {
        return [
            'success' => true,
            'transaction_id' => 'MANUAL-'.$returnRequest->return_number,
            'message' => 'Refund marked for manual processing. Amount: ৳'.number_format($amount, 2),
            'requires_manual_action' => true,
        ];
    }

    /**
     * Check refund status for Stripe
     */
    public function checkStripeRefundStatus(string $refundId): array
    {
        try {
            $stripe = new StripeClient(config('services.stripe.secret'));
            $refund = $stripe->refunds->retrieve($refundId);

            return [
                'success' => true,
                'status' => $refund->status,
                'amount' => $refund->amount / 100,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check refund status for bKash
     */
    public function checkBkashRefundStatus(string $paymentId, string $transactionId): array
    {
        try {
            return $this->bkashService->refundStatus($paymentId, $transactionId);
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
