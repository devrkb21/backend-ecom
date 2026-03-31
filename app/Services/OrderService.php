<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\PaymentGateway;
use App\Models\ShippingMethod;
use App\Models\AbandonedCart;
use App\Notifications\OrderConfirmation;
use App\Notifications\OrderStatusUpdated;
use App\Notifications\OrderShipped;
use App\Repositories\Interfaces\OrderRepositoryInterface;
use App\Repositories\Interfaces\CartRepositoryInterface;
use App\Repositories\Interfaces\ProductRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        protected OrderRepositoryInterface $orderRepository,
        protected CartRepositoryInterface $cartRepository,
        protected ProductRepositoryInterface $productRepository
    ) {}

    public function getUserOrders(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->orderRepository->getByUserIdPaginated($userId, $perPage);
    }

    public function getOrderById(int $id): Order
    {
        return $this->orderRepository->findOrFail($id);
    }

    public function getOrderByNumber(string $orderNumber): ?Order
    {
        return $this->orderRepository->findByOrderNumber($orderNumber);
    }

    public function getAllOrders(int $perPage = 15): LengthAwarePaginator
    {
        return $this->orderRepository->paginate($perPage);
    }

    public function getOrdersByStatus(string $status): Collection
    {
        return $this->orderRepository->getByStatus($status);
    }

    public function createOrderFromCart(int $userId, array $shippingData): Order
    {
        return DB::transaction(function () use ($userId, $shippingData) {
            $cart = $this->cartRepository->getByUserId($userId);

            if (!$cart || $cart->items->isEmpty()) {
                throw new \Exception('Cart is empty.');
            }

            // Validate stock for all items
            foreach ($cart->items as $item) {
                $product = $item->product;
                if (!$product->hasStock($item->quantity)) {
                    throw new \Exception("Insufficient stock for product: {$product->name}");
                }
            }

            // Get payment gateway
            $paymentMethod = $shippingData['payment_method'] ?? 'cod';
            $paymentGateway = PaymentGateway::findByCode($paymentMethod);
            
            if (!$paymentGateway || !$paymentGateway->is_active) {
                throw new \Exception('Selected payment method is not available.');
            }

            // Get shipping method
            $shippingMethodCode = $shippingData['shipping_method'] ?? 'standard';
            $shippingMethod = ShippingMethod::findByCode($shippingMethodCode);
            
            if (!$shippingMethod || !$shippingMethod->is_active) {
                throw new \Exception('Selected shipping method is not available.');
            }

            // Calculate totals
            $subtotal = $cart->subtotal;
            $tax = $subtotal * 0.1; // 10% tax
            
            // Apply coupon discount
            $coupon = $cart->coupon;
            $discountAmount = 0;
            
            if ($coupon && $coupon->isValid()) {
                $discountAmount = $cart->discount_amount;
            }
            
            // Calculate item count for shipping
            $itemCount = $cart->items->sum('quantity');
            
            // Calculate shipping cost using selected method
            $baseShipping = $shippingMethod->calculateCost($subtotal, $itemCount, 0);
            
            // Check if coupon provides free shipping
            $shipping = ($coupon && $coupon->free_shipping) ? 0 : $baseShipping;
            
            // Check if shipping method is available for this order
            $countryCode = $this->getCountryCode($shippingData['shipping_country'] ?? '');
            if (!$shippingMethod->isAvailableFor($subtotal, null, $countryCode)) {
                throw new \Exception('Selected shipping method is not available for your order.');
            }
            
            // Calculate payment gateway extra charge (e.g., COD fee)
            $paymentCharge = $this->calculatePaymentCharge($paymentGateway, $subtotal + $tax + $shipping - $discountAmount);
            
            $total = max(0, $subtotal + $tax + $shipping - $discountAmount + $paymentCharge);

            // Check if order amount is within gateway limits
            if (!$paymentGateway->isAvailableFor($total)) {
                throw new \Exception("Order amount is not within the limits for {$paymentGateway->name}.");
            }

            // Determine payment status based on gateway type
            $paymentStatus = $paymentGateway->isPayOnDelivery() ? 'pending' : 'awaiting';

            // Prepare order data
            $orderData = [
                'user_id' => $userId,
                'coupon_id' => $coupon?->id,
                'coupon_code' => $coupon?->code,
                'discount_amount' => $discountAmount,
                'order_number' => Order::generateOrderNumber(),
                'status' => 'pending',
                'payment_method' => $paymentMethod,
                'payment_status' => $paymentStatus,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'shipping' => $shipping,
                'shipping_method' => $shippingMethodCode,
                'total' => $total,
                'shipping_name' => $shippingData['shipping_name'],
                'shipping_email' => $shippingData['shipping_email'],
                'shipping_phone' => $shippingData['shipping_phone'] ?? null,
                'shipping_address' => $shippingData['shipping_address'],
                'shipping_city' => $shippingData['shipping_city'],
                'shipping_state' => $shippingData['shipping_state'] ?? null,
                'shipping_zip' => $shippingData['shipping_zip'],
                'shipping_country' => $shippingData['shipping_country'],
                'notes' => $shippingData['notes'] ?? null,
            ];

            // Prepare order items
            $items = [];
            foreach ($cart->items as $item) {
                $items[] = [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'product_sku' => $item->product->sku,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                ];

                // Decrement stock
                $item->product->decrementStock($item->quantity);
            }

            // Create order with items
            $order = $this->orderRepository->createWithItems($orderData, $items);

            // Record coupon usage
            if ($coupon && $discountAmount > 0) {
                CouponUsage::create([
                    'coupon_id' => $coupon->id,
                    'user_id' => $userId,
                    'order_id' => $order->id,
                    'discount_amount' => $discountAmount,
                ]);
                $coupon->incrementUsage();
            }

            // Clear the cart
            $this->cartRepository->clearCart($cart->id);

            // Mark any abandoned cart as recovered
            $this->markAbandonedCartRecovered($userId, $order->id);

            // Send order confirmation notification
            $order->user->notify(new OrderConfirmation($order));

            return $order;
        });
    }

    /**
     * Mark abandoned cart as recovered when order is placed
     */
    protected function markAbandonedCartRecovered(int $userId, int $orderId): void
    {
        $abandonedCart = AbandonedCart::where('user_id', $userId)
            ->whereIn('status', ['pending', 'follow_up'])
            ->orderBy('created_at', 'desc')
            ->first();

        if ($abandonedCart) {
            $abandonedCart->markAsRecovered($orderId);
        }
    }

    public function updateOrderStatus(int $orderId, string $status): Order
    {
        $order = $this->getOrderById($orderId);

        $validTransitions = [
            'pending' => ['processing', 'cancelled'],
            'processing' => ['shipped', 'cancelled'],
            'shipped' => ['delivered'],
            'delivered' => [],
            'cancelled' => [],
        ];

        if (!in_array($status, $validTransitions[$order->status] ?? [])) {
            throw new \Exception("Invalid status transition from {$order->status} to {$status}");
        }

        // If cancelling, restore stock
        if ($status === 'cancelled') {
            foreach ($order->items as $item) {
                $item->product->incrementStock($item->quantity);
            }
        }

        $oldStatus = $order->status;
        $updatedOrder = $this->orderRepository->updateStatus($orderId, $status);
        
        // Send status update notification
        $updatedOrder->user->notify(new OrderStatusUpdated($updatedOrder, $oldStatus, $status));
        
        // Send shipped notification if status is shipped
        if ($status === 'shipped') {
            $updatedOrder->user->notify(new OrderShipped(
                $updatedOrder,
                $updatedOrder->tracking_number,
                $updatedOrder->carrier
            ));
        }
        
        return $updatedOrder;
    }

    public function cancelOrder(int $orderId, int $userId): Order
    {
        $order = $this->getOrderById($orderId);

        if ($order->user_id !== $userId) {
            throw new \Exception('Unauthorized to cancel this order.');
        }

        if (!$order->canBeCancelled()) {
            throw new \Exception('This order cannot be cancelled.');
        }

        return $this->updateOrderStatus($orderId, 'cancelled');
    }

    /**
     * Calculate extra charge for a payment gateway (e.g., COD fee)
     */
    protected function calculatePaymentCharge(PaymentGateway $gateway, float $orderAmount): float
    {
        $extraCharge = $gateway->getSetting('extra_charge', 0);
        $chargeType = $gateway->getSetting('extra_charge_type', 'fixed');
        
        if ($extraCharge <= 0) {
            return 0;
        }
        
        if ($chargeType === 'percentage') {
            return round($orderAmount * ($extraCharge / 100), 2);
        }
        
        return (float) $extraCharge;
    }

    /**
     * Update payment status for an order
     */
    public function updatePaymentStatus(int $orderId, string $status, ?string $transactionId = null): Order
    {
        $order = $this->getOrderById($orderId);
        
        $validStatuses = ['pending', 'awaiting', 'paid', 'failed', 'refunded'];
        if (!in_array($status, $validStatuses)) {
            throw new \Exception('Invalid payment status.');
        }
        
        $order->payment_status = $status;
        
        if ($transactionId) {
            $order->transaction_id = $transactionId;
        }
        
        $order->save();
        
        return $order;
    }

    /**
     * Get country code from country name or code
     */
    protected function getCountryCode(?string $country): ?string
    {
        if (!$country) {
            return null;
        }

        // Common country name to code mapping
        $countryMap = [
            'united states' => 'US',
            'usa' => 'US',
            'u.s.a.' => 'US',
            'canada' => 'CA',
            'united kingdom' => 'GB',
            'uk' => 'GB',
            'australia' => 'AU',
            'germany' => 'DE',
            'france' => 'FR',
            'japan' => 'JP',
            'china' => 'CN',
            'india' => 'IN',
            'brazil' => 'BR',
            'mexico' => 'MX',
            'spain' => 'ES',
            'italy' => 'IT',
            'netherlands' => 'NL',
            'belgium' => 'BE',
            'sweden' => 'SE',
            'norway' => 'NO',
            'denmark' => 'DK',
            'finland' => 'FI',
            'switzerland' => 'CH',
            'austria' => 'AT',
            'ireland' => 'IE',
            'new zealand' => 'NZ',
            'singapore' => 'SG',
            'hong kong' => 'HK',
            'south korea' => 'KR',
            'bangladesh' => 'BD',
        ];

        $lowerCountry = strtolower(trim($country));
        
        if (isset($countryMap[$lowerCountry])) {
            return $countryMap[$lowerCountry];
        }

        // If already a 2-letter code, return uppercase
        if (strlen($country) === 2) {
            return strtoupper($country);
        }

        return null;
    }
}
