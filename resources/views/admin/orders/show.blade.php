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
                    <h6 class="mb-0 fw-semibold">
                        <i class="bi bi-receipt me-2"></i>Order #{{ $order->order_number ?? $order->id }}
                        <small class="text-muted ms-2">ID: {{ $order->id }}</small>
                    </h6>
                </div>
                @php
                    $headerStatusLabel = $order->statusConfig?->label ?? ucfirst(str_replace('_', ' ', $order->status));
                    $headerStatusColor = $order->statusConfig?->color ?? '#6C757D';
                @endphp
                <div class="d-flex align-items-center gap-2">
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-primary dropdown-toggle shadow-sm" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-printer me-1"></i> Documents
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow">
                            <li><a class="dropdown-item py-2" href="{{ route('admin.orders.invoice', $order) }}" target="_blank"><i class="bi bi-file-earmark-pdf me-2 text-danger"></i>Print Invoice</a></li>
                            <li><a class="dropdown-item py-2" href="{{ route('admin.orders.packaging-slip', $order) }}" target="_blank"><i class="bi bi-box-seam me-2 text-primary"></i>Packaging Slip</a></li>
                        </ul>
                    </div>
                    <span class="badge fs-6 text-white" style="background-color: {{ $headerStatusColor }};">
                        {{ $headerStatusLabel }}
                    </span>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="text-muted small text-uppercase mb-2">Customer Information</h6>
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
                            $isGuestCheckoutOrder = strtolower($rawUserEmail) === $guestCheckoutEmail || strtolower($rawUserName) === 'guest checkout';

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

                            $customerEmail = 'Not provided';
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
                        <p class="mb-1"><strong>{{ $customerName }}</strong></p>
                        <p class="mb-1">{{ $customerEmail }}</p>
                        @if($customerPhone !== '')
                            <p class="mb-0">{{ $customerPhone }}</p>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted small text-uppercase mb-2">Shipping Address</h6>
                        @php
                            $shippingAddress = trim((string) ($order->shipping_address ?? ''));
                            $shippingLocationText = trim((string) ((is_array($order->checkout_fields_payload) ? ($order->checkout_fields_payload['shipping_location_text'] ?? null) : null) ?? ''));
                            $shippingArea = trim((string) ((is_array($order->checkout_fields_payload) ? ($order->checkout_fields_payload['shipping_area'] ?? null) : null) ?? ''));
                            $shippingHierarchy = implode(', ', array_filter([
                                $order->shippingUnion?->name,
                                $order->shippingUpazila?->name,
                                $order->shippingDistrict?->name,
                                $order->shippingDivision?->name,
                            ]));
                            $shippingCityStateZip = implode(', ', array_filter([
                                $order->shipping_city,
                                $order->shipping_state,
                                $order->shipping_zip,
                            ]));
                            $shippingCountry = trim((string) ($order->shipping_country ?? ''));
                        @endphp

                        @if($shippingAddress !== '')
                            <p class="mb-1">{{ $shippingAddress }}</p>
                        @else
                            <p class="mb-1 text-muted">Not provided</p>
                        @endif

                        @if($shippingLocationText !== '' && $shippingLocationText !== $shippingAddress)
                            <p class="mb-1"><strong>Location:</strong> {{ $shippingLocationText }}</p>
                        @endif

                        @if($shippingArea !== '')
                            <p class="mb-1"><strong>Area:</strong> {{ $shippingArea }}</p>
                        @endif

                        @if($shippingHierarchy !== '')
                            <p class="mb-1"><strong>Division chain:</strong> {{ $shippingHierarchy }}</p>
                        @endif

                        @if($shippingCityStateZip !== '')
                            <p class="mb-1">{{ $shippingCityStateZip }}</p>
                        @endif

                        @if($shippingCountry !== '')
                            <p class="mb-0">{{ $shippingCountry }}</p>
                        @endif
                    </div>
                </div>

                <hr>

                <h6 class="mb-3 fw-semibold"><i class="bi bi-box me-2"></i>Order Items</h6>
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
                    <h6>Notes</h6>
                    <p class="text-muted">{{ $order->notes }}</p>
                @endif

                @php
                    $rawCheckoutPayload = is_array($order->checkout_fields_payload) ? $order->checkout_fields_payload : [];
                    $checkoutPayload = array_filter($rawCheckoutPayload, static function ($value) {
                        if ($value === null) {
                            return false;
                        }

                        return trim((string) $value) !== '';
                    });

                    $checkoutFieldLabels = [
                        'shipping_name' => 'Shipping Name',
                        'shipping_email' => 'Shipping Email',
                        'shipping_phone' => 'Shipping Phone',
                        'shipping_address' => 'Shipping Address',
                        'shipping_location_text' => 'Shipping Location',
                        'shipping_area' => 'Shipping Area',
                        'shipping_division_id' => 'Shipping Division',
                        'shipping_district_id' => 'Shipping District',
                        'shipping_upazila_id' => 'Shipping Upazila',
                        'shipping_union_id' => 'Shipping Union',
                        'shipping_city' => 'Shipping City',
                        'shipping_state' => 'Shipping State',
                        'shipping_zip' => 'Shipping ZIP/Postal Code',
                        'shipping_country' => 'Shipping Country',
                        'billing_first_name' => 'Billing First Name',
                        'billing_last_name' => 'Billing Last Name',
                        'billing_name' => 'Billing Name',
                        'billing_email' => 'Billing Email',
                        'billing_phone' => 'Billing Phone',
                        'billing_address_1' => 'Billing Address Line 1',
                        'billing_address_2' => 'Billing Address Line 2',
                        'billing_city' => 'Billing City',
                        'billing_state' => 'Billing State',
                        'billing_postcode' => 'Billing ZIP/Postal Code',
                        'billing_country' => 'Billing Country',
                        'order_notes' => 'Order Notes',
                        'notes' => 'Notes',
                    ];
                @endphp

                @if(!empty($checkoutPayload))
                    <hr>
                    <h6 class="mb-3 fw-semibold"><i class="bi bi-file-earmark-text me-2"></i>Checkout Details</h6>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <tbody>
                                @foreach($checkoutPayload as $fieldKey => $rawValue)
                                    @php
                                        $normalizedKey = strtolower(trim((string) $fieldKey));
                                        $label = $checkoutFieldLabels[$normalizedKey] ?? ucwords(str_replace('_', ' ', $normalizedKey));
                                        $displayValue = trim((string) $rawValue);

                                        if ($normalizedKey === 'shipping_division_id' && $order->shippingDivision?->name) {
                                            $displayValue = $order->shippingDivision->name;
                                        } elseif ($normalizedKey === 'shipping_district_id' && $order->shippingDistrict?->name) {
                                            $displayValue = $order->shippingDistrict->name;
                                        } elseif ($normalizedKey === 'shipping_upazila_id' && $order->shippingUpazila?->name) {
                                            $displayValue = $order->shippingUpazila->name;
                                        } elseif ($normalizedKey === 'shipping_union_id' && $order->shippingUnion?->name) {
                                            $displayValue = $order->shippingUnion->name;
                                        }
                                    @endphp
                                    <tr>
                                        <th class="text-muted" style="width: 35%;">{{ $label }}</th>
                                        <td>{{ $displayValue }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
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
                <form action="{{ route('admin.orders.update-status', $order) }}" method="POST">
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
    document.addEventListener('DOMContentLoaded', function() {
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
    });
</script>
@endpush
