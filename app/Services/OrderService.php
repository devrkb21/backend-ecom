<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Cart;
use App\Models\Address;
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
        protected BangladeshLocationResolver $locationResolver,
        protected CheckoutTaxService $checkoutTaxService
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

    public function createOrderFromCart(?int $userId, array $shippingData, ?string $checkoutSessionId = null): Order
    {
        return DB::transaction(function () use ($userId, $shippingData, $checkoutSessionId) {
            $isGuestCheckout = $userId === null;
            $guestAccessToken = null;
            $cart = null;
            $coupon = null;
            $discountAmount = 0;
            $authenticatedUser = $userId !== null ? User::query()->find($userId) : null;

            if ($isGuestCheckout) {
                $checkoutItems = $this->buildGuestCheckoutItems($shippingData['items'] ?? []);

                $guestCouponCode = strtoupper(trim((string) ($shippingData['coupon_code'] ?? '')));
                if ($guestCouponCode !== '') {
                    [$coupon, $discountAmount] = $this->resolveGuestCheckoutCoupon($guestCouponCode, $checkoutItems);
                }
            } else {
                $cart = $this->cartRepository->getByUserId($userId);

                if (!$cart || $cart->items->isEmpty()) {
                    throw new \Exception('Cart is empty.');
                }

                $checkoutItems = $cart->items->map(function ($item) {
                    if (!$item->product) {
                        throw new \Exception('One or more cart items are no longer available.');
                    }

                    $variant = $item->variant;

                    if ($item->product_variant_id !== null && !$variant) {
                        throw new \Exception('One or more cart item variants are no longer available.');
                    }

                    if ($variant && !$variant->is_active) {
                        throw new \Exception("Selected variant is unavailable for product: {$item->product->name}");
                    }

                    return [
                        'product' => $item->product,
                        'variant' => $variant,
                        'product_id' => (int) $item->product_id,
                        'product_variant_id' => $item->product_variant_id !== null ? (int) $item->product_variant_id : null,
                        'product_name' => $item->product->name,
                        'product_sku' => $variant?->sku ?: $item->product->sku,
                        'quantity' => (int) $item->quantity,
                        'price' => (float) $item->price,
                    ];
                });

                $coupon = $cart->coupon;
                if ($coupon && $coupon->isValid()) {
                    $discountAmount = (float) $cart->discount_amount;
                }
            }

            $checkoutFields = $this->extractCheckoutFields($shippingData);
            $canonicalShipping = $this->deriveCanonicalShippingData($checkoutFields, $shippingData, $cart, null);
            $checkoutFields = $this->applyBillingFallbackToCheckoutFields($checkoutFields, $canonicalShipping, $shippingData);
            $orderOwner = $this->resolveOrderOwner($authenticatedUser, $canonicalShipping);

            if (!$orderOwner && $isGuestCheckout) {
                $orderOwner = $this->resolveGuestCheckoutUser();
            }

            $orderOwnerId = $orderOwner?->id;
            $stockEnabled = Product::isStockEnabled();

            // Validate stock for all items
            if ($stockEnabled) {
                foreach ($checkoutItems as $item) {
                    /** @var Product $product */
                    $product = $item['product'];
                    /** @var ProductVariant|null $variant */
                    $variant = $item['variant'] ?? null;
                    $quantity = (int) $item['quantity'];

                    if ($variant instanceof ProductVariant) {
                        if (!$variant->hasStock($quantity)) {
                            throw new \Exception("Insufficient stock for variant of product: {$product->name}");
                        }
                    } elseif (!$product->hasStock($quantity)) {
                        throw new \Exception("Insufficient stock for product: {$product->name}");
                    }
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
            $tax = $this->calculateTaxForSubtotal((float) $subtotal);

            // Calculate item count for shipping
            $itemCount = (int) $checkoutItems->sum('quantity');

            $shippingLocationText = trim((string) ($canonicalShipping['shipping_location_text'] ?? ''));
            if ($shippingLocationText === '') {
                $shippingLocationText = trim((string) ($canonicalShipping['shipping_address'] ?? ''));
            }

            $divisionId = !empty($canonicalShipping['shipping_division_id']) ? (int) $canonicalShipping['shipping_division_id'] : null;
            $districtId = !empty($canonicalShipping['shipping_district_id']) ? (int) $canonicalShipping['shipping_district_id'] : null;
            $upazilaId = !empty($canonicalShipping['shipping_upazila_id']) ? (int) $canonicalShipping['shipping_upazila_id'] : null;
            $unionId = !empty($canonicalShipping['shipping_union_id']) ? (int) $canonicalShipping['shipping_union_id'] : null;

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

            // Trust the most specific ID provided and enforce hierarchy consistency.
            // This prevents mismatch errors if the text resolver found a different parent level.
            $union = $unionId ? BdUnion::query()->find($unionId) : null;
            if ($union) {
                $upazilaId = $union->upazila_id;
                $upazila = BdUpazila::query()->find($upazilaId);
                if ($upazila) {
                    $districtId = $upazila->district_id;
                    $district = BdDistrict::query()->find($districtId);
                    if ($district) {
                        $divisionId = $district->division_id;
                        $division = BdDivision::query()->find($divisionId);
                    }
                }
            } else {
                $upazila = $upazilaId ? BdUpazila::query()->find($upazilaId) : null;
                if ($upazila) {
                    $districtId = $upazila->district_id;
                    $district = BdDistrict::query()->find($districtId);
                    if ($district) {
                        $divisionId = $district->division_id;
                        $division = BdDivision::query()->find($divisionId);
                    }
                } else {
                    $district = $districtId ? BdDistrict::query()->find($districtId) : null;
                    if ($district) {
                        $divisionId = $district->division_id;
                        $division = BdDivision::query()->find($divisionId);
                    } else {
                        $division = $divisionId ? BdDivision::query()->find($divisionId) : null;
                    }
                }
            }

            // Ensure models are loaded if they weren't resolved via hierarchy enforcement above.
            $division = $division ?? ($divisionId ? BdDivision::query()->find($divisionId) : null);
            $district = $district ?? ($districtId ? BdDistrict::query()->find($districtId) : null);
            $upazila = $upazila ?? ($upazilaId ? BdUpazila::query()->find($upazilaId) : null);
            $union = $union ?? ($unionId ? BdUnion::query()->find($unionId) : null);

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

            $shippingArea = $canonicalShipping['shipping_area'] ?? null;
            if (empty($shippingArea) && $shippingLocationText !== '') {
                $shippingArea = $shippingLocationText;
            }

            $shippingAddressParts = array_filter([
                $canonicalShipping['shipping_address'] ?? null,
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
            if ($isGuestCheckout) {
                $guestAccessToken = Str::random(64);
            }

            $orderData = [
                'user_id' => $orderOwnerId,
                'coupon_id' => $coupon?->id,
                'coupon_code' => $coupon?->code,
                'discount_amount' => $discountAmount,
                'order_number' => Order::generateOrderNumber(),
                'guest_access_token_hash' => $guestAccessToken !== null ? hash('sha256', $guestAccessToken) : null,
                'status' => 'pending',
                'order_source' => $shippingData['order_source'] ?? 'Web',
                'payment_method' => $paymentMethod,
                'payment_status' => $paymentStatus,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'shipping' => $shipping,
                'shipping_method' => $shippingMethodCode,
                'total' => $total,
                'shipping_name' => $canonicalShipping['shipping_name'],
                'shipping_email' => $canonicalShipping['shipping_email'] ?? ($cart?->user?->email ?? $orderOwner?->email ?? 'customer@local.invalid'),
                'shipping_phone' => $canonicalShipping['shipping_phone'] ?? null,
                'shipping_address' => $normalizedShippingAddress !== '' ? $normalizedShippingAddress : ($canonicalShipping['shipping_address'] ?? 'N/A'),
                'shipping_division_id' => $division?->id,
                'shipping_district_id' => $district?->id,
                'shipping_upazila_id' => $upazila?->id,
                'shipping_union_id' => $union?->id,
                'shipping_city' => $canonicalShipping['shipping_city']
                    ?? $district?->name
                    ?? $upazila?->name
                    ?? ($shippingLocationText !== '' ? $shippingLocationText : 'N/A'),
                'shipping_state' => $canonicalShipping['shipping_state'] ?? $division?->name,
                'shipping_zip' => $canonicalShipping['shipping_zip'] ?? '0000',
                'shipping_country' => $canonicalShipping['shipping_country'] ?? 'Bangladesh',
                'notes' => $canonicalShipping['notes'] ?? null,
                'checkout_fields_payload' => $checkoutFields,
            ];

            // Prepare order items
            $items = [];
            foreach ($checkoutItems as $item) {
                /** @var Product $product */
                $product = $item['product'];

                $items[] = [
                    'product_id' => (int) $item['product_id'],
                    'product_variant_id' => $item['product_variant_id'] ?? null,
                    'product_name' => $item['product_name'],
                    'product_sku' => $item['product_sku'],
                    'quantity' => (int) $item['quantity'],
                    'price' => (float) $item['price'],
                ];

                // Decrement stock
                if ($stockEnabled) {
                    if (($item['variant'] ?? null) instanceof ProductVariant) {
                        $item['variant']->decrementStock((int) $item['quantity']);
                    } else {
                        $product->decrementStock((int) $item['quantity']);
                    }
                }
            }

            // Create order with items
            $order = $this->orderRepository->createWithItems($orderData, $items);

            if ($guestAccessToken !== null) {
                // Expose the one-time guest token to the controller response without storing plain text in DB.
                $order->setAttribute('guest_access_token', $guestAccessToken);
            }

            // Persist checkout addresses for authenticated and matched users.
            $this->persistCheckoutAddresses(
                $orderOwner,
                $checkoutFields,
                $canonicalShipping,
                $division,
                $district,
                $upazila,
                $union,
                $shippingData
            );

            // Record coupon usage
            if ($coupon && $discountAmount > 0) {
                if ($orderOwnerId !== null) {
                    CouponUsage::create([
                        'coupon_id' => $coupon->id,
                        'user_id' => $orderOwnerId,
                        'order_id' => $order->id,
                        'discount_amount' => $discountAmount,
                    ]);
                }

                $coupon->incrementUsage();
            }

            // Clear the cart
            if ($cart) {
                $this->cartRepository->clearCart($cart->id);
            }

            // Remove matching incomplete abandoned-cart records once checkout is completed.
            $this->removeCompletedCheckoutFromAbandonedCarts($orderOwnerId, $canonicalShipping, $checkoutSessionId);

            // Send order confirmation notification
            if ($order->user) {
                $order->user->notify(new OrderConfirmation($order));
            }

            return $order;
        });
    }

    public function createRecoveredOrderFromAbandonedCart(AbandonedCart $abandonedCart): Order
    {
        if (!in_array($abandonedCart->status, ['pending', 'follow_up'], true)) {
            throw new \Exception('Only incomplete checkouts can be marked as recovered.');
        }

        $rawItems = is_array($abandonedCart->cart_items) ? $abandonedCart->cart_items : [];
        if (empty($rawItems)) {
            throw new \Exception('Unable to recover checkout: cart item snapshot is missing.');
        }

        return DB::transaction(function () use ($abandonedCart, $rawItems) {
            $stockEnabled = Product::isStockEnabled();

            $groupedItems = collect($rawItems)
                ->map(function (array $item) {
                    return [
                        'product_id' => (int) ($item['product_id'] ?? 0),
                        'product_variant_id' => isset($item['variant_id']) && $item['variant_id'] !== null
                            ? (int) $item['variant_id']
                            : null,
                        'quantity' => max(0, (int) ($item['quantity'] ?? 0)),
                        'price' => max(0, (float) ($item['price'] ?? 0)),
                    ];
                })
                ->filter(fn (array $item) => $item['product_id'] > 0 && $item['quantity'] > 0)
                ->groupBy(function (array $item) {
                    return $item['product_id'] . ':' . ($item['product_variant_id'] ?? 0);
                })
                ->map(function (SupportCollection $items, string $identity) {
                    [$rawProductId, $rawVariantId] = array_pad(explode(':', $identity, 2), 2, '0');

                    return [
                        'product_id' => (int) $rawProductId,
                        'product_variant_id' => ((int) $rawVariantId) > 0 ? (int) $rawVariantId : null,
                        'quantity' => (int) $items->sum('quantity'),
                        'price' => (float) ($items->last()['price'] ?? 0),
                    ];
                })
                ->values();

            if ($groupedItems->isEmpty()) {
                throw new \Exception('Unable to recover checkout: no valid items found in snapshot.');
            }

            $products = Product::query()
                ->whereIn('id', $groupedItems->pluck('product_id')->all())
                ->get()
                ->keyBy('id');

            $variantIds = $groupedItems
                ->pluck('product_variant_id')
                ->filter(fn ($id) => $id !== null)
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();

            $variants = empty($variantIds)
                ? collect()
                : ProductVariant::query()->whereIn('id', $variantIds)->get()->keyBy('id');

            $items = [];
            $computedSubtotal = 0.0;

            foreach ($groupedItems as $item) {
                $productId = (int) ($item['product_id'] ?? 0);
                $variantId = $item['product_variant_id'] !== null ? (int) $item['product_variant_id'] : null;
                $quantity = (int) ($item['quantity'] ?? 0);

                /** @var Product|null $product */
                $product = $products->get($productId);
                if (!$product) {
                    throw new \Exception("Recovered checkout product is missing: {$productId}");
                }

                if (!$product->is_active) {
                    throw new \Exception("Recovered checkout product is inactive: {$product->name}");
                }

                /** @var ProductVariant|null $variant */
                $variant = null;
                if ($variantId !== null) {
                    $variant = $variants->get($variantId);

                    if (!$variant || (int) $variant->product_id !== $product->id) {
                        throw new \Exception("Recovered checkout variant is invalid for product: {$product->name}");
                    }

                    if (!$variant->is_active) {
                        throw new \Exception("Recovered checkout variant is inactive for product: {$product->name}");
                    }

                    if ($stockEnabled && !$variant->hasStock($quantity)) {
                        throw new \Exception("Insufficient stock for recovered checkout variant: {$product->name}");
                    }
                } elseif ($stockEnabled && !$product->hasStock($quantity)) {
                    throw new \Exception("Insufficient stock for recovered checkout product: {$product->name}");
                }

                $unitPrice = (float) ($item['price'] ?? 0);
                if ($unitPrice <= 0) {
                    $unitPrice = (float) $product->getPriceForQuantity($quantity);

                    if ($variant instanceof ProductVariant) {
                        $customDiscountedPrice = $variant->getRawOriginal('discounted_price');
                        if ($customDiscountedPrice !== null) {
                            $unitPrice = round(max(0, (float) $customDiscountedPrice), 2);
                        } else {
                            $unitPrice = round($unitPrice + (float) $variant->price_adjustment, 2);
                        }
                    }
                }

                $computedSubtotal += ($unitPrice * $quantity);

                $items[] = [
                    'product_id' => $product->id,
                    'product_variant_id' => $variant?->id,
                    'product_name' => $product->name,
                    'product_sku' => $variant?->sku ?: $product->sku,
                    'quantity' => $quantity,
                    'price' => $unitPrice,
                ];
            }

            $subtotal = round(max(0, (float) ($abandonedCart->subtotal ?? 0)), 2);
            if ($subtotal <= 0) {
                $subtotal = round($computedSubtotal, 2);
            }

            $discountAmount = round(max(0, (float) ($abandonedCart->discount_amount ?? 0)), 2);
            if ($discountAmount > $subtotal) {
                $discountAmount = $subtotal;
            }

            $tax = $this->calculateTaxForSubtotal($subtotal);

            $baseTotal = round(max(0, $subtotal + $tax - $discountAmount), 2);
            $snapshotTotal = round(max(0, (float) ($abandonedCart->total ?? 0)), 2);
            $total = $snapshotTotal > 0 ? max($baseTotal, $snapshotTotal) : $baseTotal;
            $shippingAmount = round(max(0, $total - $baseTotal), 2);

            $shippingEmail = strtolower(trim((string) ($abandonedCart->email ?? '')));
            if ($shippingEmail === '' || !filter_var($shippingEmail, FILTER_VALIDATE_EMAIL)) {
                $shippingEmail = 'customer@local.invalid';
            }

            $abandonedCart->loadMissing('user');

            $orderOwner = $this->resolveOrderOwner($abandonedCart->user, [
                'shipping_email' => $shippingEmail,
                'shipping_phone' => $abandonedCart->phone,
            ]);

            $shippingName = $this->findFirstNonEmptyValue([
                $abandonedCart->name,
                $orderOwner?->name,
                'Customer',
            ]);

            $shippingPhone = $this->nullableString($this->firstPresentValue([
                $abandonedCart->phone,
                $orderOwner?->phone,
            ]));

            $shippingAddress = $this->findFirstNonEmptyValue([
                $abandonedCart->shipping_address,
                $abandonedCart->shipping_location_text,
                'Address not provided',
            ]);

            $shippingCity = $this->findFirstNonEmptyValue([
                $abandonedCart->shipping_city,
                $abandonedCart->shipping_district,
                'N/A',
            ]);

            $shippingState = $this->nullableString($this->firstPresentValue([
                $abandonedCart->shipping_state,
                $abandonedCart->shipping_division,
            ]));

            $shippingZip = $this->findFirstNonEmptyValue([
                $abandonedCart->shipping_zip,
                '0000',
            ]);

            $shippingCountry = $this->toCountryName((string) $this->firstPresentValue([
                $abandonedCart->shipping_country,
                'Bangladesh',
            ]));

            $couponCode = $this->nullableString($abandonedCart->coupon_code);
            $couponId = null;
            if ($couponCode !== null) {
                $couponId = Coupon::query()
                    ->whereRaw('LOWER(code) = ?', [strtolower($couponCode)])
                    ->value('id');
            }

            $recoveryNotes = [
                'Recovered from abandoned cart #' . $abandonedCart->id,
            ];

            if ($this->nullableString($abandonedCart->admin_notes) !== null) {
                $recoveryNotes[] = 'Admin notes: ' . $this->nullableString($abandonedCart->admin_notes);
            }

            $checkoutPayload = array_filter([
                'source' => 'admin_recovered_abandoned_cart',
                'abandoned_cart_id' => $abandonedCart->id,
                'shipping_location_text' => $this->nullableString($abandonedCart->shipping_location_text),
                'shipping_area' => $this->nullableString($abandonedCart->shipping_area),
                'shipping_division' => $this->nullableString($abandonedCart->shipping_division),
                'shipping_district' => $this->nullableString($abandonedCart->shipping_district),
                'shipping_upazila' => $this->nullableString($abandonedCart->shipping_upazila),
                'shipping_union' => $this->nullableString($abandonedCart->shipping_union),
            ], fn ($value) => $value !== null && $value !== '');

            $orderData = [
                'user_id' => $orderOwner?->id,
                'coupon_id' => $couponId,
                'coupon_code' => $couponCode,
                'discount_amount' => $discountAmount,
                'order_number' => Order::generateOrderNumber(),
                'status' => 'pending',
                'order_source' => 'Abandoned Cart',
                'payment_method' => $this->nullableString($abandonedCart->payment_method) ?? 'cod',
                'payment_status' => 'pending',
                'subtotal' => $subtotal,
                'tax' => $tax,
                'shipping' => $shippingAmount,
                'shipping_method' => $this->nullableString($abandonedCart->shipping_method) ?? 'manual_recovery',
                'total' => round($total, 2),
                'shipping_name' => $shippingName,
                'shipping_email' => $shippingEmail,
                'shipping_phone' => $shippingPhone,
                'shipping_address' => $shippingAddress,
                'shipping_city' => $shippingCity,
                'shipping_state' => $shippingState,
                'shipping_zip' => $shippingZip,
                'shipping_country' => $shippingCountry,
                'notes' => implode("\n", $recoveryNotes),
                'checkout_fields_payload' => $checkoutPayload,
            ];

            $order = $this->orderRepository->createWithItems($orderData, $items);

            if ($stockEnabled) {
                foreach ($items as $item) {
                    $variantId = $item['product_variant_id'] ?? null;

                    if ($variantId !== null) {
                        /** @var ProductVariant|null $variant */
                        $variant = $variants->get((int) $variantId);
                        if ($variant) {
                            $variant->decrementStock((int) $item['quantity']);
                            continue;
                        }
                    }

                    /** @var Product|null $product */
                    $product = $products->get((int) $item['product_id']);
                    if ($product) {
                        $product->decrementStock((int) $item['quantity']);
                    }
                }
            }

            $abandonedCart->markAsRecovered($order->id);

            if ($order->user) {
                $order->user->notify(new OrderConfirmation($order));
            }

            return $order;
        });
    }

    protected function resolveOrderOwner(?User $authenticatedUser, array $canonicalShipping): ?User
    {
        if ($authenticatedUser) {
            return $authenticatedUser;
        }

        $shippingEmail = strtolower(trim((string) ($canonicalShipping['shipping_email'] ?? '')));
        $shippingPhone = (string) ($canonicalShipping['shipping_phone'] ?? '');

        return $this->findExistingUserByContact($shippingEmail, $shippingPhone);
    }

    protected function findExistingUserByContact(string $email, string $phone): ?User
    {
        $guestEmail = strtolower(trim((string) config('shop.guest_checkout_user_email', 'guest.checkout@innercollection.local')));

        if (
            $email !== ''
            && $email !== 'customer@local.invalid'
            && $email !== $guestEmail
            && filter_var($email, FILTER_VALIDATE_EMAIL)
        ) {
            $matchedByEmail = User::query()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->first();

            if ($matchedByEmail) {
                return $matchedByEmail;
            }
        }

        $normalizedPhone = $this->normalizePhone($phone);
        if ($normalizedPhone === '') {
            return null;
        }

        $normalizedPhoneSql = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '(', ''), ')', ''), '+', '')";

        $matchedCustomerByPhone = User::query()
            ->where('role', User::ROLE_CUSTOMER)
            ->whereRaw($normalizedPhoneSql . ' = ?', [$normalizedPhone])
            ->orderByDesc('id')
            ->first();

        if ($matchedCustomerByPhone) {
            return $matchedCustomerByPhone;
        }

        return User::query()
            ->whereRaw($normalizedPhoneSql . ' = ?', [$normalizedPhone])
            ->orderByDesc('id')
            ->first();
    }

    protected function normalizePhone(?string $phone): string
    {
        return preg_replace('/\D+/', '', (string) ($phone ?? '')) ?? '';
    }

    protected function shouldPersistCheckoutAddressesForUser(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        $guestEmail = strtolower(trim((string) config('shop.guest_checkout_user_email', 'guest.checkout@innercollection.local')));
        $userEmail = strtolower(trim((string) $user->email));

        return $userEmail !== '' && $userEmail !== $guestEmail;
    }

    protected function persistCheckoutAddresses(
        ?User $orderOwner,
        array $checkoutFields,
        array $canonicalShipping,
        ?BdDivision $division,
        ?BdDistrict $district,
        ?BdUpazila $upazila,
        ?BdUnion $union,
        array $shippingData
    ): void {
        if (!$this->shouldPersistCheckoutAddressesForUser($orderOwner)) {
            return;
        }

        $shippingAddressPayload = $this->buildShippingAddressPayload(
            $canonicalShipping,
            $orderOwner,
            $division,
            $district,
            $upazila,
            $union
        );

        $this->upsertDefaultUserAddress($orderOwner->id, 'shipping', $shippingAddressPayload);

        $hasBillingInput = $this->hasAnyCheckoutValue($checkoutFields, [
            'billing_first_name',
            'billing_last_name',
            'billing_name',
            'billing_email',
            'billing_phone',
            'billing_address_1',
            'billing_address_2',
            'billing_city',
            'billing_state',
            'billing_postcode',
            'billing_country',
        ]);

        $useBillingAddress = $this->toBooleanFlag($shippingData['use_billing_address'] ?? false);

        if (!$useBillingAddress && !$hasBillingInput) {
            return;
        }

        $billingAddressPayload = $this->buildBillingAddressPayload(
            $checkoutFields,
            $canonicalShipping,
            $shippingAddressPayload
        );

        $this->upsertDefaultUserAddress($orderOwner->id, 'billing', $billingAddressPayload);
    }

    protected function buildShippingAddressPayload(
        array $canonicalShipping,
        User $orderOwner,
        ?BdDivision $division,
        ?BdDistrict $district,
        ?BdUpazila $upazila,
        ?BdUnion $union
    ): array {
        $shippingAddressLine = $this->findFirstNonEmptyValue([
            $canonicalShipping['shipping_address'] ?? null,
            $canonicalShipping['shipping_location_text'] ?? null,
            'Address not provided',
        ]);

        $shippingCity = $this->findFirstNonEmptyValue([
            $canonicalShipping['shipping_city'] ?? null,
            $district?->name,
            $upazila?->name,
            'N/A',
        ]);

        return [
            'label' => 'Checkout Shipping',
            'name' => $this->findFirstNonEmptyValue([
                $canonicalShipping['shipping_name'] ?? null,
                $orderOwner->name,
                'Customer',
            ]),
            'phone' => $this->findFirstNonEmptyValue([
                $canonicalShipping['shipping_phone'] ?? null,
                $orderOwner->phone,
                'N/A',
            ]),
            'email' => $this->nullableString($this->firstPresentValue([
                $canonicalShipping['shipping_email'] ?? null,
                $orderOwner->email,
            ])),
            'address_line_1' => $shippingAddressLine,
            'address_line_2' => $this->nullableString($canonicalShipping['shipping_area'] ?? null),
            'division_id' => $division?->id,
            'district_id' => $district?->id,
            'upazila_id' => $upazila?->id,
            'union_id' => $union?->id,
            'area' => $this->nullableString($canonicalShipping['shipping_area'] ?? null),
            'city' => $shippingCity,
            'state' => $this->nullableString($this->findFirstNonEmptyValue([
                $canonicalShipping['shipping_state'] ?? null,
                $division?->name,
            ])),
            'postal_code' => $this->nullableString($canonicalShipping['shipping_zip'] ?? null),
            'country' => $this->toCountryName((string) ($canonicalShipping['shipping_country'] ?? 'Bangladesh')),
            'instructions' => $this->nullableString($canonicalShipping['notes'] ?? null),
        ];
    }

    protected function buildBillingAddressPayload(
        array $checkoutFields,
        array $canonicalShipping,
        array $shippingAddressPayload
    ): array {
        $billingFirstName = $this->findFirstNonEmptyValue([
            $checkoutFields['billing_first_name'] ?? null,
        ]);

        $billingLastName = $this->findFirstNonEmptyValue([
            $checkoutFields['billing_last_name'] ?? null,
        ]);

        $billingFullName = trim($billingFirstName . ' ' . $billingLastName);

        return [
            'label' => 'Checkout Billing',
            'name' => $this->findFirstNonEmptyValue([
                $checkoutFields['billing_name'] ?? null,
                $billingFullName,
                $shippingAddressPayload['name'] ?? null,
                'Customer',
            ]),
            'phone' => $this->findFirstNonEmptyValue([
                $checkoutFields['billing_phone'] ?? null,
                $shippingAddressPayload['phone'] ?? null,
                'N/A',
            ]),
            'email' => $this->nullableString($this->firstPresentValue([
                $checkoutFields['billing_email'] ?? null,
                $shippingAddressPayload['email'] ?? null,
            ])),
            'address_line_1' => $this->findFirstNonEmptyValue([
                $checkoutFields['billing_address_1'] ?? null,
                $canonicalShipping['shipping_address'] ?? null,
                $shippingAddressPayload['address_line_1'] ?? null,
                'Address not provided',
            ]),
            'address_line_2' => $this->nullableString($this->firstPresentValue([
                $checkoutFields['billing_address_2'] ?? null,
                $shippingAddressPayload['address_line_2'] ?? null,
            ])),
            'division_id' => $this->toNullableInt($shippingAddressPayload['division_id'] ?? null),
            'district_id' => $this->toNullableInt($shippingAddressPayload['district_id'] ?? null),
            'upazila_id' => $this->toNullableInt($shippingAddressPayload['upazila_id'] ?? null),
            'union_id' => $this->toNullableInt($shippingAddressPayload['union_id'] ?? null),
            'area' => $this->nullableString($this->firstPresentValue([
                $checkoutFields['billing_address_2'] ?? null,
                $shippingAddressPayload['area'] ?? null,
            ])),
            'city' => $this->findFirstNonEmptyValue([
                $checkoutFields['billing_city'] ?? null,
                $shippingAddressPayload['city'] ?? null,
                'N/A',
            ]),
            'state' => $this->nullableString($this->firstPresentValue([
                $checkoutFields['billing_state'] ?? null,
                $shippingAddressPayload['state'] ?? null,
            ])),
            'postal_code' => $this->nullableString($this->firstPresentValue([
                $checkoutFields['billing_postcode'] ?? null,
                $shippingAddressPayload['postal_code'] ?? null,
            ])),
            'country' => $this->toCountryName((string) $this->firstPresentValue([
                $checkoutFields['billing_country'] ?? null,
                $shippingAddressPayload['country'] ?? null,
                'Bangladesh',
            ])),
            'instructions' => $this->nullableString($canonicalShipping['notes'] ?? null),
        ];
    }

    protected function upsertDefaultUserAddress(int $userId, string $addressType, array $addressData): void
    {
        $normalizedAddressType = in_array($addressType, ['shipping', 'billing', 'both'], true) ? $addressType : 'shipping';

        $existingAddressQuery = Address::query()
            ->where('user_id', $userId)
            ->where(function ($query) use ($normalizedAddressType) {
                if ($normalizedAddressType === 'shipping') {
                    $query->whereIn('type', ['shipping', 'both']);

                    return;
                }

                if ($normalizedAddressType === 'billing') {
                    $query->whereIn('type', ['billing', 'both']);

                    return;
                }

                $query->where('type', 'both');
            })
            ->orderByDesc('is_default')
            ->orderByDesc('updated_at');

        /** @var Address|null $existingAddress */
        $existingAddress = $existingAddressQuery->first();

        if ($existingAddress) {
            $existingAddress->fill(array_merge($addressData, [
                'type' => $this->mergeAddressType($existingAddress->type, $normalizedAddressType),
                'is_default' => true,
            ]));
            $existingAddress->save();
            $existingAddress->setAsDefault();

            return;
        }

        $createdAddress = Address::query()->create(array_merge($addressData, [
            'user_id' => $userId,
            'type' => $normalizedAddressType,
            'is_default' => true,
        ]));

        $createdAddress->setAsDefault();
    }

    protected function mergeAddressType(string $existingType, string $incomingType): string
    {
        if ($existingType === 'both' || $incomingType === 'both') {
            return 'both';
        }

        if ($existingType === $incomingType) {
            return $existingType;
        }

        if (
            ($existingType === 'shipping' && $incomingType === 'billing')
            || ($existingType === 'billing' && $incomingType === 'shipping')
        ) {
            return 'both';
        }

        return $incomingType;
    }

    protected function hasAnyCheckoutValue(array $checkoutFields, array $keys): bool
    {
        foreach ($keys as $key) {
            if ($this->findFirstNonEmptyValue([$checkoutFields[$key] ?? null]) !== '') {
                return true;
            }
        }

        return false;
    }

    protected function applyBillingFallbackToCheckoutFields(array $checkoutFields, array $canonicalShipping, array $payload): array
    {
        $billingKeys = [
            'billing_first_name',
            'billing_last_name',
            'billing_name',
            'billing_email',
            'billing_phone',
            'billing_address_1',
            'billing_address_2',
            'billing_city',
            'billing_state',
            'billing_postcode',
            'billing_country',
        ];

        $useBillingAddress = $this->toBooleanFlag($payload['use_billing_address'] ?? false);
        $hasBillingInput = $this->hasAnyCheckoutValue($checkoutFields, $billingKeys);

        $shippingName = trim((string) ($canonicalShipping['shipping_name'] ?? ''));
        $nameParts = preg_split('/\s+/', $shippingName, 2) ?: [];

        $fallbackValues = [
            'billing_first_name' => $nameParts[0] ?? null,
            'billing_last_name' => $nameParts[1] ?? null,
            'billing_name' => $shippingName !== '' ? $shippingName : null,
            'billing_email' => $canonicalShipping['shipping_email'] ?? null,
            'billing_phone' => $canonicalShipping['shipping_phone'] ?? null,
            'billing_address_1' => $canonicalShipping['shipping_address'] ?? null,
            'billing_address_2' => $canonicalShipping['shipping_area'] ?? null,
            'billing_city' => $canonicalShipping['shipping_city'] ?? null,
            'billing_state' => $canonicalShipping['shipping_state'] ?? null,
            'billing_postcode' => $canonicalShipping['shipping_zip'] ?? null,
            'billing_country' => $canonicalShipping['shipping_country'] ?? null,
        ];

        foreach ($fallbackValues as $key => $fallbackValue) {
            $normalizedFallback = trim((string) ($fallbackValue ?? ''));
            if ($normalizedFallback === '') {
                continue;
            }

            $existingValue = trim((string) ($checkoutFields[$key] ?? ''));

            // If billing section is unchecked or empty, fully mirror shipping to billing.
            if (!$useBillingAddress || !$hasBillingInput) {
                $checkoutFields[$key] = $normalizedFallback;
                continue;
            }

            // If billing section is used but partially filled, fill only missing billing values from shipping.
            if ($existingValue === '') {
                $checkoutFields[$key] = $normalizedFallback;
            }
        }

        return $checkoutFields;
    }

    protected function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : null;
    }

    protected function toBooleanFlag(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
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

        $stockEnabled = Product::isStockEnabled();

        $groupedItems = collect($rawItems)
            ->groupBy(function (array $item) {
                $productId = (int) ($item['product_id'] ?? 0);
                $variantId = isset($item['variant_id']) && $item['variant_id'] !== null
                    ? (int) $item['variant_id']
                    : 0;

                return $productId . ':' . $variantId;
            })
            ->map(function (SupportCollection $group, string $groupKey) {
                [$rawProductId, $rawVariantId] = array_pad(explode(':', $groupKey, 2), 2, '0');

                return [
                    'product_id' => (int) $rawProductId,
                    'product_variant_id' => ((int) $rawVariantId) > 0 ? (int) $rawVariantId : null,
                    'quantity' => (int) $group->sum(function (array $item) {
                        return (int) ($item['quantity'] ?? 0);
                    }),
                ];
            })
            ->values();

        return $groupedItems->map(function (array $item) use ($stockEnabled) {
            $productId = (int) ($item['product_id'] ?? 0);
            $variantId = $item['product_variant_id'] !== null ? (int) $item['product_variant_id'] : null;
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

            $variant = null;
            if ($variantId !== null) {
                $variant = $product->variants()->where('id', $variantId)->first();

                if (!$variant) {
                    throw new \Exception("Selected variant is invalid for product: {$product->name}");
                }

                if (!$variant->is_active) {
                    throw new \Exception("Selected variant is unavailable for product: {$product->name}");
                }
            }

            if ($stockEnabled) {
                if ($variant instanceof ProductVariant) {
                    if (!$variant->hasStock($quantity)) {
                        throw new \Exception("Insufficient stock for variant of product: {$product->name}");
                    }
                } elseif (!$product->hasStock($quantity)) {
                    throw new \Exception("Insufficient stock for product: {$product->name}");
                }
            }

            $unitPrice = $product->getPriceForQuantity($quantity);
            if ($variant instanceof ProductVariant) {
                $customDiscountedPrice = $variant->getRawOriginal('discounted_price');
                if ($customDiscountedPrice !== null) {
                    $unitPrice = round(max(0, (float) $customDiscountedPrice), 2);
                } else {
                    $unitPrice = round((float) $unitPrice + (float) $variant->price_adjustment, 2);
                }
            }

            return [
                'product' => $product,
                'variant' => $variant,
                'product_id' => $product->id,
                'product_variant_id' => $variant?->id,
                'product_name' => $product->name,
                'product_sku' => $variant?->sku ?: $product->sku,
                'quantity' => $quantity,
                // Always use server-side pricing to prevent tampering.
                'price' => (float) $unitPrice,
            ];
        });
    }

    protected function resolveGuestCheckoutCoupon(string $couponCode, SupportCollection $checkoutItems): array
    {
        $coupon = Coupon::findByCode($couponCode);

        if (!$coupon) {
            throw new \Exception('Invalid coupon code.');
        }

        if (!$coupon->allow_guest_checkout) {
            throw new \Exception('Please login to use this coupon.');
        }

        $previewCart = new Cart();
        $previewCart->setAttribute('user_id', null);
        $previewCart->setRelation('items', $checkoutItems->map(function (array $item) {
            return (object) [
                'product_id' => (int) $item['product_id'],
                'product_variant_id' => $item['product_variant_id'] !== null ? (int) $item['product_variant_id'] : null,
                'variant_id' => $item['product_variant_id'] !== null ? (int) $item['product_variant_id'] : null,
                'quantity' => (int) $item['quantity'],
                'price' => (float) $item['price'],
                'product' => $item['product'],
                'variant' => $item['variant'] ?? null,
            ];
        }));

        $errors = $coupon->validateForCart($previewCart);
        if (!empty($errors)) {
            throw new \Exception($errors[0]);
        }

        $discountAmount = (float) $coupon->calculateDiscount($previewCart);

        return [$coupon, $discountAmount];
    }

    protected function extractCheckoutFields(array $payload): array
    {
        $fields = [];

        if (isset($payload['checkout_fields']) && is_array($payload['checkout_fields'])) {
            $fields = $payload['checkout_fields'];
        }

        $legacyKeys = [
            'shipping_name',
            'shipping_email',
            'shipping_phone',
            'shipping_address',
            'shipping_area',
            'shipping_location_text',
            'shipping_division_id',
            'shipping_district_id',
            'shipping_upazila_id',
            'shipping_union_id',
            'shipping_city',
            'shipping_state',
            'shipping_zip',
            'shipping_country',
            'notes',
            'order_notes',
            'billing_first_name',
            'billing_last_name',
            'billing_name',
            'billing_email',
            'billing_phone',
            'billing_address_1',
            'billing_address_2',
            'billing_city',
            'billing_state',
            'billing_postcode',
            'billing_country',
        ];

        foreach ($legacyKeys as $key) {
            if (array_key_exists($key, $payload) && !array_key_exists($key, $fields)) {
                $fields[$key] = $payload[$key];
            }
        }

        $normalized = [];

        foreach ($fields as $key => $value) {
            $fieldKey = strtolower(trim((string) $key));
            if ($fieldKey === '') {
                continue;
            }

            if (is_scalar($value) || $value === null) {
                $normalized[$fieldKey] = $value;
            }
        }

        return $normalized;
    }

    protected function deriveCanonicalShippingData(array $checkoutFields, array $payload, ?Cart $cart, ?User $guestUser): array
    {
        $semantic = $this->deriveSchemaSemanticFields($checkoutFields);

        $fallbackName = trim((string) ($cart?->user?->name ?? $guestUser?->name ?? 'Customer'));
        $fallbackEmail = trim((string) ($cart?->user?->email ?? $guestUser?->email ?? 'customer@local.invalid'));

        $billingFirstName = $this->findFirstNonEmptyValue([
            $checkoutFields['billing_first_name'] ?? null,
            $semantic['billing_first_name'] ?? null,
        ]);
        $billingLastName = $this->findFirstNonEmptyValue([
            $checkoutFields['billing_last_name'] ?? null,
            $semantic['billing_last_name'] ?? null,
        ]);

        $billingFullName = trim($billingFirstName . ' ' . $billingLastName);

        $shippingName = $this->findFirstNonEmptyValue([
            $checkoutFields['shipping_name'] ?? null,
            $semantic['shipping_name'] ?? null,
            $checkoutFields['billing_name'] ?? null,
            $billingFullName,
        ]);

        if ($shippingName === '') {
            $shippingName = $fallbackName !== '' ? $fallbackName : 'Customer';
        }

        $shippingEmail = $this->findFirstNonEmptyValue([
            $checkoutFields['shipping_email'] ?? null,
            $semantic['shipping_email'] ?? null,
            $checkoutFields['billing_email'] ?? null,
            $fallbackEmail,
        ]);

        if ($shippingEmail === '' || !filter_var($shippingEmail, FILTER_VALIDATE_EMAIL)) {
            $shippingEmail = $fallbackEmail !== '' ? $fallbackEmail : 'customer@local.invalid';
        }

        $shippingPhone = $this->findFirstNonEmptyValue([
            $checkoutFields['shipping_phone'] ?? null,
            $semantic['shipping_phone'] ?? null,
            $checkoutFields['billing_phone'] ?? null,
        ]);

        $shippingAddress = $this->findFirstNonEmptyValue([
            $checkoutFields['shipping_address'] ?? null,
            $semantic['shipping_address'] ?? null,
            $checkoutFields['billing_address_1'] ?? null,
            $checkoutFields['address'] ?? null,
        ]);

        $shippingLocationText = $this->findFirstNonEmptyValue([
            $checkoutFields['shipping_location_text'] ?? null,
            $semantic['shipping_location_text'] ?? null,
            $checkoutFields['shipping_area'] ?? null,
            $checkoutFields['billing_city'] ?? null,
        ]);

        if ($shippingAddress === '') {
            $shippingAddress = $shippingLocationText !== '' ? $shippingLocationText : 'Address not provided';
        }

        $shippingArea = $this->findFirstNonEmptyValue([
            $checkoutFields['shipping_area'] ?? null,
            $semantic['shipping_area'] ?? null,
            $checkoutFields['billing_address_2'] ?? null,
        ]);

        $shippingCity = $this->findFirstNonEmptyValue([
            $checkoutFields['shipping_city'] ?? null,
            $semantic['shipping_city'] ?? null,
            $checkoutFields['billing_city'] ?? null,
        ]);

        $shippingState = $this->findFirstNonEmptyValue([
            $checkoutFields['shipping_state'] ?? null,
            $semantic['shipping_state'] ?? null,
            $checkoutFields['billing_state'] ?? null,
        ]);

        $shippingZip = $this->findFirstNonEmptyValue([
            $checkoutFields['shipping_zip'] ?? null,
            $semantic['shipping_zip'] ?? null,
            $checkoutFields['billing_postcode'] ?? null,
            $checkoutFields['postcode'] ?? null,
        ]);

        $shippingCountryRaw = $this->findFirstNonEmptyValue([
            $checkoutFields['shipping_country'] ?? null,
            $semantic['shipping_country'] ?? null,
            $checkoutFields['billing_country'] ?? null,
            'Bangladesh',
        ]);

        $notes = $this->findFirstNonEmptyValue([
            $payload['notes'] ?? null,
            $checkoutFields['notes'] ?? null,
            $checkoutFields['order_notes'] ?? null,
            $semantic['notes'] ?? null,
        ]);

        return [
            'shipping_name' => $shippingName,
            'shipping_email' => $shippingEmail,
            'shipping_phone' => $shippingPhone !== '' ? $shippingPhone : null,
            'shipping_address' => $shippingAddress,
            'shipping_area' => $shippingArea !== '' ? $shippingArea : null,
            'shipping_location_text' => $shippingLocationText !== '' ? $shippingLocationText : null,
            'shipping_division_id' => $this->toNullableInt($this->firstPresentValue([
                $checkoutFields['shipping_division_id'] ?? null,
                $semantic['shipping_division_id'] ?? null,
            ])),
            'shipping_district_id' => $this->toNullableInt($this->firstPresentValue([
                $checkoutFields['shipping_district_id'] ?? null,
                $semantic['shipping_district_id'] ?? null,
            ])),
            'shipping_upazila_id' => $this->toNullableInt($this->firstPresentValue([
                $checkoutFields['shipping_upazila_id'] ?? null,
                $semantic['shipping_upazila_id'] ?? null,
            ])),
            'shipping_union_id' => $this->toNullableInt($this->firstPresentValue([
                $checkoutFields['shipping_union_id'] ?? null,
                $semantic['shipping_union_id'] ?? null,
            ])),
            'shipping_city' => $shippingCity !== '' ? $shippingCity : null,
            'shipping_state' => $shippingState !== '' ? $shippingState : null,
            'shipping_zip' => $shippingZip !== '' ? $shippingZip : null,
            'shipping_country' => $this->toCountryName($shippingCountryRaw),
            'notes' => $notes !== '' ? $notes : null,
        ];
    }

    protected function deriveSchemaSemanticFields(array $checkoutFields): array
    {
        /** @var CheckoutAddressConfigService $checkoutConfigService */
        $checkoutConfigService = app(CheckoutAddressConfigService::class);
        $enabledFields = $checkoutConfigService->getEnabledFields();

        $mapped = [];
        $firstName = '';
        $lastName = '';

        foreach ($enabledFields as $field) {
            $key = strtolower(trim((string) ($field['key'] ?? '')));
            if ($key === '' || !array_key_exists($key, $checkoutFields)) {
                continue;
            }

            $value = $checkoutFields[$key];
            $stringValue = trim((string) $value);
            if ($stringValue === '' && !is_numeric($value)) {
                continue;
            }

            $type = strtolower(trim((string) ($field['type'] ?? 'text')));
            $label = strtolower(trim((string) ($field['label'] ?? '')));
            $descriptor = strtolower($key . ' ' . $label);

            if ($type === 'email' && empty($mapped['shipping_email'])) {
                $mapped['shipping_email'] = $stringValue;
                continue;
            }

            if ($type === 'tel' && empty($mapped['shipping_phone'])) {
                $mapped['shipping_phone'] = $stringValue;
                continue;
            }

            if ($type === 'country' && empty($mapped['shipping_country'])) {
                $mapped['shipping_country'] = $stringValue;
                continue;
            }

            if ($type === 'location_text' && empty($mapped['shipping_location_text'])) {
                $mapped['shipping_location_text'] = $stringValue;
                continue;
            }

            if ($type === 'location_division') {
                $mapped['shipping_division_id'] = $value;
                continue;
            }

            if ($type === 'location_district') {
                $mapped['shipping_district_id'] = $value;
                continue;
            }

            if ($type === 'location_upazila') {
                $mapped['shipping_upazila_id'] = $value;
                continue;
            }

            if ($type === 'location_union') {
                $mapped['shipping_union_id'] = $value;
                continue;
            }

            if ($this->containsAny($descriptor, ['first name', 'first_name', 'firstname'])) {
                $firstName = $stringValue;
                $mapped['billing_first_name'] = $stringValue;
                continue;
            }

            if ($this->containsAny($descriptor, ['last name', 'last_name', 'lastname'])) {
                $lastName = $stringValue;
                $mapped['billing_last_name'] = $stringValue;
                continue;
            }

            if ($this->containsAny($descriptor, ['name', 'receiver', 'customer']) && empty($mapped['shipping_name'])) {
                $mapped['shipping_name'] = $stringValue;
                continue;
            }

            if ($this->containsAny($descriptor, ['address 2', 'address_2', 'apartment', 'suite', 'area', 'neighborhood']) && empty($mapped['shipping_area'])) {
                $mapped['shipping_area'] = $stringValue;
                continue;
            }

            if ($this->containsAny($descriptor, ['address', 'street']) && empty($mapped['shipping_address'])) {
                $mapped['shipping_address'] = $stringValue;
                continue;
            }

            if ($this->containsAny($descriptor, ['city', 'town']) && empty($mapped['shipping_city'])) {
                $mapped['shipping_city'] = $stringValue;
                continue;
            }

            if ($this->containsAny($descriptor, ['state', 'county', 'province']) && empty($mapped['shipping_state'])) {
                $mapped['shipping_state'] = $stringValue;
                continue;
            }

            if ($this->containsAny($descriptor, ['zip', 'postal', 'postcode']) && empty($mapped['shipping_zip'])) {
                $mapped['shipping_zip'] = $stringValue;
                continue;
            }

            if ($this->containsAny($descriptor, ['note', 'instruction']) && empty($mapped['notes'])) {
                $mapped['notes'] = $stringValue;
                continue;
            }
        }

        if (empty($mapped['shipping_name'])) {
            $combinedName = trim($firstName . ' ' . $lastName);
            if ($combinedName !== '') {
                $mapped['shipping_name'] = $combinedName;
            }
        }

        return $mapped;
    }

    protected function firstPresentValue(array $candidates): mixed
    {
        foreach ($candidates as $candidate) {
            if ($candidate === null) {
                continue;
            }

            if (is_string($candidate) && trim($candidate) === '') {
                continue;
            }

            return $candidate;
        }

        return null;
    }

    protected function findFirstNonEmptyValue(array $candidates): string
    {
        foreach ($candidates as $candidate) {
            if ($candidate === null) {
                continue;
            }

            if (is_numeric($candidate)) {
                return (string) $candidate;
            }

            $value = trim((string) $candidate);
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    protected function toNullableInt(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value) && trim($value) === '') {
            return null;
        }

        $intValue = (int) $value;

        return $intValue > 0 ? $intValue : null;
    }

    protected function toCountryName(string $value): string
    {
        $normalized = strtolower(trim($value));
        if ($normalized === '' || $normalized === 'bd' || $normalized === 'bangladesh') {
            return 'Bangladesh';
        }

        return $value;
    }

    protected function containsAny(string $subject, array $needles): bool
    {
        foreach ($needles as $needle) {
            if ($needle !== '' && str_contains($subject, strtolower($needle))) {
                return true;
            }
        }

        return false;
    }

    protected function calculateTaxForSubtotal(float $subtotal): float
    {
        return $this->checkoutTaxService->calculateTaxAmount($subtotal);
    }

    /**
     * Remove any open abandoned-cart records that match a completed checkout.
     */
    protected function removeCompletedCheckoutFromAbandonedCarts(?int $userId, array $shippingData = [], ?string $checkoutSessionId = null): void
    {
        $baseQuery = AbandonedCart::query()
            ->whereIn('status', ['pending', 'follow_up'])
            ->where('created_at', '>=', now()->subDays(7));

        $matchedIds = collect();

        if ($userId !== null) {
            $matchedIds = $matchedIds->merge((clone $baseQuery)
                ->where('user_id', $userId)
                ->pluck('id'));
        }

        if ($checkoutSessionId) {
            $matchedIds = $matchedIds->merge((clone $baseQuery)
                ->where('session_id', $checkoutSessionId)
                ->pluck('id'));
        }

        $shippingEmail = strtolower(trim((string) ($shippingData['shipping_email'] ?? '')));
        $shippingPhone = preg_replace('/\D+/', '', (string) ($shippingData['shipping_phone'] ?? ''));
        $hasEmail = $shippingEmail !== '' && filter_var($shippingEmail, FILTER_VALIDATE_EMAIL) !== false;
        $hasPhone = is_string($shippingPhone) && $shippingPhone !== '';

        if ($hasEmail || $hasPhone) {
            $matchedIds = $matchedIds->merge((clone $baseQuery)
                ->where(function ($query) use ($hasEmail, $hasPhone, $shippingEmail, $shippingPhone) {
                    $hasPrimaryCondition = false;

                    if ($hasEmail) {
                        $query->whereRaw('LOWER(email) = ?', [$shippingEmail]);
                        $hasPrimaryCondition = true;
                    }

                    if ($hasPhone) {
                        $rawPhoneSql = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '(', ''), ')', ''), '+', '') = ?";

                        if ($hasPrimaryCondition) {
                            $query->orWhereRaw($rawPhoneSql, [$shippingPhone]);
                        } else {
                            $query->whereRaw($rawPhoneSql, [$shippingPhone]);
                        }
                    }
                })
                ->pluck('id'));
        }

        $idsToDelete = $matchedIds
            ->filter(fn ($id) => is_numeric($id) && (int) $id > 0)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($idsToDelete->isNotEmpty()) {
            AbandonedCart::query()
                ->whereIn('id', $idsToDelete->all())
                ->delete();
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
        if ($status === 'cancelled' && Product::isStockEnabled()) {
            foreach ($order->items as $item) {
                if ($item->variant) {
                    $item->variant->incrementStock((int) $item->quantity);
                    continue;
                }

                if ($item->product) {
                    $item->product->incrementStock((int) $item->quantity);
                }
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
