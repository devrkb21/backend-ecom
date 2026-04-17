<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\BdDistrict;
use App\Models\BdDivision;
use App\Models\BdUnion;
use App\Models\BdUpazila;
use App\Models\PaymentGateway;
use App\Models\ShippingMethod;
use App\Models\AbandonedCart;
use App\Models\User;
use App\Notifications\OrderConfirmation;
use App\Notifications\OrderStatusUpdated;
use App\Notifications\OrderShipped;
use App\Services\BangladeshLocationResolver;
use App\Repositories\Interfaces\OrderRepositoryInterface;
use App\Repositories\Interfaces\CartRepositoryInterface;
use App\Repositories\Interfaces\ProductRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class OrderService
{
    public function __construct(
        protected OrderRepositoryInterface $orderRepository,
        protected CartRepositoryInterface $cartRepository,
        protected ProductRepositoryInterface $productRepository,
        protected BangladeshLocationResolver $locationResolver
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

    public function createOrderFromCart(?int $userId, array $shippingData): Order
    {
        return DB::transaction(function () use ($userId, $shippingData) {
            $isGuestCheckout = $userId === null;
            $guestUser = null;
            $cart = null;
            $coupon = null;
            $discountAmount = 0;

            if ($isGuestCheckout) {
                $guestUser = $this->resolveGuestCheckoutUser();
            }

            $orderOwnerId = $userId ?? $guestUser?->id;

            if ($isGuestCheckout) {
                $checkoutItems = $this->buildGuestCheckoutItems($shippingData['items'] ?? []);
            } else {
                $cart = $this->cartRepository->getByUserId($userId);

                if (!$cart || $cart->items->isEmpty()) {
                    throw new \Exception('Cart is empty.');
                }

                $checkoutItems = $cart->items->map(function ($item) {
                    if (!$item->product) {
                        throw new \Exception('One or more cart items are no longer available.');
                    }

                    return [
                        'product' => $item->product,
                        'product_id' => (int) $item->product_id,
                        'product_name' => $item->product->name,
                        'product_sku' => $item->product->sku,
                        'quantity' => (int) $item->quantity,
                        'price' => (float) $item->price,
                    ];
                });

                $coupon = $cart->coupon;
                if ($coupon && $coupon->isValid()) {
                    $discountAmount = (float) $cart->discount_amount;
                }
            }

            // Validate stock for all items
            foreach ($checkoutItems as $item) {
                /** @var Product $product */
                $product = $item['product'];
                $quantity = (int) $item['quantity'];

                if (!$product->hasStock($quantity)) {
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
            $subtotal = $checkoutItems->sum(function (array $item) {
                return ((float) $item['price']) * ((int) $item['quantity']);
            });
            $tax = $subtotal * 0.1; // 10% tax

            // Calculate item count for shipping
            $itemCount = (int) $checkoutItems->sum('quantity');

            $shippingLocationText = trim((string) ($shippingData['shipping_location_text'] ?? ''));
            if ($shippingLocationText === '') {
                $shippingLocationText = trim((string) ($shippingData['shipping_address'] ?? ''));
            }

            $divisionId = !empty($shippingData['shipping_division_id']) ? (int) $shippingData['shipping_division_id'] : null;
            $districtId = !empty($shippingData['shipping_district_id']) ? (int) $shippingData['shipping_district_id'] : null;
            $upazilaId = !empty($shippingData['shipping_upazila_id']) ? (int) $shippingData['shipping_upazila_id'] : null;
            $unionId = !empty($shippingData['shipping_union_id']) ? (int) $shippingData['shipping_union_id'] : null;

            if ($shippingLocationText !== '') {
                $resolved = $this->locationResolver->resolve(
                    $shippingLocationText,
                    $divisionId,
                    $districtId,
                    $upazilaId,
                    $unionId
                );

                $divisionId = $divisionId ?? (!empty($resolved['division_id']) ? (int) $resolved['division_id'] : null);
                $districtId = $districtId ?? (!empty($resolved['district_id']) ? (int) $resolved['district_id'] : null);
                $upazilaId = $upazilaId ?? (!empty($resolved['upazila_id']) ? (int) $resolved['upazila_id'] : null);
                $unionId = $unionId ?? (!empty($resolved['union_id']) ? (int) $resolved['union_id'] : null);
            }

            // Resolve Bangladesh hierarchy when ids are available; keep text-only checkout functional if partial resolution.
            $division = null;
            if ($divisionId) {
                $division = BdDivision::query()->find($divisionId);
                if (!$division) {
                    throw new \Exception('Unable to resolve shipping location from address.');
                }
            }

            $district = null;
            if ($districtId) {
                $district = BdDistrict::query()
                    ->where('id', $districtId)
                    ->when($division?->id, function ($query, int $resolvedDivisionId) {
                        $query->where('division_id', $resolvedDivisionId);
                    })
                    ->first();

                if (!$district) {
                    throw new \Exception('Unable to resolve shipping location from address.');
                }
            }

            $upazila = null;
            if ($upazilaId) {
                $upazila = BdUpazila::query()
                    ->where('id', $upazilaId)
                    ->when($district?->id, function ($query, int $resolvedDistrictId) {
                        $query->where('district_id', $resolvedDistrictId);
                    })
                    ->first();

                if (!$upazila) {
                    throw new \Exception('Unable to resolve shipping location from address.');
                }
            }

            $union = null;
            if ($unionId) {
                $union = BdUnion::query()
                    ->where('id', $unionId)
                    ->when($upazila?->id, function ($query, int $resolvedUpazilaId) {
                        $query->where('upazila_id', $resolvedUpazilaId);
                    })
                    ->first();

                if (!$union) {
                    throw new \Exception('Unable to resolve shipping location from address.');
                }
            }

            // Calculate shipping cost using selected method
            $baseShipping = $shippingMethod->calculateCost($subtotal, $itemCount, 0);

            // Product-level free delivery offer (if any cart product enables it)
            $hasProductFreeDelivery = $checkoutItems->contains(function (array $item) {
                /** @var Product $product */
                $product = $item['product'];

                return $product->hasFreeDeliveryOffer();
            });

            // Free shipping can come from coupon or product-level offer
            $shipping = (($coupon && $coupon->free_shipping) || $hasProductFreeDelivery) ? 0 : $baseShipping;

            // Check if shipping method is available for this order
            $hasResolvedLocationContext = $division?->id || $district?->id || $upazila?->id;
            if (!$hasResolvedLocationContext && $shippingMethod->locationRules()->exists()) {
                throw new \Exception('Unable to determine your shipping area from address. Please provide a more specific address.');
            }

            if (!$shippingMethod->isAvailableFor(
                $subtotal,
                null,
                'BD',
                $division?->id,
                $district?->id,
                $upazila?->id
            )) {
                throw new \Exception('Selected shipping method is not available for your order.');
            }

            $shippingArea = $shippingData['shipping_area'] ?? null;
            if (empty($shippingArea) && $shippingLocationText !== '') {
                $shippingArea = $shippingLocationText;
            }

            $shippingAddressParts = array_filter([
                $shippingData['shipping_address'] ?? null,
                $shippingArea,
                $union?->name,
                $upazila?->name,
                $district?->name,
            ]);

            $normalizedShippingAddress = implode(', ', $shippingAddressParts);

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
                'user_id' => $orderOwnerId,
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
                'shipping_email' => $shippingData['shipping_email'] ?? ($cart?->user?->email ?? $guestUser?->email ?? 'customer@local.invalid'),
                'shipping_phone' => $shippingData['shipping_phone'] ?? null,
                'shipping_address' => $normalizedShippingAddress,
                'shipping_division_id' => $division?->id,
                'shipping_district_id' => $district?->id,
                'shipping_upazila_id' => $upazila?->id,
                'shipping_union_id' => $union?->id,
                'shipping_city' => $shippingData['shipping_city']
                    ?? $district?->name
                    ?? $upazila?->name
                    ?? ($shippingLocationText !== '' ? $shippingLocationText : 'N/A'),
                'shipping_state' => $shippingData['shipping_state'] ?? $division?->name,
                'shipping_zip' => $shippingData['shipping_zip'] ?? '0000',
                'shipping_country' => 'Bangladesh',
                'notes' => $shippingData['notes'] ?? null,
            ];

            // Prepare order items
            $items = [];
            foreach ($checkoutItems as $item) {
                /** @var Product $product */
                $product = $item['product'];

                $items[] = [
                    'product_id' => (int) $item['product_id'],
                    'product_name' => $item['product_name'],
                    'product_sku' => $item['product_sku'],
                    'quantity' => (int) $item['quantity'],
                    'price' => (float) $item['price'],
                ];

                // Decrement stock
                $product->decrementStock((int) $item['quantity']);
            }

            // Create order with items
            $order = $this->orderRepository->createWithItems($orderData, $items);

            // Record coupon usage
            if ($coupon && $discountAmount > 0 && $userId !== null) {
                CouponUsage::create([
                    'coupon_id' => $coupon->id,
                    'user_id' => $userId,
                    'order_id' => $order->id,
                    'discount_amount' => $discountAmount,
                ]);
                $coupon->incrementUsage();
            }

            // Clear the cart
            if ($cart) {
                $this->cartRepository->clearCart($cart->id);
            }

            // Mark any abandoned cart as recovered
            $this->markAbandonedCartRecovered($userId, $order->id);

            // Send order confirmation notification
            if ($order->user) {
                $order->user->notify(new OrderConfirmation($order));
            }

            return $order;
        });
    }

    protected function resolveGuestCheckoutUser(): User
    {
        $email = trim((string) config('shop.guest_checkout_user_email', 'guest.checkout@innercollection.local'));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email = 'guest.checkout@innercollection.local';
        }

        $name = trim((string) config('shop.guest_checkout_user_name', 'Guest Checkout'));
        if ($name === '') {
            $name = 'Guest Checkout';
        }

        $phone = config('shop.guest_checkout_user_phone');

        $user = User::withTrashed()->where('email', $email)->first();
        if ($user) {
            if ($user->trashed()) {
                $user->restore();
            }

            $updates = [];
            if ($user->role !== User::ROLE_CUSTOMER) {
                $updates['role'] = User::ROLE_CUSTOMER;
            }

            if ($user->email_verified_at === null) {
                $updates['email_verified_at'] = now();
            }

            if (!empty($updates)) {
                $user->forceFill($updates)->save();
            }

            return $user;
        }

        $user = User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make(Str::random(48)),
            'phone' => is_string($phone) && trim($phone) !== '' ? trim($phone) : null,
            'role' => User::ROLE_CUSTOMER,
        ]);

        if ($user->email_verified_at === null) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        return $user;
    }

    protected function buildGuestCheckoutItems(array $rawItems): SupportCollection
    {
        if (empty($rawItems)) {
            throw new \Exception('Guest checkout requires at least one item.');
        }

        $groupedItems = collect($rawItems)
            ->groupBy(function (array $item) {
                return (int) ($item['product_id'] ?? 0);
            })
            ->map(function (SupportCollection $group, int $productId) {
                return [
                    'product_id' => $productId,
                    'quantity' => (int) $group->sum(function (array $item) {
                        return (int) ($item['quantity'] ?? 0);
                    }),
                ];
            })
            ->values();

        return $groupedItems->map(function (array $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);

            if ($productId <= 0) {
                throw new \Exception('Invalid product selected for guest checkout.');
            }

            if ($quantity <= 0) {
                throw new \Exception('Invalid quantity provided for guest checkout item.');
            }

            /** @var Product|null $product */
            $product = $this->productRepository->find($productId);

            if (!$product || !$product->is_active) {
                throw new \Exception("Selected product is unavailable: {$productId}");
            }

            return [
                'product' => $product,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_sku' => $product->sku,
                'quantity' => $quantity,
                // Always use server-side pricing to prevent tampering.
                'price' => (float) $product->current_price,
            ];
        });
    }

    /**
     * Mark abandoned cart as recovered when order is placed
     */
    protected function markAbandonedCartRecovered(?int $userId, int $orderId): void
    {
        if ($userId === null) {
            return;
        }

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

        // Send status update notifications only when order belongs to a user account.
        if ($updatedOrder->user) {
            $updatedOrder->user->notify(new OrderStatusUpdated($updatedOrder, $oldStatus));

            // Send shipped notification if status is shipped
            if ($status === 'shipped') {
                $updatedOrder->user->notify(new OrderShipped(
                    $updatedOrder,
                    $updatedOrder->tracking_number,
                    $updatedOrder->carrier
                ));
            }
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

}
