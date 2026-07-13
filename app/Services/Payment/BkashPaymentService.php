<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\PaymentGateway;
use Devrkb21\Bkash\Facades\Bkash;

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
        $appKey = $this->getModeSetting('app_key') ?: config('bkash.app_key');
        $appSecret = $this->getModeSetting('app_secret') ?: config('bkash.app_secret');
        $username = $this->getModeSetting('username') ?: config('bkash.username');
        $password = $this->getModeSetting('password') ?: config('bkash.password');
        $mode = $this->gateway->getSetting('mode', 'sandbox');
        $sandbox = $mode === 'sandbox' || $mode !== 'live';

        $environment = $sandbox ? 'sandbox' : 'production';
        $baseUrl = $sandbox 
            ? 'https://tokenized.sandbox.bka.sh/v2/tokenized-checkout'
            : 'https://tokenized.pay.bka.sh/v2/tokenized-checkout';

        // Set config values for devrkb21/bkash-pgw-laravel
        config([
            'bkash.environment' => $environment,
            'bkash.base_url' => $baseUrl,
            'bkash.app_key' => $appKey,
            'bkash.app_secret' => $appSecret,
            'bkash.username' => $username,
            'bkash.password' => $password,
        ]);

        // Clear resolved singletons in container to load fresh dynamic configurations
        app()->forgetInstance(\Devrkb21\Bkash\Contracts\BkashClientContract::class);
        app()->forgetInstance(\Devrkb21\Bkash\Contracts\AuthServiceContract::class);
        app()->forgetInstance(\Devrkb21\Bkash\Contracts\PaymentServiceContract::class);
        app()->forgetInstance(\Devrkb21\Bkash\Contracts\AgreementServiceContract::class);
        app()->forgetInstance(\Devrkb21\Bkash\Contracts\RefundServiceContract::class);
        app()->forgetInstance(\Devrkb21\Bkash\BkashManager::class);
        app()->forgetInstance('bkash');
        
        // Debug log to verify config is set
        \Log::debug('bKash config set for devrkb21 package', [
            'environment' => config('bkash.environment'),
            'app_key' => substr((string) config('bkash.app_key'), 0, 8) . '...',
            'username' => config('bkash.username'),
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
            try {
                // Note: devrkb21 package expects array representation, not json string.
                $response = Bkash::payment()->createPayment($requestData);
            } catch (\Exception $e) {
                // If it fails due to merchantInvoiceNumber validation (duplicate), retry with time appended (HHMM)
                if (str_contains($e->getMessage(), 'merchantInvoiceNumber')) {
                    $suffix = now()->timezone('Asia/Dhaka')->format('Hi');
                    $modifiedInvoiceNumber = $invoiceNumber . '-' . $suffix;
                    $requestData['merchantInvoiceNumber'] = $modifiedInvoiceNumber;
                    
                    \Log::warning('bKash merchantInvoiceNumber duplicate detected. Retrying with suffix.', [
                        'original' => $invoiceNumber,
                        'modified' => $modifiedInvoiceNumber,
                        'error' => $e->getMessage()
                    ]);

                    $response = Bkash::payment()->createPayment($requestData);
                } else {
                    throw $e;
                }
            }
            
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
                    'payment_id' => $response['paymentID'] ?? $response['paymentId'],
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

        // Ensure config is fresh
        $this->configureFromGateway();

        try {
            $response = Bkash::payment()->executePayment($paymentId);
        } catch (\Exception $e) {
            \Log::warning('bKash executePayment failed, trying queryPayment fallback', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage()
            ]);
            try {
                $response = Bkash::payment()->queryPayment($paymentId);
            } catch (\Exception $qe) {
                $response = null;
            }
        }

        if (isset($response['statusCode']) && $response['statusCode'] === '0000' && 
            ($response['transactionStatus'] ?? '') === 'Completed') {
            return [
                'success' => true,
                'transaction_id' => $response['trxID'] ?? $response['trxId'] ?? null,
                'payment_id' => $response['paymentID'] ?? $response['paymentId'] ?? null,
                'amount' => $response['amount'] ?? null,
                'status' => 'completed',
                'customer_msisdn' => $response['customerMsisdn'] ?? $response['customer_msisdn'] ?? null,
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

        // Ensure config is fresh
        $this->configureFromGateway();

        try {
            $response = Bkash::payment()->queryPayment($paymentId);
        } catch (\Exception $e) {
            $response = [];
        }

        return [
            'payment_id' => $response['paymentID'] ?? $response['paymentId'] ?? null,
            'transaction_id' => $response['trxID'] ?? $response['trxId'] ?? null,
            'status' => $response['transactionStatus'] ?? $response['transaction_status'] ?? 'unknown',
            'amount' => $response['amount'] ?? null,
            'verification_status' => $response['verificationStatus'] ?? $response['verification_status'] ?? null,
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

        // Ensure config is fresh
        $this->configureFromGateway();

        try {
            return Bkash::refund()->searchTransaction($transactionId);
        } catch (\Exception $e) {
            \Log::error('bKash searchTransaction failed', ['trxId' => $transactionId, 'error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Refund a payment
     */
    public function refund(string $paymentId, string $transactionId, float $amount, string $reason = ''): array
    {
        if (!$this->isConfigured()) {
            throw new \Exception('bKash is not properly configured.');
        }

        // Ensure config is fresh
        $this->configureFromGateway();

        $sku = 'refund-' . time();
        $payload = [
            'paymentID' => $paymentId,
            'trxID' => $transactionId,
            'amount' => (string) round($amount, 2),
            'reason' => $reason,
            'sku' => $sku,
        ];

        try {
            $response = Bkash::refund()->refundTransaction($payload);
        } catch (\Exception $e) {
            \Log::error('bKash refund failed', ['payload' => $payload, 'error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'bKash refund error: ' . $e->getMessage(),
            ];
        }

        if (isset($response['statusCode']) && $response['statusCode'] === '0000') {
            return [
                'success' => true,
                'refund_transaction_id' => $response['refundTrxID'] ?? null,
                'original_transaction_id' => $response['originalTrxID'] ?? null,
                'amount' => $response['amount'] ?? null,
                'status' => $response['transactionStatus'] ?? null,
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

        // Ensure config is fresh
        $this->configureFromGateway();

        try {
            return Bkash::refund()->refundStatus($paymentId, $transactionId);
        } catch (\Exception $e) {
            \Log::error('bKash refundStatus failed', ['paymentId' => $paymentId, 'trxId' => $transactionId, 'error' => $e->getMessage()]);
            return [];
        }
    }
}
