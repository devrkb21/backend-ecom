<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\PaymentGateway;
use App\Models\SavedPaymentMethod;
use App\Models\User;
use Stripe\Customer;
use Stripe\Event;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\PaymentMethod as StripePaymentMethod;
use Stripe\Refund;
use Stripe\Stripe;
use Stripe\Webhook;

class StripePaymentService
{
    protected PaymentGateway $gateway;

    public function __construct()
    {
        $this->gateway = PaymentGateway::findByCode('stripe');

        if ($this->gateway && $this->gateway->is_active) {
            $secretKey = $this->getModeSetting('secret_key');
            if ($secretKey) {
                Stripe::setApiKey($secretKey);
            }
        }
    }

    /**
     * Get a setting based on the current mode (test/live)
     */
    protected function getModeSetting(string $key): ?string
    {
        if (! $this->gateway) {
            return null;
        }
        $mode = $this->gateway->getSetting('mode', 'test');
        $env = $mode === 'test' ? 'test' : 'live';

        return $this->gateway->getSetting("{$env}.{$key}");
    }

    /**
     * Check if Stripe is properly configured
     */
    public function isConfigured(): bool
    {
        if (! $this->gateway || ! $this->gateway->is_active) {
            return false;
        }

        $secretKey = $this->getModeSetting('secret_key');
        $publicKey = $this->getModeSetting('public_key');

        return ! empty($secretKey) && ! empty($publicKey);
    }

    /**
     * Get the public key for frontend
     */
    public function getPublicKey(): ?string
    {
        return $this->getModeSetting('public_key');
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
        if (! $this->isConfigured()) {
            throw new \Exception('Stripe is not properly configured.');
        }

        try {
            $metadata = array_merge([
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'customer_email' => $order->shipping_email,
            ], is_array($options['metadata'] ?? null) ? $options['metadata'] : []);

            $payload = [
                'amount' => $this->convertToCents((float) $order->total),
                'currency' => strtolower($options['currency'] ?? 'bdt'),
                'metadata' => $metadata,
                'description' => "Order #{$order->order_number}",
                'receipt_email' => $order->shipping_email,
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ];

            if (! empty($options['customer_id'])) {
                $payload['customer'] = (string) $options['customer_id'];
            }

            if (! empty($options['setup_future_usage'])) {
                $payload['setup_future_usage'] = (string) $options['setup_future_usage'];
            }

            $paymentIntent = PaymentIntent::create($payload);

            return [
                'client_secret' => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id,
                'amount' => $order->total,
                'currency' => strtoupper($options['currency'] ?? 'BDT'),
            ];
        } catch (ApiErrorException $e) {
            throw new \Exception('Stripe Error: '.$e->getMessage());
        }
    }

    public function getOrCreateCustomerForUser(User $user): string
    {
        if (! $this->isConfigured()) {
            throw new \Exception('Stripe is not properly configured.');
        }

        $existingCustomerId = trim((string) $user->stripe_customer_id);

        if ($existingCustomerId !== '') {
            try {
                $customer = Customer::retrieve($existingCustomerId);
                if (empty($customer->deleted)) {
                    return $existingCustomerId;
                }
            } catch (ApiErrorException) {
                // Recreate customer if stored ID is invalid or removed.
            }
        }

        try {
            $customer = Customer::create([
                'email' => $user->email,
                'name' => $user->name,
                'phone' => $user->phone,
                'metadata' => [
                    'user_id' => (string) $user->id,
                ],
            ]);

            $user->forceFill([
                'stripe_customer_id' => $customer->id,
            ])->save();

            return $customer->id;
        } catch (ApiErrorException $e) {
            throw new \Exception('Stripe Error: '.$e->getMessage());
        }
    }

    public function savePaymentMethodForUser(User $user, string $paymentMethodId, bool $makeDefault = false): SavedPaymentMethod
    {
        if (! $this->isConfigured()) {
            throw new \Exception('Stripe is not properly configured.');
        }

        $paymentMethodId = trim($paymentMethodId);
        if ($paymentMethodId === '') {
            throw new \Exception('Stripe payment method ID is required.');
        }

        $customerId = $this->getOrCreateCustomerForUser($user);

        try {
            $this->ensurePaymentMethodAttachedToCustomer($paymentMethodId, $customerId);
            $savedMethod = $this->syncSavedPaymentMethodFromStripe($user, $customerId, $paymentMethodId);

            $hasDefault = SavedPaymentMethod::query()
                ->where('user_id', $user->id)
                ->where('gateway', 'stripe')
                ->where('is_active', true)
                ->where('is_default', true)
                ->exists();

            if ($makeDefault || ! $hasDefault) {
                $this->setDefaultSavedPaymentMethod($user, $savedMethod);
            }

            return $savedMethod->fresh();
        } catch (ApiErrorException $e) {
            throw new \Exception('Stripe Error: '.$e->getMessage());
        }
    }

