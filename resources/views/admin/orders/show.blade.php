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
                <span class="badge fs-6 text-white" style="background-color: {{ $headerStatusColor }};">
                    {{ $headerStatusLabel }}
                </span>
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
                                    <td>
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
</div>
@endsection
