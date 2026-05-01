<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\PaymentGateway;
use Karim007\LaravelBkashTokenize\Facade\BkashPaymentTokenize;
use Karim007\LaravelBkashTokenize\Facade\BkashRefundTokenize;

class BkashPaymentService
{
    protected ?PaymentGateway $gateway = null;

    public function __construct()
    {
        $this->gateway = PaymentGateway::findByCode('bkash');
        
        if ($this->gateway && $this->gateway->is_active) {
            $this->configureFromGateway();
        }
    }

    /**
     * Get a setting based on the current mode (sandbox/live)
     */
    protected function getModeSetting(string $key): ?string
    {
        if (!$this->gateway) return null;
        $mode = $this->gateway->getSetting('mode', 'sandbox');
        $env = $mode === 'sandbox' ? 'sandbox' : 'live';
        return $this->gateway->getSetting("{$env}.{$key}");
    }

    /**
     * Configure bKash from gateway settings
     */
    protected function configureFromGateway(): void
    {
        // Get settings from database, fall back to .env
        $appKey = $this->getModeSetting('app_key') ?: config('bkash.bkash_app_key');
        $appSecret = $this->getModeSetting('app_secret') ?: config('bkash.bkash_app_secret');
        $username = $this->getModeSetting('username') ?: config('bkash.bkash_username');
        $password = $this->getModeSetting('password') ?: config('bkash.bkash_password');
        $mode = $this->gateway->getSetting('mode', 'sandbox');
        $sandbox = $mode === 'sandbox' || $mode !== 'live';

        // Set sandbox mode - the library uses this to determine the base URL
        config([
            'bkash.sandbox' => $sandbox,
            'bkash.bkash_app_key' => $appKey,
            'bkash.bkash_app_secret' => $appSecret,
            'bkash.bkash_username' => $username,
            'bkash.bkash_password' => $password,
            'bkash.callbackURL' => url('/api/v1/bkash/callback'),
        ]);
        
        // Debug log to verify config is set
        \Log::debug('bKash config set', [
            'sandbox' => config('bkash.sandbox'),
            'app_key' => substr(config('bkash.bkash_app_key'), 0, 8) . '...',
            'username' => config('bkash.bkash_username'),
            'callback' => config('bkash.callbackURL'),
        ]);
    }

    /**
     * Check if bKash is properly configured
     */
    public function isConfigured(): bool
    {
        if (!$this->gateway || !$this->gateway->is_active) {
            return false;
        }

        $appKey = $this->getModeSetting('app_key');
        $appSecret = $this->getModeSetting('app_secret');
        $username = $this->getModeSetting('username');
        $password = $this->getModeSetting('password');

        return !empty($appKey) && !empty($appSecret) && !empty($username) && !empty($password);
    }

    /**
     * Check if running in sandbox mode
     */
    public function isSandboxMode(): bool
    {
        return $this->gateway?->getSetting('mode', 'sandbox') === 'sandbox';
    }

    /**
     * Create a payment for an order
     */
    public function createPayment(Order $order): array
    {
        if (!$this->isConfigured()) {
            throw new \Exception('bKash is not properly configured.');
        }

        // Ensure config is fresh
        $this->configureFromGateway();

        $invoiceNumber = $order->order_number;
        
        $requestData = [
            'intent' => 'sale',
            'mode' => '0011', // Tokenized checkout
            'payerReference' => (string) $order->user_id,
            'currency' => 'BDT',
            'amount' => (string) round((float) $order->total, 2),
            'merchantInvoiceNumber' => $invoiceNumber,
            'callbackURL' => url('/api/v1/bkash/callback'),
        ];

        try {
            $response = BkashPaymentTokenize::cPayment(json_encode($requestData));
            
            // Log response for debugging
            \Log::debug('bKash cPayment response', [
                'request' => $requestData,
                'response' => $response,
            ]);
            
            if (!$response) {
                \Log::error('bKash API returned null response');
                return [
                    'success' => false,
                    'message' => 'bKash API is not responding. Please try again later.',
                    'error_code' => 'API_ERROR',
                ];
            }

            if (isset($response['bkashURL'])) {
                return [
                    'success' => true,
                    'payment_id' => $response['paymentID'],
                    'bkash_url' => $response['bkashURL'],
                    'order_id' => $order->id,
                    'amount' => $order->total,
                ];
            }

            return [
                'success' => false,
                'message' => $response['statusMessage'] ?? 'Failed to create bKash payment',
                'error_code' => $response['statusCode'] ?? 'UNKNOWN',
            ];
        } catch (\Exception $e) {
            \Log::error('bKash createPayment exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return [
                'success' => false,
                'message' => 'bKash payment failed: ' . $e->getMessage(),
                'error_code' => 'EXCEPTION',
            ];
        }
    }

    /**
     * Execute payment after callback
     */
    public function executePayment(string $paymentId): array
    {
        if (!$this->isConfigured()) {
            throw new \Exception('bKash is not properly configured.');
        }

        $response = BkashPaymentTokenize::executePayment($paymentId);

        if (!$response) {
            // Try query payment if execute fails
            $response = BkashPaymentTokenize::queryPayment($paymentId);
        }

        if (isset($response['statusCode']) && $response['statusCode'] === '0000' && 
            $response['transactionStatus'] === 'Completed') {
            return [
                'success' => true,
                'transaction_id' => $response['trxID'],
                'payment_id' => $response['paymentID'],
                'amount' => $response['amount'],
                'status' => 'completed',
            ];
        }

        return [
            'success' => false,
            'message' => $response['statusMessage'] ?? 'Payment execution failed',
            'status' => $response['transactionStatus'] ?? 'unknown',
        ];
    }

    /**
     * Query payment status
     */
    public function queryPayment(string $paymentId): array
    {
        if (!$this->isConfigured()) {
            throw new \Exception('bKash is not properly configured.');
        }

        $response = BkashPaymentTokenize::queryPayment($paymentId);

        return [
            'payment_id' => $response['paymentID'] ?? null,
            'transaction_id' => $response['trxID'] ?? null,
            'status' => $response['transactionStatus'] ?? 'unknown',
            'amount' => $response['amount'] ?? null,
            'verification_status' => $response['verificationStatus'] ?? null,
        ];
    }

    /**
     * Search transaction by transaction ID
     */
    public function searchTransaction(string $transactionId): array
    {
        if (!$this->isConfigured()) {
            throw new \Exception('bKash is not properly configured.');
        }

        return BkashPaymentTokenize::searchTransaction($transactionId);
    }

    /**
     * Refund a payment
     */
    public function refund(string $paymentId, string $transactionId, float $amount, string $reason = ''): array
    {
        if (!$this->isConfigured()) {
            throw new \Exception('bKash is not properly configured.');
        }

        $sku = 'refund-' . time();
        $response = BkashRefundTokenize::refund($paymentId, $transactionId, $amount, $reason, $sku);

        if (isset($response['statusCode']) && $response['statusCode'] === '0000') {
            return [
                'success' => true,
                'refund_transaction_id' => $response['refundTrxID'],
                'original_transaction_id' => $response['originalTrxID'],
                'amount' => $response['amount'],
                'status' => $response['transactionStatus'],
            ];
        }

        return [
            'success' => false,
            'message' => $response['statusMessage'] ?? 'Refund failed',
        ];
    }

    /**
     * Check refund status
     */
    public function refundStatus(string $paymentId, string $transactionId): array
    {
        if (!$this->isConfigured()) {
            throw new \Exception('bKash is not properly configured.');
        }

        return BkashRefundTokenize::refundStatus($paymentId, $transactionId);
    }
}
