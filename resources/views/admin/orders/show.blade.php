@extends('admin.layouts.app')

@section('title', 'Order Details')
@section('page-title', 'Order Details')

@section('content')
<div class="row g-3">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <a href="{{ route('admin.orders.index') }}" class="btn btn-sm btn-outline-secondary me-2">
                        <i class="bi bi-arrow-left"></i>
                    </a>
                    <h6 class="mb-0 fw-semibold d-flex align-items-center">
                        <span><i class="bi bi-receipt me-2"></i>Order #{{ $order->order_number ?? $order->id }}</span>
                        <small class="text-muted ms-2" style="font-size: 13px;">ID: {{ $order->id }}</small>
                    </h6>
                    @if($order->trashed())
                        <span class="badge bg-danger ms-3"><i class="bi bi-trash me-1"></i> TRASHED</span>
                        <form action="{{ route('admin.orders.restore', $order) }}" method="POST" class="ms-2 mb-0">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-success py-0 px-2" style="font-size: 12px;">
                                <i class="bi bi-arrow-counterclockwise me-1"></i> Restore Order
                            </button>
                        </form>
                    @endif
                </div>
                @php
                    $headerStatusLabel = $order->statusConfig?->label ?? ucfirst(str_replace('_', ' ', $order->status));
                    $headerStatusColor = $order->statusConfig?->color ?? '#6C757D';
                @endphp
                <span id="headerStatusBadge" class="badge fs-6 text-white" style="background-color: {{ $headerStatusColor }};">
                    {{ $headerStatusLabel }}
                </span>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-12 mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="text-muted small text-uppercase mb-0">Contact Information</h6>
                            <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2" data-bs-toggle="modal" data-bs-target="#editCustomerInfoModal">
                                <i class="bi bi-pencil-square me-1"></i><small>Edit</small>
                            </button>
                        </div>
                        @php
                            $checkoutPayloadForCustomer = is_array($order->checkout_fields_payload) ? $order->checkout_fields_payload : [];

                            $firstNonEmptyValue = static function (array $candidates): string {
                                foreach ($candidates as $candidate) {
                                    $value = trim((string) ($candidate ?? ''));
                                    if ($value !== '') {
                                        return $value;
                                    }
                                }

                                return '';
                            };

                            $guestCheckoutEmail = strtolower(trim((string) config('shop.guest_checkout_user_email', 'guest.checkout@innercollection.local')));
                            $placeholderEmails = array_filter([
                                $guestCheckoutEmail,
                                'customer@local.invalid',
                                'guest@local.invalid',
                            ]);

                            $rawUserName = trim((string) ($order->user?->name ?? ''));
                            $rawUserEmail = trim((string) ($order->user?->email ?? ''));
                            $isGuestCheckoutOrder = !$order->user_id || strtolower($rawUserEmail) === $guestCheckoutEmail || strtolower($rawUserName) === 'guest checkout';

                            $billingFullName = trim($firstNonEmptyValue([
                                $checkoutPayloadForCustomer['billing_first_name'] ?? null,
                            ]) . ' ' . $firstNonEmptyValue([
                                $checkoutPayloadForCustomer['billing_last_name'] ?? null,
                            ]));

                            if ($isGuestCheckoutOrder) {
                                $customerName = $firstNonEmptyValue([
                                    $order->shipping_name,
                                    $checkoutPayloadForCustomer['shipping_name'] ?? null,
                                    $checkoutPayloadForCustomer['billing_name'] ?? null,
                                    $billingFullName,
                                    $rawUserName,
                                ]);
                            } else {
                                $customerName = $firstNonEmptyValue([
                                    $rawUserName,
                                    $order->shipping_name,
                                    $checkoutPayloadForCustomer['shipping_name'] ?? null,
                                ]);
                            }

                            $customerEmailCandidates = $isGuestCheckoutOrder
                                ? [
                                    $order->shipping_email,
                                    $checkoutPayloadForCustomer['shipping_email'] ?? null,
                                    $checkoutPayloadForCustomer['billing_email'] ?? null,
                                ]
                                : [
                                    $rawUserEmail,
                                    $order->shipping_email,
                                    $checkoutPayloadForCustomer['shipping_email'] ?? null,
                                    $checkoutPayloadForCustomer['billing_email'] ?? null,
                                ];

                            $customerEmail = null;
                            foreach ($customerEmailCandidates as $emailCandidate) {
                                $rawEmail = trim((string) ($emailCandidate ?? ''));
                                $normalizedEmail = strtolower($rawEmail);
                                if ($normalizedEmail === '' || in_array($normalizedEmail, $placeholderEmails, true)) {
                                    continue;
                                }

                                if (filter_var($rawEmail, FILTER_VALIDATE_EMAIL)) {
                                    $customerEmail = $rawEmail;
                                    break;
                                }
                            }

                            $customerPhone = $firstNonEmptyValue([
                                $order->shipping_phone,
                                $checkoutPayloadForCustomer['shipping_phone'] ?? null,
                                $checkoutPayloadForCustomer['billing_phone'] ?? null,
                                $order->user?->phone,
                            ]);

                            if ($customerName === '') {
                                $customerName = 'Guest Checkout';
                            }
                        @endphp
                        <div class="row">
                            <div class="col-md-7">
                                <table class="table table-borderless mb-0">
                                    <tr>
                                        <th class="text-muted text-uppercase small" width="100">Name</th>
                                        <td><strong>{{ $customerName }}</strong></td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted text-uppercase small">Phone</th>
                                        <td>
                                            @if($customerPhone !== '')
                                                <span class="d-inline-flex align-items-center gap-2">
                                                    <span id="customerPhoneDisplay">{{ $customerPhone }}</span>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1 copy-phone-btn" data-phone="{{ $customerPhone }}" title="Copy number">
                                                        <i class="bi bi-clipboard"></i>
                                                    </button>
                                                </span>
                                            @else
                                                <span class="text-muted">Not provided</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted text-uppercase small">Email</th>
                                        <td>
                                            @if($customerEmail)
                                                <a href="mailto:{{ $customerEmail }}">{{ $customerEmail }}</a>
                                            @else
                                                <span class="text-muted">Not provided</span>
                                            @endif
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-5">
                                @if($isGuestCheckoutOrder)
                                    <div class="alert alert-secondary mb-0 py-2 px-3">
                                        <strong><i class="bi bi-person-x me-1"></i> Guest Checkout</strong><br>
                                        <small class="text-muted">User was not logged in</small>
                                    </div>
                                @else
                                    <div class="alert alert-info mb-0 py-2 px-3">
                                        <strong><i class="bi bi-person-check me-1"></i> Registered User</strong><br>
                                        <small>{{ $order->user->name }}</small><br>
                                        <small class="text-muted">{{ $order->user->email }}</small>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <hr class="my-3">
                        <h6 class="text-muted small text-uppercase mb-2">Shipping Address</h6>
                        @php
                            $shippingAddress = trim((string) ($order->shipping_address ?? ''));
                            $shippingLocationText = trim((string) ((is_array($order->checkout_fields_payload) ? ($order->checkout_fields_payload['shipping_location_text'] ?? null) : null) ?? ''));
                            $shippingArea = trim((string) ((is_array($order->checkout_fields_payload) ? ($order->checkout_fields_payload['shipping_area'] ?? null) : null) ?? ''));
                            
                            $geoParts = [];
                            
                            // Try to gather location parts without duplicating them
                            $candidates = [
                                $order->shippingUnion?->name,
                                $order->shippingUpazila?->name,
                                $order->shipping_city,
                                $order->shippingDistrict?->name,
                                $order->shippingDivision?->name,
                                $order->shipping_state,
                                $order->shipping_zip,
                                $order->shipping_country
                            ];
                            
                            // Combine all existing displayed text to check for duplicates
                            $alreadyDisplayed = strtolower($shippingAddress . ' ' . $shippingLocationText . ' ' . $shippingArea);
                            
                            foreach ($candidates as $candidate) {
                                $val = trim((string) $candidate);
                                // don't add if empty, "0000", already in the parts list, or already visible in the address
                                if ($val !== '' && $val !== '0000' && !in_array($val, $geoParts, true)
                                    && stripos($alreadyDisplayed, $val) === false) {
                                    $geoParts[] = $val;
                                }
                            }
                            
                            $cleanLocationLine = implode(', ', $geoParts);
                        @endphp

                        @if($shippingAddress !== '')
                            <p class="mb-1" style="white-space: pre-line;">{{ $shippingAddress }}</p>
                        @else
                            <p class="mb-1 text-muted">Not provided</p>
                        @endif

                        @if($shippingLocationText !== '' && $shippingLocationText !== $shippingAddress)
                            <p class="mb-1 text-muted small"><i class="bi bi-geo-alt me-1"></i>{{ $shippingLocationText }}</p>
                        @endif

                        @if($shippingArea !== '' && stripos($shippingAddress, $shippingArea) === false)
                            <p class="mb-1 text-muted small"><i class="bi bi-map me-1"></i>{{ $shippingArea }}</p>
                        @endif

                        @if($cleanLocationLine !== '')
                            <p class="mb-1 text-muted small">{{ $cleanLocationLine }}</p>
                        @endif
                    </div>
                </div>

                <hr>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-box me-2"></i>Order Items</h6>
                    @if(!in_array($order->status, ['shipped', 'delivered', 'cancelled', 'returned', 'refunded', 'completed', 'failed']))
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editItemsModal">
                            <i class="bi bi-pencil me-1"></i>Edit Items
                        </button>
                    @endif
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th class="text-end">Price</th>
                                <th class="text-center">Quantity</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->items as $item)
                                <tr>
                                    <td style="max-width: 250px; white-space: normal;">
                                        @php
                                            $variantName = trim((string) ($item->variant?->name ?? ''));
                                            $variantSku = trim((string) ($item->variant?->sku ?? ''));
                                            $variantId = $item->product_variant_id ? (int) $item->product_variant_id : null;

                                            if ($variantSku === '' && $variantId) {
                                                $variantSku = trim((string) ($item->product_sku ?? ''));
                                            }

                                            $variantAttributeSummary = '';
                                            if ($item->variant && $item->variant->relationLoaded('attributeValues')) {
                                                $variantAttributeSummary = $item->variant->attributeValues
                                                    ->map(function ($attributeValue) {
                                                        $attributeName = trim((string) ($attributeValue->attribute?->name ?? ''));
                                                        $value = trim((string) ($attributeValue->value ?? ''));

                                                        if ($value === '') {
                                                            return null;
                                                        }

                                                        return $attributeName !== '' ? "{$attributeName}: {$value}" : $value;
                                                    })
                                                    ->filter()
                                                    ->implode(', ');
                                            }

                                            $variantLabel = $variantName !== ''
                                                ? $variantName
                                                : ($variantAttributeSummary !== ''
                                                    ? $variantAttributeSummary
                                                    : ($variantId ? ('Variant #' . $variantId) : ''));
                                        @endphp
                                        @if($item->product)
                                            <a href="{{ route('admin.products.show', $item->product_id) }}" class="text-decoration-none">
                                                {{ $item->product->name }}
                                            </a>
                                        @else
                                            <span class="text-muted">{{ $item->product_name ?: 'Product Deleted' }}</span>
                                        @endif

                                        @if($variantAttributeSummary !== '')
                                            <br><small class="text-muted">{{ $variantAttributeSummary }}</small>
                                        @elseif($variantLabel !== '')
                                            <br><small class="text-muted">{{ $variantLabel }}</small>
                                        @endif

                                        @if($variantSku !== '')
                                            <br><small class="text-muted">Variant SKU: {{ $variantSku }}</small>
                                        @endif
                                    </td>
                                    <td class="text-end">৳{{ number_format($item->price, 2) }}</td>
                                    <td class="text-center">{{ $item->quantity }}</td>
                                    <td class="text-end">৳{{ number_format($item->price * $item->quantity, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end">Subtotal:</td>
                                <td class="text-end">৳{{ number_format($order->subtotal, 2) }}</td>
                            </tr>
                            @if($order->discount_amount > 0)
                                <tr class="text-success">
                                    <td colspan="3" class="text-end">
                                        Discount
                                        @if($order->coupon_code)
                                            <span class="badge bg-success ms-1">{{ $order->coupon_code }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end">-৳{{ number_format($order->discount_amount, 2) }}</td>
                                </tr>
                            @endif
                            @if($order->loyalty_discount_amount > 0)
                                <tr class="text-info">
                                    <td colspan="3" class="text-end">
                                        Loyalty Discount
                                        @if($order->customer_group_name)
                                            <span class="badge bg-info ms-1">{{ $order->customer_group_name }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end">-৳{{ number_format($order->loyalty_discount_amount, 2) }}</td>
                                </tr>
                            @endif
                            @if($order->tax > 0)
                                <tr>
                                    <td colspan="3" class="text-end">Tax:</td>
                                    <td class="text-end">৳{{ number_format($order->tax, 2) }}</td>
                                </tr>
                            @endif
                            @if($order->shipping > 0)
                                <tr>
                                    <td colspan="3" class="text-end">Shipping:</td>
                                    <td class="text-end">৳{{ number_format($order->shipping, 2) }}</td>
                                </tr>
                            @endif
                            
                            <!-- Admin Discount Section -->
                            <tr class="bg-light">
                                <td colspan="4" class="p-3">
                                    @if($order->discount_amount <= 0)
                                        <form action="{{ route('admin.orders.apply-discount', $order) }}" method="POST" class="row g-2 align-items-center justify-content-end">
                                        @csrf
                                        <div class="col-auto">
                                            <label class="col-form-label col-form-label-sm fw-semibold">Apply Discount:</label>
                                        </div>
                                        <div class="col-auto">
                                            <select name="discount_type" id="discountTypeSelect" class="form-select form-select-sm" style="width: auto;">
                                                <option value="fixed">Fixed Amount (৳)</option>
                                                <option value="percentage">Percentage (%)</option>
                                                <option value="coupon">Coupon Code</option>
                                            </select>
                                        </div>
                                        <div class="col-auto">
                                            <input type="text" name="discount_value" id="discountValueInput" class="form-control form-control-sm" placeholder="Value/Code" required style="max-width: 150px;">
                                            <datalist id="activeCouponsList">
                                                @foreach($activeCoupons as $coupon)
                                                    <option value="{{ $coupon->code }}">{{ $coupon->name }} ({{ $coupon->formatted_value }})</option>
                                                @endforeach
                                            </datalist>
                                        </div>
                                        <div class="col-auto">
                                            <button type="submit" class="btn btn-sm btn-outline-primary">Apply</button>
                                        </div>
                                    </form>
                                    @else
                                        <div class="d-flex justify-content-end align-items-center gap-3 mt-2">
                                            <span class="text-success small fw-semibold">
                                                <i class="bi bi-check-circle-fill me-1"></i> Discount Applied
                                            </span>
                                            <form action="{{ route('admin.orders.remove-discount', $order) }}" method="POST" class="m-0">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger px-2 py-1" onclick="return confirm('Remove existing discount?')"><i class="bi bi-x-circle me-1"></i> Remove</button>
                                        </form>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                            
                            <tr>
                                <th colspan="3" class="text-end">Total:</th>
                                <th class="text-end">৳{{ number_format($order->total, 2) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                @if($order->notes)
                    <hr>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">Notes</h6>
                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" data-bs-toggle="collapse" data-bs-target="#editNotesForm">
                            <i class="bi bi-pencil me-1"></i><small>Edit</small>
                        </button>
                    </div>
                    <p class="text-muted mb-2">{{ $order->notes }}</p>
                    <div class="collapse" id="editNotesForm">
                        <form action="{{ route('admin.orders.update-customer-info', $order) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="shipping_name" value="{{ $order->shipping_name }}">
                            <input type="hidden" name="shipping_phone" value="{{ $order->shipping_phone }}">
                            <div class="input-group input-group-sm">
                                <textarea class="form-control" name="notes" rows="2">{{ $order->notes }}</textarea>
                                <button type="submit" class="btn btn-outline-primary"><i class="bi bi-check-lg"></i></button>
                            </div>
                        </form>
                    </div>
                @endif

                {{-- Checkout Details section removed — all needed info shown above --}}
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <!-- Tracking Card -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-truck me-2"></i>Tracking Information</span>
                <a href="{{ route('admin.orders.tracking.edit', $order) }}" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-pencil"></i> Manage
                </a>
            </div>
            <div class="card-body">
                @if($order->tracking_number)
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <th>Tracking #</th>
                            <td>{{ $order->tracking_number }}</td>
                        </tr>
                        <tr>
                            <th>Carrier</th>
                            <td>{{ ucfirst($order->carrier) }}</td>
                        </tr>
                        @if($order->shipped_at)
                        <tr>
                            <th>Shipped</th>
                            <td>{{ $order->shipped_at->format('M d, Y') }}</td>
                        </tr>
                        @endif
                        @if($order->estimated_delivery_at)
                        <tr>
                            <th>Est. Delivery</th>
                            <td>{{ $order->estimated_delivery_at->format('M d, Y') }}</td>
                        </tr>
                        @endif
                    </table>
                    @if($order->carrier_tracking_url)
                    <a href="{{ $order->carrier_tracking_url }}" target="_blank" class="btn btn-sm btn-outline-info w-100 mt-2">
                        <i class="bi bi-box-arrow-up-right me-1"></i> Track on {{ ucfirst($order->carrier) }}
                    </a>
                    @endif
                @else
                    <p class="text-muted mb-2">No tracking information yet.</p>
                    <a href="{{ route('admin.orders.tracking.edit', $order) }}" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus"></i> Add Tracking
                    </a>
                @endif
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">Update Status</div>
            <div class="card-body">
                <form action="{{ route('admin.orders.update-status', $order) }}" method="POST" id="updateStatusForm">
                    @csrf
                    @method('PATCH')

                    <div class="mb-3">
                        <label for="status" class="form-label">Order Status</label>
                        <select class="form-select @error('status') is-invalid @enderror" id="status" name="status">
                            @php
                                $statusOptions = $availableStatuses->keyBy('key');
                            @endphp

                            @if(!$statusOptions->has($order->status))
                                <option value="{{ $order->status }}" selected>
                                    {{ ucfirst(str_replace('_', ' ', $order->status)) }} (Inactive)
                                </option>
                            @endif

                            @foreach($availableStatuses as $statusOption)
                                <option value="{{ $statusOption->key }}" {{ $order->status === $statusOption->key ? 'selected' : '' }}>
                                    {{ $statusOption->label }}
                                </option>
                            @endforeach
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-check"></i> Update Status
                    </button>
                </form>
            </div>
        </div>

        @if($order->payment)
            <div class="card">
                <div class="card-header">Payment Information</div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <th>Payment ID</th>
                            <td>
                                <a href="{{ route('admin.payments.show', $order->payment->id) }}">
                                    #{{ $order->payment->id }}
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <th>Method</th>
                            <td>{{ ucfirst($order->payment->payment_method) }}</td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td>
                                <span class="badge badge-status-{{ $order->payment->status }}">
                                    {{ ucfirst($order->payment->status) }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Amount</th>
                            <td>৳{{ number_format($order->payment->amount, 2) }}</td>
                        </tr>
                    </table>

                    <form action="{{ route('admin.orders.update-payment-status', $order) }}" method="POST" class="mt-3 border-top pt-3">
                        @csrf
                        @method('PATCH')
                        <div class="input-group input-group-sm">
                            <select name="payment_status" class="form-select">
                                @foreach(['pending', 'awaiting', 'paid', 'failed', 'refunded'] as $ps)
                                    <option value="{{ $ps }}" {{ $order->payment_status === $ps ? 'selected' : '' }}>
                                        {{ ucfirst($ps) }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-outline-primary">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        @else
            <div class="card">
                <div class="card-header"><i class="bi bi-credit-card me-2"></i>Payment Information</div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <th>Method</th>
                            <td>
                                @php
                                    $methodLabels = [
                                        'cod' => 'Cash on Delivery',
                                        'stripe' => 'Stripe',
                                        'bkash' => 'bKash'
                                    ];
                                @endphp
                                {{ $methodLabels[$order->payment_method] ?? ucfirst($order->payment_method ?? 'N/A') }}
                            </td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td>
                                @php
                                    $statusColors = [
                                        'pending' => 'warning',
                                        'awaiting' => 'info',
                                        'paid' => 'success',
                                        'failed' => 'danger',
                                        'refunded' => 'secondary'
                                    ];
                                    $statusColor = $statusColors[$order->payment_status] ?? 'secondary';
                                @endphp
                                <span class="badge bg-{{ $statusColor }}">
                                    {{ ucfirst($order->payment_status ?? 'Unknown') }}
                                </span>
                            </td>
                        </tr>
                    </table>

                    <form action="{{ route('admin.orders.update-payment-status', $order) }}" method="POST" class="mt-3 border-top pt-3">
                        @csrf
                        @method('PATCH')
                        <div class="input-group input-group-sm">
                            <select name="payment_status" class="form-select">
                                @foreach(['pending', 'awaiting', 'paid', 'failed', 'refunded'] as $ps)
                                    <option value="{{ $ps }}" {{ $order->payment_status === $ps ? 'selected' : '' }}>
                                        {{ ucfirst($ps) }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-outline-primary">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        <div class="card mt-4">
            <div class="card-header"><i class="bi bi-funnel me-2"></i>Order Source</div>
            <div class="card-body">
                <form action="{{ route('admin.orders.update-source', $order) }}" method="POST">
                    @csrf
                    @method('PATCH')

                    <div class="mb-3">
                        @php
                            $sourceOptionsStr = \App\Models\Setting::getValue('general', 'order_source_options', 'Web,Facebook,Instagram,WhatsApp');
                            $sourceOptions = array_filter(array_map('trim', explode(',', $sourceOptionsStr)));
                        @endphp
                        <label for="order_source" class="form-label">Order From</label>
                        <select class="form-select @error('order_source') is-invalid @enderror" id="order_source" name="order_source">
                            <option value="">-- Select Source --</option>
                            @foreach($sourceOptions as $source)
                                <option value="{{ $source }}" {{ $order->order_source === $source ? 'selected' : '' }}>
                                    {{ $source }}
                                </option>
                            @endforeach
                            @if($order->order_source && !in_array($order->order_source, $sourceOptions))
                                <option value="{{ $order->order_source }}" selected>{{ $order->order_source }}</option>
                            @endif
                        </select>
                        @error('order_source')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-check"></i> Update Source
                    </button>
                </form>
            </div>
        </div>

        {{-- Fraud Blocker Quick Actions --}}
        <div class="card mt-4 border-danger border-opacity-25">
            <div class="card-header bg-danger bg-opacity-10 d-flex justify-content-between align-items-center">
                <span><i class="bi bi-shield-x me-2 text-danger"></i><strong class="text-danger">Fraud Blocker</strong></span>
                <a href="{{ route('admin.fraud-blocks.index') }}" class="btn btn-sm btn-outline-danger py-0 px-2">
                    <small>View All</small>
                </a>
            </div>
            <div class="card-body p-0">
                @php
                    $fraudPhone = trim((string) ($order->shipping_phone ?? ''));
                    $fraudEmail = trim((string) ($order->shipping_email ?? ''));
                    if ($fraudEmail === '' || $fraudEmail === 'Not provided') {
                        $fraudEmail = trim((string) ($checkoutPayloadForCustomer['shipping_email'] ?? ''));
                    }
                    
                    // Try to get IP and device from associated abandoned cart
                    $relatedAbandonedCart = \App\Models\AbandonedCart::where('recovered_order_id', $order->id)->first()
                        ?? \App\Models\AbandonedCart::where('phone', $fraudPhone)->where('phone', '!=', '')->latest()->first();
                    $fraudIp = trim((string) ($checkoutPayloadForCustomer['device_ip'] ?? $relatedAbandonedCart?->ip_address ?? ''));
                    $fraudDevice = trim((string) ($checkoutPayloadForCustomer['device_user_agent'] ?? $relatedAbandonedCart?->user_agent ?? ''));
                    
                    $fraudItems = [];
                    if ($fraudPhone !== '') {
                        $fraudItems[] = ['type' => 'phone', 'value' => $fraudPhone, 'icon' => 'bi-telephone', 'label' => 'Phone'];
                    }
                    if ($fraudEmail !== '' && $fraudEmail !== 'Not provided' && filter_var($fraudEmail, FILTER_VALIDATE_EMAIL)) {
                        $fraudItems[] = ['type' => 'email', 'value' => $fraudEmail, 'icon' => 'bi-envelope', 'label' => 'Email'];
                    }
                    if ($fraudIp !== '') {
                        $fraudItems[] = ['type' => 'ip', 'value' => $fraudIp, 'icon' => 'bi-globe2', 'label' => 'IP'];
                    }
                    if ($fraudDevice !== '') {
                        $fraudItems[] = ['type' => 'device', 'value' => $fraudDevice, 'icon' => 'bi-laptop', 'label' => 'Device'];
                    }
                @endphp

                <div class="list-group list-group-flush">
                    @forelse($fraudItems as $fi)
                        @php
                            $isCurrentlyBlocked = \App\Models\FraudBlock::isBlocked($fi['type'], $fi['value']);
                        @endphp
                        <div class="list-group-item d-flex justify-content-between align-items-center py-2 fraud-block-item" data-type="{{ $fi['type'] }}" data-value="{{ $fi['value'] }}">
                            <div class="text-truncate me-2">
                                <i class="bi {{ $fi['icon'] }} me-1 text-muted"></i>
                                <small class="text-truncate" title="{{ $fi['value'] }}">{{ Str::limit($fi['value'], 25) }}</small>
                            </div>
                            @if($isCurrentlyBlocked)
                                <button type="button"
                                    class="btn btn-sm btn-danger fraud-unblock-btn"
                                    title="Click to unblock">
                                    <i class="bi bi-shield-fill-x"></i>
                                    <small>Blocked</small>
                                </button>
                            @else
                                <button type="button"
                                    class="btn btn-sm btn-outline-danger fraud-open-modal-btn"
                                    title="Click to block">
                                    <i class="bi bi-shield-plus"></i>
                                    <small>Block</small>
                                </button>
                            @endif
                        </div>
                    @empty
                        <div class="list-group-item text-center text-muted py-3">
                            <small>No blockable data found for this order.</small>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Fraud Block Modal --}}
    <div class="modal fade" id="fraudBlockModal" tabindex="-1" aria-labelledby="fraudBlockModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger bg-opacity-10 border-0">
                    <h5 class="modal-title text-danger" id="fraudBlockModalLabel">
                        <i class="bi bi-shield-x me-2"></i>Block Confirmation
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label text-muted small text-uppercase">Blocking</label>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-danger bg-opacity-10 text-danger" id="fraudModalTypeLabel"></span>
                            <code id="fraudModalValue" class="text-dark"></code>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="fraudModalReason" class="form-label fw-semibold">Reason <span class="text-muted fw-normal">(optional)</span></label>
                        <input type="text" class="form-control" id="fraudModalReason" placeholder="e.g. Fake orders, Spam calls...">
                    </div>
                    <div class="mb-0">
                        <label for="fraudModalCustomMessage" class="form-label fw-semibold">Custom Message <span class="text-muted fw-normal">(shown to user, optional)</span></label>
                        <textarea class="form-control" id="fraudModalCustomMessage" rows="2" placeholder="e.g. Your account has been suspended due to policy violations..."></textarea>
                        <div class="form-text">This message will be shown to the user when they try to checkout.</div>
                    </div>
                    <input type="hidden" id="fraudModalType">
                    <input type="hidden" id="fraudModalValueHidden">
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i>Cancel
                    </button>
                    <button type="button" class="btn btn-danger" id="fraudModalBlockBtn">
                        <i class="bi bi-shield-x me-1"></i>Block
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Edit Order Items Modal --}}
    <div class="modal fade" id="editItemsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <form action="{{ route('admin.orders.update-items', $order) }}" method="POST" id="editItemsForm">
                    @csrf
                    <div class="modal-header bg-primary bg-opacity-10 border-0">
                        <h5 class="modal-title text-primary"><i class="bi bi-box me-2"></i>Edit Order Items</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="position-relative mb-3">
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="text" class="form-control" id="modalProductSearchInput" placeholder="Search product by name or SKU..." autocomplete="off">
                            </div>
                            <div id="modalProductSearchResults" class="position-absolute w-100 bg-white border rounded-bottom shadow-sm" style="display:none; z-index:1050; max-height:300px; overflow-y:auto;"></div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0" id="modalOrderItemsTable">
                                <thead>
                                    <tr>
                                        <th style="width:40px"></th>
                                        <th>Product</th>
                                        <th style="width:100px">Price (৳)</th>
                                        <th style="width:80px">Qty</th>
                                        <th style="width:90px" class="text-end">Total</th>
                                        <th style="width:40px"></th>
                                    </tr>
                                </thead>
                                <tbody id="modalOrderItemsBody">
                                    <tr id="modalNoItemsRow" style="{{ $order->items->count() > 0 ? 'display:none;' : '' }}">
                                        <td colspan="6" class="text-center text-muted py-4">
                                            <i class="bi bi-cart3 fs-3 d-block mb-2"></i>
                                            Search and add products above
                                        </td>
                                    </tr>
                                    @foreach($order->items as $index => $item)
                                        @php
                                            $variantName = trim((string) ($item->variant?->name ?? ''));
                                            $variantSku = trim((string) ($item->variant?->sku ?? ''));
                                            if ($variantSku === '' && $item->product_variant_id) {
                                                $variantSku = trim((string) ($item->product_sku ?? ''));
                                            }
                                            $variantAttributeSummary = '';
                                            if ($item->variant && $item->variant->relationLoaded('attributeValues')) {
                                                $variantAttributeSummary = $item->variant->attributeValues->map(fn($av) => $av->value)->filter()->implode(' / ');
                                            }
                                            $variantLabel = $variantName !== '' ? $variantName : ($variantAttributeSummary !== '' ? $variantAttributeSummary : '');
                                        @endphp
                                        <tr class="order-item-row" data-idx="{{ $index }}">
                                            <td>
                                                @if($item->product && $item->product->images && $item->product->images->isNotEmpty())
                                                    <img src="{{ $item->product->images->first()->url }}" style="width:36px;height:36px;object-fit:cover;" class="rounded">
                                                @else
                                                    <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width:36px;height:36px;"><i class="bi bi-image text-muted small"></i></div>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="fw-semibold small">{{ $item->product_name }}{{ $variantLabel ? ' — ' . $variantLabel : '' }}</div>
                                                <div class="text-muted" style="font-size:11px;">SKU: {{ $item->product_sku ?: 'N/A' }}</div>
                                                <input type="hidden" name="items[{{ $index }}][product_id]" value="{{ $item->product_id }}">
                                                @if($item->product_variant_id)
                                                    <input type="hidden" name="items[{{ $index }}][variant_id]" value="{{ $item->product_variant_id }}">
                                                @endif
                                            </td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm item-price" name="items[{{ $index }}][price]" value="{{ number_format($item->price, 2, '.', '') }}" min="0" step="0.01">
                                            </td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm item-qty" name="items[{{ $index }}][quantity]" value="{{ $item->quantity }}" min="1" step="1">
                                            </td>
                                            <td class="text-end fw-semibold item-total">৳{{ number_format($item->price * $item->quantity, 2, '.', '') }}</td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-outline-danger remove-item-btn"><i class="bi bi-x-lg"></i></button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer border-0 bg-light">
                        <div class="me-auto text-muted small">
                            Subtotal: <strong id="modalSubtotalPreview" class="text-dark">৳{{ number_format($order->subtotal, 2) }}</strong>
                        </div>
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="modalSaveItemsBtn">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Customer Info Edit Modal --}}
    <div class="modal fade" id="editCustomerInfoModal" tabindex="-1" aria-labelledby="editCustomerInfoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('admin.orders.update-customer-info', $order) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="modal-header bg-primary bg-opacity-10 border-0">
                        <h5 class="modal-title text-primary" id="editCustomerInfoModalLabel">
                            <i class="bi bi-pencil-square me-2"></i>Edit Customer Info
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="editShippingName" class="form-label fw-semibold">Customer Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="editShippingName" name="shipping_name" value="{{ $order->shipping_name }}" required>
                        </div>
                        <div class="mb-3">
                            <label for="editShippingPhone" class="form-label fw-semibold">Phone <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="editShippingPhone" name="shipping_phone" value="{{ $order->shipping_phone }}" required>
                        </div>
                        <div class="mb-3">
                            <label for="editShippingEmail" class="form-label fw-semibold">Email</label>
                            <input type="email" class="form-control" id="editShippingEmail" name="shipping_email" value="{{ $order->shipping_email }}">
                        </div>
                        <div class="mb-3">
                            <label for="editShippingAddress" class="form-label fw-semibold">Shipping Address</label>
                            <textarea class="form-control" id="editShippingAddress" name="shipping_address" rows="3">{{ $order->shipping_address }}</textarea>
                        </div>
                        
                        @php
                            $shippingLocationText = trim((string) ((is_array($order->checkout_fields_payload) ? ($order->checkout_fields_payload['shipping_location_text'] ?? null) : null) ?? ''));
                            $shippingArea = trim((string) ((is_array($order->checkout_fields_payload) ? ($order->checkout_fields_payload['shipping_area'] ?? null) : null) ?? ''));
                        @endphp
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="editShippingLocationText" class="form-label fw-semibold">Shipping Location Text</label>
                                <input type="text" class="form-control" id="editShippingLocationText" name="shipping_location_text" value="{{ $shippingLocationText }}">
                            </div>
                            <div class="col-md-6">
                                <label for="editShippingArea" class="form-label fw-semibold">Shipping Area</label>
                                <input type="text" class="form-control" id="editShippingArea" name="shipping_area" value="{{ $shippingArea }}">
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="editShippingDistrict" class="form-label fw-semibold">District (জেলা)</label>
                                <select class="form-select" id="editShippingDistrict" name="shipping_district_id">
                                    <option value="">-- Select District --</option>
                                    @foreach($districts as $district)
                                        <option value="{{ $district->id }}" {{ (int) $order->shipping_district_id === (int) $district->id ? 'selected' : '' }}>
                                            {{ $district->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="editShippingCity" class="form-label fw-semibold">City</label>
                                <input type="text" class="form-control" id="editShippingCity" name="shipping_city" value="{{ $order->shipping_city }}">
                            </div>
                        </div>
                        <div class="mb-3 mt-3">
                            <label for="editNotes" class="form-label fw-semibold">Order Notes</label>
                            <textarea class="form-control" id="editNotes" name="notes" rows="2">{{ $order->notes }}</textarea>
                        </div>
                        <div id="districtRatePreview" class="alert alert-info py-2 px-3 d-none">
                            <small>
                                <i class="bi bi-truck me-1"></i>
                                New shipping charge: <strong id="districtRateValue"></strong>
                                <span class="text-muted">(Current: ৳{{ number_format($order->shipping, 2) }})</span>
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-lg me-1"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Send SMS --}}
    <div class="col-12 mt-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-chat-dots me-2"></i>Send SMS to Customer</h6>
                @if($order->shipping_phone)
                    <span class="badge bg-light text-dark"><i class="bi bi-phone me-1"></i>{{ $order->shipping_phone }}</span>
                @endif
            </div>
            <div class="card-body">
                <form action="{{ route('admin.orders.send-sms', $order) }}" method="POST" data-no-admin-ajax="1">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Quick Template</label>
                            <select class="form-select form-select-sm" id="smsTemplateSelect">
                                <option value="">-- Custom Message --</option>
                                @foreach($smsTemplates as $sKey => $sTpl)
                                    @if($sTpl['template'])
                                        <option value="{{ $sTpl['template'] }}" data-status="{{ $sKey }}">
                                            {{ $sTpl['label'] }}: {{ Str::limit($sTpl['template'], 50) }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold">Message <span class="text-danger">*</span></label>
                            <textarea class="form-control form-control-sm" name="sms_message" id="smsMessageInput" rows="3" maxlength="500" required placeholder="Type your SMS message..."></textarea>
                            <div class="d-flex justify-content-between mt-1">
                                <small class="text-muted">Placeholders: <code>{order_number}</code> <code>{customer_name}</code> <code>{status}</code> <code>{total}</code> <code>{site_name}</code></small>
                                <small class="text-muted"><span id="smsCharCount">0</span>/500</small>
                            </div>
                        </div>
                        <div class="col-12 text-end">
                            <button type="submit" class="btn btn-primary btn-sm" {{ $order->shipping_phone ? '' : 'disabled' }}>
                                <i class="bi bi-send me-1"></i> Send SMS
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Activity Log --}}
    <div class="col-12 mt-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-clock-history me-2"></i>Activity Log</h6>
                <span class="badge bg-light text-dark">{{ $order->activityLogs->count() }} entries</span>
            </div>
            <div class="card-body p-0">
                @if($order->activityLogs->isEmpty())
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-journal-text fs-3 d-block mb-2"></i>
                        No activity recorded yet.
                    </div>
                @else
                    <div class="list-group list-group-flush" style="max-height: 500px; overflow-y: auto;">
                        @foreach($order->activityLogs as $log)
                            @php
                                $iconMap = [
                                    'status_change' => ['icon' => 'bi-arrow-repeat', 'color' => 'primary'],
                                    'sms_sent' => ['icon' => 'bi-chat-check', 'color' => 'success'],
                                    'sms_failed' => ['icon' => 'bi-chat-x', 'color' => 'danger'],
                                    'manual_sms' => ['icon' => 'bi-send-check', 'color' => 'info'],
                                    'order_created' => ['icon' => 'bi-plus-circle', 'color' => 'success'],
                                    'note' => ['icon' => 'bi-sticky', 'color' => 'warning'],
                                ];
                                $logStyle = $iconMap[$log->type] ?? ['icon' => 'bi-dot', 'color' => 'secondary'];
                            @endphp
                            <div class="list-group-item px-3 py-2">
                                <div class="d-flex align-items-start">
                                    <div class="me-3 mt-1">
                                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-{{ $logStyle['color'] }} bg-opacity-10" style="width:32px;height:32px;">
                                            <i class="bi {{ $logStyle['icon'] }} text-{{ $logStyle['color'] }}"></i>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <strong class="small">{{ $log->title }}</strong>
                                                @if($log->description)
                                                    <p class="mb-0 small text-muted mt-1" style="white-space: pre-wrap;">{{ Str::limit($log->description, 200) }}</p>
                                                @endif
                                            </div>
                                            <div class="text-end ms-3 flex-shrink-0">
                                                <small class="text-muted d-block">{{ $log->created_at->format('d M, h:i A') }}</small>
                                                <small class="text-muted">{{ $log->admin_name ?? 'System' }}</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function() {
        const discountTypeSelect = document.getElementById('discountTypeSelect');
        const discountValueInput = document.getElementById('discountValueInput');
        
        if (discountTypeSelect && discountValueInput) {
            discountTypeSelect.addEventListener('change', function() {
                if (this.value === 'coupon') {
                    discountValueInput.setAttribute('list', 'activeCouponsList');
                    discountValueInput.placeholder = 'Type or select coupon';
                } else {
                    discountValueInput.removeAttribute('list');
                    discountValueInput.placeholder = 'Value';
                }
            });
            // trigger on load
            discountTypeSelect.dispatchEvent(new Event('change'));
        }

        // SMS Template selector
        const templateSelect = document.getElementById('smsTemplateSelect');
        const smsInput = document.getElementById('smsMessageInput');
        const charCount = document.getElementById('smsCharCount');

        if (templateSelect && smsInput) {
            templateSelect.addEventListener('change', function() {
                if (this.value) {
                    let text = this.value;
                    // Dynamic replacements basic
                    text = text.replace('{order_number}', '{{ $order->order_number }}');
                    text = text.replace('{customer_name}', '{{ $order->shipping_name }}');
                    text = text.replace('{status}', '{{ $order->statusConfig?->label ?? $order->status }}');
                    text = text.replace('{total}', '{{ number_format($order->total, 2) }}');
                    text = text.replace('{site_name}', '{{ \App\Models\Setting::getValue("general", "site_name", "Shop") }}');
                    
                    smsInput.value = text;
                    if (charCount) charCount.textContent = smsInput.value.length;
                }
            });

            smsInput.addEventListener('input', function() {
                if (charCount) charCount.textContent = this.value.length;
            });
        }

        // Copy phone button
        document.querySelectorAll('.copy-phone-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const phone = this.dataset.phone;
                navigator.clipboard.writeText(phone).then(() => {
                    const icon = this.querySelector('i');
                    icon.className = 'bi bi-clipboard-check text-success';
                    setTimeout(() => { icon.className = 'bi bi-clipboard'; }, 1500);
                });
            });
        });

        // District rate preview in edit modal
        const districtSelect = document.getElementById('editShippingDistrict');
        const ratePreview = document.getElementById('districtRatePreview');
        const rateValue = document.getElementById('districtRateValue');
        const originalDistrictId = {{ $order->shipping_district_id ?? 'null' }};

        if (districtSelect && ratePreview) {
            districtSelect.addEventListener('change', function() {
                const selectedId = parseInt(this.value);
                if (!selectedId || selectedId === originalDistrictId) {
                    ratePreview.classList.add('d-none');
                    return;
                }

                rateValue.textContent = 'Loading...';
                ratePreview.classList.remove('d-none');

                fetch('{{ route("admin.orders.district-shipping-rate") }}?district_id=' + selectedId + '&shipping_method={{ urlencode($order->shipping_method ?? "") }}', {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    }
                })
                .then(r => r.json())
                .then(data => {
                    if (data.formatted_rate) {
                        rateValue.textContent = data.formatted_rate;
                    } else {
                        rateValue.textContent = 'No rate found';
                    }
                })
                .catch(() => {
                    rateValue.textContent = 'Error';
                });
            });
        }

        // Fraud Blocker logic
        const defaultMessages = {
            'phone': @json(\App\Models\Setting::getValue('fraud_blocks', 'default_phone_msg', '')),
            'email': @json(\App\Models\Setting::getValue('fraud_blocks', 'default_email_msg', '')),
            'ip': @json(\App\Models\Setting::getValue('fraud_blocks', 'default_ip_msg', '')),
            'device': @json(\App\Models\Setting::getValue('fraud_blocks', 'default_device_msg', ''))
        };

        const fraudModal = new bootstrap.Modal(document.getElementById('fraudBlockModal'));
        const modalTypeLabel = document.getElementById('fraudModalTypeLabel');
        const modalValueText = document.getElementById('fraudModalValue');
        const modalReason = document.getElementById('fraudModalReason');
        const modalCustomMessage = document.getElementById('fraudModalCustomMessage');
        const modalTypeHidden = document.getElementById('fraudModalType');
        const modalValueHidden = document.getElementById('fraudModalValueHidden');
        const modalBlockBtn = document.getElementById('fraudModalBlockBtn');
        let currentBlockButton = null;

        // Open Modal
        document.querySelectorAll('.fraud-open-modal-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const item = btn.closest('.fraud-block-item');
                const type = item.dataset.type;
                const value = item.dataset.value;

                currentBlockButton = btn;
                modalTypeLabel.textContent = type.charAt(0).toUpperCase() + type.slice(1);
                modalValueText.textContent = value;
                modalTypeHidden.value = type;
                modalValueHidden.value = value;
                
                modalReason.value = 'Blocked from Order #{{ $order->order_number }}';
                modalCustomMessage.value = defaultMessages[type] || '';

                fraudModal.show();
            });
        });

        // Submit Block
        if (modalBlockBtn) {
            modalBlockBtn.addEventListener('click', function() {
                const type = modalTypeHidden.value;
                const value = modalValueHidden.value;
                const reason = modalReason.value;
                const customMessage = modalCustomMessage.value;

                modalBlockBtn.disabled = true;
                modalBlockBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Blocking...';

                fetch('{{ route("admin.fraud-blocks.quick-block") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        type: type,
                        value: value,
                        order_id: {{ $order->id }},
                        reason: reason,
                        custom_message: customMessage
                    })
                })
                .then(r => r.json())
                .then(data => {
                    modalBlockBtn.disabled = false;
                    modalBlockBtn.innerHTML = '<i class="bi bi-shield-x me-1"></i>Block';
                    
                    if (data.success && currentBlockButton) {
                        fraudModal.hide();
                        // Change button to unblock state
                        currentBlockButton.className = 'btn btn-sm btn-danger fraud-unblock-btn';
                        currentBlockButton.innerHTML = '<i class="bi bi-shield-fill-x"></i> <small>Blocked</small>';
                        currentBlockButton.title = 'Click to unblock';
                        
                        // Re-attach listener by reloading page to be safe, or just changing class and attaching event
                        window.location.reload();
                    } else if(data.message) {
                        alert(data.message);
                    }
                })
                .catch(() => {
                    modalBlockBtn.disabled = false;
                    modalBlockBtn.innerHTML = '<i class="bi bi-shield-x me-1"></i>Block';
                    alert('An error occurred while blocking.');
                });
            });
        }

        // Unblock
        document.querySelectorAll('.fraud-unblock-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const item = btn.closest('.fraud-block-item');
                const type = item.dataset.type;
                const value = item.dataset.value;

                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

                fetch('{{ route("admin.fraud-blocks.quick-unblock") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ type: type, value: value })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    }
                })
                .catch(() => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-exclamation-triangle"></i> Error';
                });
            });
        });

        // Edit Items Modal Logic
        const modalSearchInput = document.getElementById('modalProductSearchInput');
        const modalSearchResults = document.getElementById('modalProductSearchResults');
        const modalItemsBody = document.getElementById('modalOrderItemsBody');
        const modalNoItemsRow = document.getElementById('modalNoItemsRow');
        const modalSaveBtn = document.getElementById('modalSaveItemsBtn');
        const modalSubtotalPreview = document.getElementById('modalSubtotalPreview');
        let searchTimeout = null;
        let itemIndex = {{ $order->items->count() }}; // start from existing count

        if (modalSearchInput) {
            modalSearchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                var q = this.value.trim();
                if (q.length < 1) {
                    modalSearchResults.style.display = 'none';
                    return;
                }
                searchTimeout = setTimeout(function() {
                    fetch('{{ route("admin.orders.search-products") }}?q=' + encodeURIComponent(q), {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (!data.products || data.products.length === 0) {
                            modalSearchResults.innerHTML = '<div class="p-3 text-center text-muted small">No products found</div>';
                            modalSearchResults.style.display = 'block';
                            return;
                        }
                        window._currentModalSearchResults = data.products;
                        modalSearchResults.innerHTML = data.products.map(function(p, pIndex) {
                            var img = p.image ? '<img src="' + p.image + '" style="width:36px;height:36px;object-fit:cover;" class="rounded me-2">' : '<div class="bg-light rounded d-flex align-items-center justify-content-center me-2" style="width:36px;height:36px;"><i class="bi bi-image text-muted small"></i></div>';
                            var variantBtns = '';
                            if (p.variants && p.variants.length > 0) {
                                variantBtns = '<div class="mt-1">' + p.variants.map(function(v, vIndex) {
                                    return '<button type="button" class="btn btn-outline-secondary btn-sm me-1 mb-1 add-variant-btn" data-pindex="' + pIndex + '" data-vindex="' + vIndex + '" style="font-size:11px;padding:1px 6px;">' + v.label + ' (৳' + v.price.toFixed(0) + ')</button>';
                                }).join('') + '</div>';
                            }
                            return '<div class="d-flex align-items-start p-2 border-bottom product-search-item" style="cursor:pointer;" data-pindex="' + pIndex + '">' +
                                img +
                                '<div class="flex-grow-1">' +
                                    '<div class="fw-semibold small">' + p.name + '</div>' +
                                    '<div class="text-muted" style="font-size:11px;">SKU: ' + (p.sku || 'N/A') + ' | Stock: ' + p.stock + ' | ৳' + p.price.toFixed(0) + '</div>' +
                                    variantBtns +
                                '</div>' +
                            '</div>';
                        }).join('');
                        modalSearchResults.style.display = 'block';
                    }).catch(() => { modalSearchResults.style.display = 'none'; });
                }, 300);
            });

            document.addEventListener('click', function(e) {
                if (!modalSearchInput.contains(e.target) && !modalSearchResults.contains(e.target)) {
                    modalSearchResults.style.display = 'none';
                }
            });

            modalSearchResults.addEventListener('click', function(e) {
                var variantBtn = e.target.closest('.add-variant-btn');
                if (variantBtn) {
                    e.stopPropagation();
                    var pIndex = variantBtn.dataset.pindex;
                    var vIndex = variantBtn.dataset.vindex;
                    var product = window._currentModalSearchResults[pIndex];
                    var variant = product.variants[vIndex];
                    addItem(product, variant);
                    modalSearchResults.style.display = 'none';
                    modalSearchInput.value = '';
                    return;
                }
                var item = e.target.closest('.product-search-item');
                if (item) {
                    var pIndex = item.dataset.pindex;
                    var product = window._currentModalSearchResults[pIndex];
                    if (product && product.variants && product.variants.length > 0) return;
                    addItem(product, null);
                    modalSearchResults.style.display = 'none';
                    modalSearchInput.value = '';
                }
            });

            function addItem(product, variant) {
                if (modalNoItemsRow) modalNoItemsRow.style.display = 'none';
                var idx = itemIndex++;
                var price = variant ? variant.price : product.price;
                var name = product.name + (variant ? ' — ' + variant.label : '');
                var sku = variant ? (variant.sku || product.sku) : product.sku;
                var img = product.image ? '<img src="' + product.image + '" style="width:36px;height:36px;object-fit:cover;" class="rounded">' : '<div class="bg-light rounded d-flex align-items-center justify-content-center" style="width:36px;height:36px;"><i class="bi bi-image text-muted small"></i></div>';

                var tr = document.createElement('tr');
                tr.className = 'order-item-row';
                tr.dataset.idx = idx;
                tr.innerHTML =
                    '<td>' + img + '</td>' +
                    '<td>' +
                        '<div class="fw-semibold small">' + name + '</div>' +
                        '<div class="text-muted" style="font-size:11px;">SKU: ' + (sku || 'N/A') + '</div>' +
                        '<input type="hidden" name="items[' + idx + '][product_id]" value="' + product.id + '">' +
                        (variant ? '<input type="hidden" name="items[' + idx + '][variant_id]" value="' + variant.id + '">' : '') +
                    '</td>' +
                    '<td><input type="number" class="form-control form-control-sm item-price" name="items[' + idx + '][price]" value="' + price.toFixed(2) + '" min="0" step="0.01"></td>' +
                    '<td><input type="number" class="form-control form-control-sm item-qty" name="items[' + idx + '][quantity]" value="1" min="1" step="1"></td>' +
                    '<td class="text-end fw-semibold item-total">৳' + price.toFixed(2) + '</td>' +
                    '<td><button type="button" class="btn btn-sm btn-outline-danger remove-item-btn"><i class="bi bi-x-lg"></i></button></td>';
                modalItemsBody.appendChild(tr);
                recalculateModal();
            }

            modalItemsBody.addEventListener('click', function(e) {
                var btn = e.target.closest('.remove-item-btn');
                if (btn) {
                    btn.closest('tr').remove();
                    var rows = modalItemsBody.querySelectorAll('.order-item-row');
                    if (rows.length === 0 && modalNoItemsRow) modalNoItemsRow.style.display = '';
                    recalculateModal();
                }
            });

            modalItemsBody.addEventListener('input', function(e) {
                if (e.target.classList.contains('item-price') || e.target.classList.contains('item-qty')) {
                    var row = e.target.closest('tr');
                    var price = parseFloat(row.querySelector('.item-price').value) || 0;
                    var qty = parseInt(row.querySelector('.item-qty').value) || 1;
                    row.querySelector('.item-total').textContent = '৳' + (price * qty).toFixed(2);
                    recalculateModal();
                }
            });

            function recalculateModal() {
                var rows = modalItemsBody.querySelectorAll('.order-item-row');
                var subtotal = 0;
                rows.forEach(function(row) {
                    var price = parseFloat(row.querySelector('.item-price').value) || 0;
                    var qty = parseInt(row.querySelector('.item-qty').value) || 1;
                    subtotal += price * qty;
                });
                modalSubtotalPreview.textContent = '৳' + subtotal.toFixed(2);
                modalSaveBtn.disabled = rows.length === 0;
            }
        }

        // Realtime Order Status Update
        const updateStatusForm = document.getElementById('updateStatusForm');
        if (updateStatusForm) {
            updateStatusForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const btn = this.querySelector('button[type="submit"]');
                const originalHtml = btn.innerHTML;
                const statusSelect = this.querySelector('select[name="status"]');
                const statusVal = statusSelect.value;
                
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Updating...';

                fetch(this.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams(new FormData(this)).toString()
                })
                .then(r => r.json())
                .then(data => {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                    if (data.success) {
                        // Update the badge
                        const badge = document.getElementById('headerStatusBadge');
                        if (badge) {
                            badge.textContent = data.new_label;
                            badge.style.backgroundColor = data.new_color;
                        }
                        
                        // Hide Edit Items Button if restricted
                        const restrictedStatuses = ['shipped', 'delivered', 'cancelled', 'returned', 'refunded', 'completed', 'failed'];
                        const editItemsBtn = document.querySelector('button[data-bs-target="#editItemsModal"]');
                        if (editItemsBtn) {
                            if (restrictedStatuses.includes(data.new_status)) {
                                editItemsBtn.style.display = 'none';
                            } else {
                                editItemsBtn.style.display = '';
                            }
                        }

                        // Show success toast or alert
                        alert('Order status updated successfully.');
                    } else if (data.error) {
                        alert(data.error);
                    }
                })
                .catch(() => {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                    alert('An error occurred while updating status.');
                });
            });
        }
    })();
</script>
@endpush