    public function setDefaultSavedPaymentMethod(User $user, SavedPaymentMethod $savedPaymentMethod): void
    {
        if ((int) $savedPaymentMethod->user_id !== (int) $user->id) {
            throw new \Exception('Unauthorized saved payment method access.');
        }

        $customerId = $this->getOrCreateCustomerForUser($user);

        try {
            Customer::update($customerId, [
                'invoice_settings' => [
                    'default_payment_method' => $savedPaymentMethod->provider_payment_method_id,
                ],
            ]);

            SavedPaymentMethod::query()
                ->where('user_id', $user->id)
                ->where('gateway', 'stripe')
                ->where('id', '!=', $savedPaymentMethod->id)
                ->update(['is_default' => false]);

            $savedPaymentMethod->update([
                'is_default' => true,
                'is_active' => true,
            ]);
        } catch (ApiErrorException $e) {
            throw new \Exception('Stripe Error: '.$e->getMessage());
        }
    }

    public function removeSavedPaymentMethod(User $user, SavedPaymentMethod $savedPaymentMethod): void
    {
        if ((int) $savedPaymentMethod->user_id !== (int) $user->id) {
            throw new \Exception('Unauthorized saved payment method access.');
        }

        try {
            $paymentMethod = StripePaymentMethod::retrieve($savedPaymentMethod->provider_payment_method_id);
            $paymentMethod->detach();
        } catch (ApiErrorException $e) {
            // Ignore detach failures when Stripe method is already removed remotely.
            if (! str_contains(strtolower($e->getMessage()), 'no such payment_method')) {
                throw new \Exception('Stripe Error: '.$e->getMessage());
            }
        }

        $wasDefault = (bool) $savedPaymentMethod->is_default;
        $savedPaymentMethod->delete();

        $nextDefault = SavedPaymentMethod::query()
            ->where('user_id', $user->id)
            ->where('gateway', 'stripe')
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();

        if ($nextDefault) {
            if ($wasDefault || ! $nextDefault->is_default) {
                $this->setDefaultSavedPaymentMethod($user, $nextDefault);
            }

            return;
        }

        if (! empty($user->stripe_customer_id)) {
            try {
                Customer::update($user->stripe_customer_id, [
                    'invoice_settings' => [
                        'default_payment_method' => null,
                    ],
                ]);
            } catch (ApiErrorException) {
                // Best effort cleanup; local record already removed.
            }
        }
    }

    public function markSavedPaymentMethodUsed(User $user, string $paymentMethodId): void
    {
        SavedPaymentMethod::query()
            ->where('user_id', $user->id)
            ->where('provider_payment_method_id', $paymentMethodId)
            ->where('is_active', true)
            ->update(['last_used_at' => now()]);
    }

    /**
     * Confirm a PaymentIntent (after frontend confirms payment)
     */
    public function confirmPayment(string $paymentIntentId): array
    {
        if (! $this->isConfigured()) {
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
            throw new \Exception('Stripe Error: '.$e->getMessage());
        }
    }

    /**
     * Process refund for a payment
     */
    public function refund(string $paymentIntentId, ?float $amount = null): array
    {
        if (! $this->isConfigured()) {
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
            throw new \Exception('Stripe Refund Error: '.$e->getMessage());
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
            throw new \Exception('Stripe Error: '.$e->getMessage());
        }
    }

    /**
     * Convert dollars to cents for Stripe
     */
    public function convertToCents(float $amount): int
    {
        return (int) round($amount * 100);
    }

    /**
     * Verify webhook signature
     */
    public function verifyWebhookSignature(string $payload, string $sigHeader): Event
    {
        $webhookSecret = $this->getModeSetting('webhook_secret');

        if (! $webhookSecret) {
            throw new \Exception('Webhook secret not configured.');
        }

        return Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
    }

    protected function syncSavedPaymentMethodFromStripe(User $user, string $customerId, string $paymentMethodId): SavedPaymentMethod
    {
        $paymentMethod = StripePaymentMethod::retrieve($paymentMethodId);

        if (($paymentMethod->type ?? null) !== 'card') {
            throw new \Exception('Only card payment methods can be saved.');
        }

        return SavedPaymentMethod::query()->updateOrCreate(
            ['provider_payment_method_id' => $paymentMethodId],
            [
                'user_id' => $user->id,
                'gateway' => 'stripe',
                'provider_customer_id' => $customerId,
                'card_brand' => $paymentMethod->card->brand ?? null,
                'card_last_four' => $paymentMethod->card->last4 ?? null,
                'card_exp_month' => $paymentMethod->card->exp_month ?? null,
                'card_exp_year' => $paymentMethod->card->exp_year ?? null,
                'card_fingerprint' => $paymentMethod->card->fingerprint ?? null,
                'cardholder_name' => $paymentMethod->billing_details->name ?? null,
                'is_active' => true,
                'last_used_at' => now(),
                'metadata' => [
                    'country' => $paymentMethod->card->country ?? null,
                    'funding' => $paymentMethod->card->funding ?? null,
                    'type' => $paymentMethod->type ?? null,
                ],
            ]
        );
    }

    protected function ensurePaymentMethodAttachedToCustomer(string $paymentMethodId, string $customerId): void
    {
        $paymentMethod = StripePaymentMethod::retrieve($paymentMethodId);
        $attachedCustomer = (string) ($paymentMethod->customer ?? '');

        if ($attachedCustomer === $customerId) {
            return;
        }

        if ($attachedCustomer !== '' && $attachedCustomer !== $customerId) {
            throw new \Exception('This card is already attached to a different customer.');
        }

        $paymentMethod->attach([
            'customer' => $customerId,
        ]);
    }
}
