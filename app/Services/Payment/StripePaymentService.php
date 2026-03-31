<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentGateway;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Refund;
use Stripe\Exception\ApiErrorException;

class StripePaymentService
{
    protected PaymentGateway $gateway;

    public function __construct()
    {
        $this->gateway = PaymentGateway::findByCode('stripe');
        
        if ($this->gateway && $this->gateway->is_active) {
            $secretKey = $this->gateway->getSetting('secret_key');
            if ($secretKey) {
                Stripe::setApiKey($secretKey);
            }
        }
    }

    /**
     * Check if Stripe is properly configured
     */
    public function isConfigured(): bool
    {
        if (!$this->gateway || !$this->gateway->is_active) {
            return false;
        }

        $secretKey = $this->gateway->getSetting('secret_key');
        $publicKey = $this->gateway->getSetting('public_key');

        return !empty($secretKey) && !empty($publicKey);
    }

    /**
     * Get the public key for frontend
     */
    public function getPublicKey(): ?string
    {
        return $this->gateway?->getSetting('public_key');
    }

    /**
     * Check if running in test mode
     */
    public function isTestMode(): bool
    {
        return $this->gateway?->getSetting('mode', 'test') === 'test';
    }

    /**
     * Create a PaymentIntent for an order
     */
    public function createPaymentIntent(Order $order, array $options = []): array
    {
        if (!$this->isConfigured()) {
            throw new \Exception('Stripe is not properly configured.');
        }

        try {
            $paymentIntent = PaymentIntent::create([
                'amount' => $this->convertToCents((float) $order->total),
                'currency' => strtolower($options['currency'] ?? 'usd'),
                'metadata' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'customer_email' => $order->shipping_email,
                ],
                'description' => "Order #{$order->order_number}",
                'receipt_email' => $order->shipping_email,
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);

            return [
                'client_secret' => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id,
                'amount' => $order->total,
                'currency' => strtoupper($options['currency'] ?? 'USD'),
            ];
        } catch (ApiErrorException $e) {
            throw new \Exception('Stripe Error: ' . $e->getMessage());
        }
    }

    /**
     * Confirm a PaymentIntent (after frontend confirms payment)
     */
    public function confirmPayment(string $paymentIntentId): array
    {
        if (!$this->isConfigured()) {
            throw new \Exception('Stripe is not properly configured.');
        }

        try {
            $paymentIntent = PaymentIntent::retrieve($paymentIntentId);

            return [
                'id' => $paymentIntent->id,
                'status' => $paymentIntent->status,
                'amount' => $paymentIntent->amount / 100,
                'currency' => strtoupper($paymentIntent->currency),
                'payment_method' => $paymentIntent->payment_method,
                'succeeded' => $paymentIntent->status === 'succeeded',
            ];
        } catch (ApiErrorException $e) {
            throw new \Exception('Stripe Error: ' . $e->getMessage());
        }
    }

    /**
     * Process refund for a payment
     */
    public function refund(string $paymentIntentId, ?float $amount = null): array
    {
        if (!$this->isConfigured()) {
            throw new \Exception('Stripe is not properly configured.');
        }

        try {
            $refundData = [
                'payment_intent' => $paymentIntentId,
            ];

            if ($amount !== null) {
                $refundData['amount'] = $this->convertToCents($amount);
            }

            $refund = Refund::create($refundData);

            return [
                'id' => $refund->id,
                'status' => $refund->status,
                'amount' => $refund->amount / 100,
                'currency' => strtoupper($refund->currency),
            ];
        } catch (ApiErrorException $e) {
            throw new \Exception('Stripe Refund Error: ' . $e->getMessage());
        }
    }

    /**
     * Get PaymentIntent status
     */
    public function getPaymentStatus(string $paymentIntentId): array
    {
        try {
            $paymentIntent = PaymentIntent::retrieve($paymentIntentId);

            return [
                'id' => $paymentIntent->id,
                'status' => $paymentIntent->status,
                'amount' => $paymentIntent->amount / 100,
                'succeeded' => $paymentIntent->status === 'succeeded',
            ];
        } catch (ApiErrorException $e) {
            throw new \Exception('Stripe Error: ' . $e->getMessage());
        }
    }

    /**
     * Convert dollars to cents for Stripe
     */
    protected function convertToCents(float $amount): int
    {
        return (int) round($amount * 100);
    }

    /**
     * Verify webhook signature
     */
    public function verifyWebhookSignature(string $payload, string $sigHeader): \Stripe\Event
    {
        $webhookSecret = $this->gateway?->getSetting('webhook_secret');
        
        if (!$webhookSecret) {
            throw new \Exception('Webhook secret not configured.');
        }

        return \Stripe\Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
    }
}
