@extends('admin.layouts.app')

@section('title', 'Orders')
@section('page-title', 'Orders')

@section('content')
@php
    $activeView = $view ?? 'all';
@endphp

@php
    $statusColorMap = [
        'pending' => '#f59e0b',
        'processing' => '#3b82f6',
        'shipped' => '#8b5cf6',
        'in_transit' => '#06b6d4',
        'out_for_delivery' => '#14b8a6',
        'delivered' => '#10b981',
        'cancelled' => '#ef4444',
        'returned' => '#f43f5e',
        'failed_delivery' => '#dc2626',
    ];

    $steadfastEnabled = filter_var(\App\Models\Setting::getValue('courier', 'steadfast_enabled', '0'), FILTER_VALIDATE_BOOLEAN);
    $pathaoEnabled = filter_var(\App\Models\Setting::getValue('courier', 'pathao_enabled', '0'), FILTER_VALIDATE_BOOLEAN);
@endphp

<style>
    .order-stat-card {
        transition: transform 0.2s, box-shadow 0.2s, background-color 0.2s;
        border: 1px solid rgba(0,0,0,0.05);
        background: #fff;
    }
    .order-stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 .5rem 1rem rgba(0,0,0,.08)!important;
    }
    .order-stat-card.active {
        background-color: var(--active-bg-color, #f8f9fa) !important;
        box-shadow: 0 0 0 2px var(--active-border-color, #e9ecef) !important;
        transform: translateY(-2px);
    }
    
    /* Courier Brand Styling Overrides */
    .text-steadfast-brand {
        color: #00B795 !important;
    }
    .text-pathao-brand {
        color: #E83434 !important;
    }
    .btn-outline-steadfast {
        color: #00B795 !important;
        border-color: #00B795 !important;
        background-color: transparent;
    }
    .btn-outline-steadfast:hover {
        color: #fff !important;
        background-color: #00B795 !important;
        border-color: #00B795 !important;
    }
    .btn-steadfast {
        color: #fff !important;
        background-color: #00B795 !important;
        border-color: #00B795 !important;
    }
    .btn-steadfast:hover {
        color: #fff !important;
        background-color: #00967a !important;
        border-color: #00967a !important;
    }
    .btn-outline-pathao {
        color: #E83434 !important;
        border-color: #E83434 !important;
        background-color: transparent;
    }
    .btn-outline-pathao:hover {
        color: #fff !important;
        background-color: #E83434 !important;
        border-color: #E83434 !important;
    }
    .btn-pathao {
        color: #fff !important;
        background-color: #E83434 !important;
        border-color: #E83434 !important;
    }
    .btn-pathao:hover {
        color: #fff !important;
        background-color: #c92c2c !important;
        border-color: #c92c2c !important;
    }
</style>

<div class="row g-2 mb-4">
    <!-- Total Orders -->
    <div class="col-6 col-sm-4 col-md-3 col-lg">
        <a href="{{ route('admin.orders.index') }}" class="text-decoration-none">
            <div class="card h-100 order-stat-card shadow-sm rounded-3 {{ $activeView === 'all' ? 'active' : '' }}" style="--active-border-color: #0d6efd; --active-bg-color: rgba(13,110,253,0.08); border-bottom: 3px solid #0d6efd;">
                <div class="card-body p-3">
                    <div class="text-muted text-uppercase fw-bold mb-1" style="font-size: 0.65rem; letter-spacing: 0.5px;">Total Orders</div>
                    <h3 class="mb-0 fw-bolder text-dark">{{ number_format($filterCounts['all'] ?? 0) }}</h3>
                </div>
            </div>
        </a>
    </div>

    @foreach($statuses as $st)
        @php
            $color = $st->color ?? ($statusColorMap[$st->key] ?? '#6c757d');
        @endphp
        <div class="col-6 col-sm-4 col-md-3 col-lg">
            <a href="{{ route('admin.orders.index', ['view' => $st->key]) }}" class="text-decoration-none">
                <div class="card h-100 order-stat-card shadow-sm rounded-3 {{ $activeView === $st->key ? 'active' : '' }}" style="border-bottom: 3px solid {{ $color }}; --active-border-color: {{ $color }}; --active-bg-color: {{ $color }}15;">
                    <div class="card-body p-3">
                        <div class="text-muted text-uppercase fw-bold mb-1 text-truncate" style="font-size: 0.65rem; letter-spacing: 0.5px;" title="{{ $st->label }}">{{ $st->label }}</div>
                        <h3 class="mb-0 fw-bolder" style="color: {{ $color }};">{{ number_format($filterCounts[$st->key] ?? 0) }}</h3>
                    </div>
                </div>
            </a>
        </div>
    @endforeach

    <div class="col-6 col-sm-4 col-md-3 col-lg">
        <a href="{{ route('admin.orders.index', ['view' => 'trash']) }}" class="text-decoration-none">
            <div class="card h-100 order-stat-card shadow-sm rounded-3 {{ $activeView === 'trash' ? 'active' : '' }}" style="border-bottom: 3px solid #6c757d; --active-border-color: #6c757d; --active-bg-color: rgba(108,117,125,0.08);">
                <div class="card-body p-3">
                    <div class="text-muted text-uppercase fw-bold mb-1" style="font-size: 0.65rem; letter-spacing: 0.5px;">Trash</div>
                    <h3 class="mb-0 fw-bolder" style="color: #6c757d;">{{ number_format($filterCounts['trash'] ?? 0) }}</h3>
                </div>
            </div>
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-receipt me-2"></i>Order Management (<span id="ordersResultCount">{{ $orders->total() }}</span>)</h6>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.orders.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i> Create Order
            </a>
            @if($activeView === 'trash')
                <span class="badge bg-secondary">Trash View</span>
            @endif
        </div>
    </div>

    <div class="card-body border-bottom">
        <form action="{{ route('admin.orders.index') }}" method="GET" data-realtime-filter="1" data-realtime-target="#ordersResultCount, #ordersTableWrap, #ordersPaginationWrap">
            @if($activeView !== 'all')
                <input type="hidden" name="view" value="{{ $activeView }}">
            @endif

            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1 fw-bold">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Order #, Name, Email, Phone">
                </div>
                
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1 fw-bold">Product</label>
                    <select name="product_id" class="form-select form-select-sm select2">
                        <option value="">All Products</option>
                        @if(isset($products))
                            @foreach($products as $product)
                                <option value="{{ $product->id }}" {{ request('product_id') == $product->id ? 'selected' : '' }}>
                                    {{ $product->name }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1 fw-bold">Date Filter Type</label>
                    <select name="date_type" class="form-select form-select-sm">
                        <option value="created_at" {{ request('date_type') === 'created_at' ? 'selected' : '' }}>Order Date</option>
                        <option value="delivered_at" {{ request('date_type') === 'delivered_at' ? 'selected' : '' }}>Delivery Date</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1 fw-bold">Date Range</label>
                    <div class="input-group input-group-sm">
                        <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" title="Start Date">
                        <span class="input-group-text border-0 bg-transparent px-1">-</span>
                        <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" title="End Date">
                    </div>
                </div>

                <!-- Collapsible Advanced Filters -->
                <div class="col-12 mt-2">
                    <button class="btn btn-sm btn-link text-decoration-none p-0" type="button" data-bs-toggle="collapse" data-bs-target="#advancedFilters" aria-expanded="{{ request()->hasAny(['status', 'payment_method', 'payment_status', 'order_source', 'shipping_method', 'shipping_district_id']) ? 'true' : 'false' }}">
                        <i class="bi bi-sliders"></i> Advanced Filters
                    </button>
                    
                    <div class="collapse mt-2 {{ request()->hasAny(['status', 'payment_method', 'payment_status', 'order_source', 'shipping_method', 'shipping_district_id']) ? 'show' : '' }}" id="advancedFilters">
                        <div class="row g-2 p-3 bg-light rounded border">
                            <div class="col-md-2">
                                <label class="form-label small text-muted mb-1">Order Status</label>
                                <select name="status" class="form-select form-select-sm">
                                    <option value="">All Statuses</option>
                                    @foreach($statuses as $st)
                                        <option value="{{ $st->key }}" {{ (request('status') === $st->key || $activeView === $st->key) ? 'selected' : '' }}>
                                            {{ $st->label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label small text-muted mb-1">Payment Method</label>
                                <select name="payment_method" class="form-select form-select-sm">
                                    <option value="">All Methods</option>
                                    @foreach($paymentMethods as $method)
                                        <option value="{{ $method }}" {{ request('payment_method') === $method ? 'selected' : '' }}>
                                            {{ ucfirst($method) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label small text-muted mb-1">Payment Status</label>
                                <select name="payment_status" class="form-select form-select-sm">
                                    <option value="">All Statuses</option>
                                    @foreach($paymentStatuses as $status)
                                        <option value="{{ $status }}" {{ request('payment_status') === $status ? 'selected' : '' }}>
                                            {{ ucfirst($status) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label small text-muted mb-1">Order Source</label>
                                <select name="order_source" class="form-select form-select-sm">
                                    <option value="">All Sources</option>
                                    @foreach($orderSources as $source)
                                        <option value="{{ $source }}" {{ request('order_source') === $source ? 'selected' : '' }}>
                                            {{ ucfirst($source) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label small text-muted mb-1">Shipping Method</label>
                                <select name="shipping_method" class="form-select form-select-sm">
                                    <option value="">All Shipping Methods</option>
                                    @if(isset($availableShippingMethods))
                                        @foreach($availableShippingMethods as $method)
                                            <option value="{{ $method->name }}" {{ request('shipping_method') === $method->name ? 'selected' : '' }}>
                                                {{ $method->name }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label small text-muted mb-1">District</label>
                                <select name="shipping_district_id" class="form-select form-select-sm select2">
                                    <option value="">All Districts</option>
                                    @if(isset($districts))
                                        @foreach($districts as $district)
                                            <option value="{{ $district->id }}" {{ request('shipping_district_id') == $district->id ? 'selected' : '' }}>
                                                {{ $district->name }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 mt-3 d-flex justify-content-end gap-2">
                    @if(request()->except(['view', 'page']))
                        <a href="{{ route('admin.orders.index', $activeView !== 'all' ? ['view' => $activeView] : []) }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-x"></i> Clear Filters
                        </a>
                    @endif
                    <button type="submit" class="btn btn-sm btn-primary px-4">
                        <i class="bi bi-funnel me-1"></i> Apply Filters
                    </button>
                </div>
            </div>
        </form>
    </div>

    <form action="{{ route('admin.orders.bulk-action') }}" method="POST" id="bulkOrderForm">
        @csrf
        <input type="hidden" name="view" value="{{ $activeView }}">

        <div class="card-body border-bottom">
            <div class="row g-2 align-items-center">
                <div class="col-md-4 col-lg-3">
                    <select class="form-select form-select-sm" name="bulk_action" id="bulkActionSelect" required>
                        <option value="">Bulk Mark / Action</option>
                        @if($activeView === 'trash')
                            <option value="restore">Restore Selected</option>
                            <option value="force_delete" class="text-danger">Permanently Delete</option>
                        @else
                            @foreach($bulkStatuses as $statusOption)
                                <option value="{{ $statusOption->key }}">Mark as {{ $statusOption->label }}</option>
                            @endforeach
                            @if($steadfastEnabled ?? false)
                                <option value="steadfast_send" class="text-steadfast-brand fw-bold" style="color: #00B795 !important;">🚀 Send to SteadFast Courier (Pending/Processing only)</option>
                            @endif
                            @if($pathaoEnabled ?? false)
                                <option value="pathao_send" class="text-pathao-brand fw-bold" style="color: #E83434 !important;">🚀 Send to Pathao Courier (Pending/Processing only)</option>
                            @endif
                            <option value="trash">Move to Trash</option>
                        @endif
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-primary" id="applyBulkActionBtn" disabled>
                        <i class="bi bi-check2-square me-1"></i> Apply
                    </button>
                </div>
                <div class="col text-muted small">
                    <span id="selectedOrderCount">0</span> selected
                </div>
            </div>
        </div>

        <div class="card-body p-0" id="ordersTableWrap">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="width: 40px;" class="text-center">
                                <input type="checkbox" class="form-check-input" id="selectAllOrders" aria-label="Select all orders">
                            </th>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th class="text-center">Items</th>
                            <th class="text-end">Total</th>
                            <th>Payment</th>
                            <th>Source</th>
                            <th>Courier</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    @php
                        $guestCheckoutEmail = strtolower(trim((string) config('shop.guest_checkout_user_email', 'guest.checkout@innercollection.local')));
                        $placeholderEmails = array_filter([
                            $guestCheckoutEmail,
                            'customer@local.invalid',
                            'guest@local.invalid',
                        ]);
                        $firstNonEmptyValue = static function (array $candidates): string {
                            foreach ($candidates as $candidate) {
                                $value = trim((string) ($candidate ?? ''));
                                if ($value !== '') {
                                    return $value;
                                }
                            }

                            return '';
                        };
                    @endphp
                    <tbody>
                        @forelse($orders as $order)
                            <tr>
                                @php
                                    $displayOrderNumber = $order->order_number ?? $order->id;
                                @endphp
                                <td class="text-center">
                                    <input type="checkbox" class="form-check-input order-checkbox" name="order_ids[]" value="{{ $order->id }}" aria-label="Select order {{ $displayOrderNumber }}">
                                </td>
                                <td>
                                    <a href="{{ route('admin.orders.show', $order) }}" class="text-decoration-none">
                                        <strong>#{{ $displayOrderNumber }}</strong>
                                    </a>
                                </td>
                                <td>
                                    @php
                                        $customerName = $order->customer_name;
                                        $checkoutPayloadForCustomer = is_array($order->checkout_fields_payload) ? $order->checkout_fields_payload : [];
                                        
                                        $rawUserName = trim((string) ($order->user?->name ?? ''));
                                        $rawUserEmail = trim((string) ($order->user?->email ?? ''));
                                        $isGuestCheckoutOrder = strtolower($rawUserEmail) === $guestCheckoutEmail || strtolower($rawUserName) === 'guest checkout';

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

                                        $customerEmail = '';
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

                                        $showUserProfileLink = (bool) ($order->user && !$isGuestCheckoutOrder);
                                    @endphp
                                    @if($showUserProfileLink)
                                        <a href="{{ route('admin.users.show', $order->user_id) }}" class="text-decoration-none">
                                            {{ $customerName }}
                                        </a>
                                    @else
                                        <span class="text-muted">{{ $customerName }}</span>
                                    @endif
                                    @if($customerEmail !== '')
                                        <div class="small text-muted">{{ $customerEmail }}</div>
                                    @endif
                                </td>
                                <td class="text-center">{{ $order->items_count ?? $order->items->count() }}</td>
                                <td class="text-end">
                                    <strong>৳{{ number_format($order->total, 2) }}</strong>
                                    @if($order->discount_amount > 0)
                                        <br><small class="text-success">-৳{{ number_format($order->discount_amount, 2) }}</small>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $methodLabels = ['cod' => 'COD', 'stripe' => 'Stripe', 'bkash' => 'bKash'];
                                        $statusColors = ['pending' => 'warning', 'awaiting' => 'info', 'paid' => 'success', 'failed' => 'danger', 'refunded' => 'secondary'];
                                    @endphp
                                    <span class="badge bg-{{ $statusColors[$order->payment_status] ?? 'secondary' }}">
                                        {{ $methodLabels[$order->payment_method] ?? ucfirst($order->payment_method ?? 'N/A') }}
                                    </span>
                                </td>
                                <td>
                                    @if($order->order_source)
                                        <span class="badge bg-secondary">{{ $order->order_source }}</span>
                                    @else
                                        <span class="text-muted small">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    @if($order->carrier)
                                        @php
                                            $carrierLower = strtolower($order->carrier);
                                            $carrierClass = $carrierLower === 'steadfast' ? 'text-steadfast-brand' : ($carrierLower === 'pathao' ? 'text-pathao-brand' : 'text-primary');
                                            $badgeBg = $carrierLower === 'steadfast' ? '#00B795' : ($carrierLower === 'pathao' ? '#E83434' : '#6c757d');
                                        @endphp
                                        <div class="small fw-semibold {{ $carrierClass }}">
                                            <i class="bi bi-truck text-muted me-1"></i>{{ ucfirst($order->carrier) }}
                                        </div>
                                        @if($order->tracking_number)
                                            <div class="mt-1">
                                                @if($order->carrier_tracking_url)
                                                    <a href="{{ $order->carrier_tracking_url }}" target="_blank" class="badge text-white d-inline-flex align-items-center gap-1 text-decoration-none px-2 py-1" style="background-color: {{ $badgeBg }} !important; font-size: 0.7rem;" title="Track Parcel">
                                                        #{{ $order->tracking_number }} <i class="bi bi-box-arrow-up-right" style="font-size: 0.65rem;"></i>
                                                    </a>
                                                @else
                                                    <span class="badge bg-secondary text-white d-inline-block px-2 py-1" style="font-size: 0.7rem;">
                                                        #{{ $order->tracking_number }}
                                                    </span>
                                                @endif
                                            </div>
                                        @endif
                                    @else
                                        @php
                                            $canSend = in_array($order->status, ['pending', 'processing']);
                                        @endphp
                                        
                                        @if($canSend && ($steadfastEnabled || $pathaoEnabled))
                                            <div class="d-flex flex-column gap-1">
                                                @if($steadfastEnabled)
                                                    <button type="button" class="btn btn-sm btn-outline-steadfast py-0 px-2 btn-send-steadfast" style="font-size: 0.75rem;" 
                                                            data-bs-toggle="modal" data-bs-target="#steadfastModal"
                                                            data-order-id="{{ $order->id }}"
                                                            data-order-total="{{ $order->total }}"
                                                            data-order-notes="{{ $order->notes }}"
                                                            data-customer-name="{{ trim($order->shipping_name) ?: ($order->user?->name ?: 'Guest') }}"
                                                            data-customer-phone="{{ $order->shipping_phone ?: $order->user?->phone }}"
                                                            data-customer-address="{{ trim($order->shipping_address . ' ' . ($order->checkout_fields_payload['shipping_location_text'] ?? '') . ' ' . ($order->checkout_fields_payload['shipping_area'] ?? '')) }}">
                                                        <i class="bi bi-send-check"></i> Send to SteadFast
                                                    </button>
                                                @endif
                                                @if($pathaoEnabled)
                                                    <button type="button" class="btn btn-sm btn-outline-pathao py-0 px-2 btn-send-pathao" style="font-size: 0.75rem;" 
                                                            data-bs-toggle="modal" data-bs-target="#pathaoModal"
                                                            data-order-id="{{ $order->id }}"
                                                            data-order-total="{{ $order->total }}"
                                                            data-order-notes="{{ $order->notes }}"
                                                            data-customer-name="{{ trim($order->shipping_name) ?: ($order->user?->name ?: 'Guest') }}"
                                                            data-customer-phone="{{ $order->shipping_phone ?: $order->user?->phone }}"
                                                            data-customer-address="{{ trim($order->shipping_address . ' ' . ($order->checkout_fields_payload['shipping_location_text'] ?? '') . ' ' . ($order->checkout_fields_payload['shipping_area'] ?? '')) }}"
                                                            data-order-items="{{ $order->items_count ?: $order->items()->count() ?: 1 }}">
                                                        <i class="bi bi-send-check"></i> Send to Pathao
                                                    </button>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $statusLabel = $order->statusConfig?->label ?? ucfirst(str_replace('_', ' ', $order->status));
                                        $statusColor = $order->statusConfig?->color ?? '#6C757D';
                                    @endphp
                                    <span class="badge text-white" style="background-color: {{ $statusColor }};">
                                        {{ $statusLabel }}
                                    </span>
                                </td>
                                <td>
                                    @php
                                        $createdAt = $order->created_at;
                                        if ($createdAt->diffInHours(now()) < 24) {
                                            $dateDisplay = $createdAt->diffForHumans();
                                        } else {
                                            $dateDisplay = $createdAt->format('d M, Y h:i:s A');
                                        }
                                        $delAt = $order->delivered_at;
                                    @endphp
                                    <div class="small fw-semibold">{{ $dateDisplay }}</div>
                                    @if($delAt)
                                        <div class="small text-muted" style="font-size: 0.75rem;" title="Delivered At">
                                            <i class="bi bi-truck me-1"></i>
                                            {{ $delAt->diffInHours(now()) < 24 ? $delAt->diffForHumans() : $delAt->format('d M, Y h:i A') }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-outline-info">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                    No orders found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </form>

    <div class="card-footer" id="ordersPaginationWrap">
        @include('admin.partials.pagination', ['paginator' => $orders])
    </div>
</div>

@php
    $cancellationReasonsStr = \App\Models\Setting::getValue('general', 'cancellation_reasons', 'Out of Stock,Customer Request,Fraudulent,Payment Failed,Other');
    $cancellationReasons = array_filter(array_map('trim', explode(',', $cancellationReasonsStr)));
    // Remove "Other" if it exists so we can always append it at the end
    $cancellationReasons = array_filter($cancellationReasons, fn($r) => strtolower($r) !== 'other');
@endphp

{{-- Cancel Order Modal --}}
<div class="modal fade" id="cancelOrderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger bg-opacity-10 border-0">
                <h5 class="modal-title text-danger"><i class="bi bi-x-circle me-2"></i>Cancel Order(s)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-3">Please select a reason for cancelling the selected order(s):</p>
                <select class="form-select mb-3" id="cancelReasonSelect">
                    <option value="">-- Select Reason (Optional) --</option>
                    @foreach($cancellationReasons as $reason)
                        <option value="{{ $reason }}">{{ $reason }}</option>
                    @endforeach
                    <option value="other">Other (Specify)</option>
                </select>
                <div id="cancelReasonOtherDiv" style="display:none;">
                    <label class="form-label small fw-semibold">Specify Reason <span class="text-muted fw-normal">(will be saved)</span></label>
                    <input type="text" class="form-control" id="cancelReasonOtherInput" placeholder="Type reason here...">
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-danger" id="confirmCancelOrderBtn">Confirm Cancellation</button>
            </div>
        </div>
    </div>
</div>

@if(filter_var(\App\Models\Setting::getValue('courier', 'steadfast_enabled', '0'), FILTER_VALIDATE_BOOLEAN))
{{-- Shared SteadFast Courier Modal --}}
<div class="modal fade" id="steadfastModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title"><i class="bi bi-truck me-2 text-steadfast-brand"></i>Send to SteadFast Courier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="steadfastForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="sf_recipient_name" class="form-label fw-bold">Recipient Name</label>
                        <input type="text" class="form-control" id="sf_recipient_name" name="recipient_name" required>
                    </div>

                    <div class="mb-3">
                        <label for="sf_recipient_phone" class="form-label fw-bold">Recipient Phone</label>
                        <input type="text" class="form-control" id="sf_recipient_phone" name="recipient_phone" required>
                    </div>

                    <div class="mb-3">
                        <label for="sf_recipient_address" class="form-label fw-bold">Recipient Address</label>
                        <textarea class="form-control" id="sf_recipient_address" name="recipient_address" rows="2" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="sf_cod_amount" class="form-label fw-bold">Cash on Delivery (COD) Amount</label>
                        <div class="input-group">
                            <span class="input-group-text">৳</span>
                            <input type="number" step="0.01" class="form-control" id="sf_cod_amount" name="cod_amount" required>
                        </div>
                        <small class="text-muted">By default, this is the total order amount. Edit if part of the payment was received in advance.</small>
                    </div>

                    <div class="mb-3">
                        <label for="sf_steadfast_note" class="form-label fw-bold">Delivery Note (Optional)</label>
                        <textarea class="form-control" id="sf_steadfast_note" name="note" rows="2" placeholder="Any specific instructions for the delivery man"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-steadfast">
                        <i class="bi bi-send-check me-1"></i> Send to Courier
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@if($pathaoEnabled)
{{-- Shared Pathao Courier Modal --}}
<div class="modal fade" id="pathaoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title"><i class="bi bi-truck me-2 text-pathao-brand"></i>Send to Pathao Courier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="pathaoForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="pathao_recipient_name" class="form-label fw-bold">Recipient Name</label>
                        <input type="text" class="form-control" id="pathao_recipient_name" name="recipient_name" required>
                    </div>

                    <div class="mb-3">
                        <label for="pathao_recipient_phone" class="form-label fw-bold">Recipient Phone</label>
                        <input type="text" class="form-control" id="pathao_recipient_phone" name="recipient_phone" required>
                    </div>

                    <div class="mb-3">
                        <label for="pathao_recipient_address" class="form-label fw-bold">Recipient Address</label>
                        <textarea class="form-control" id="pathao_recipient_address" name="recipient_address" rows="2" required></textarea>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="pathao_cod_amount" class="form-label fw-bold">COD Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">৳</span>
                                <input type="number" step="0.01" class="form-control" id="pathao_cod_amount" name="amount_to_collect" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="pathao_item_quantity" class="form-label fw-bold">Quantity</label>
                            <input type="number" class="form-control" id="pathao_item_quantity" name="item_quantity" value="1" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="pathao_item_weight" class="form-label fw-bold">Weight (KG)</label>
                            <input type="number" step="0.1" class="form-control" id="pathao_item_weight" name="item_weight" value="0.5" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Item Type</label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="item_type" id="pathao_item_type_parcel" value="2" checked>
                                    <label class="form-check-label" for="pathao_item_type_parcel">Parcel</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="item_type" id="pathao_item_type_document" value="1">
                                    <label class="form-check-label" for="pathao_item_type_document">Doc</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Delivery Type</label>
                        <div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="delivery_type" id="pathao_delivery_type_normal" value="48" checked>
                                <label class="form-check-label" for="pathao_delivery_type_normal">Normal (48h)</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="delivery_type" id="pathao_delivery_type_ondemand" value="12">
                                <label class="form-check-label" for="pathao_delivery_type_ondemand">On Demand (12h)</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="pathao_special_instruction" class="form-label fw-bold">Special Instruction (Optional)</label>
                        <textarea class="form-control" id="pathao_special_instruction" name="special_instruction" rows="2" placeholder="Instructions for Pathao courier"></textarea>
                    </div>

                    <!-- Manual Location Mapping Toggle -->
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="toggleManualLocation" name="manual_location" value="1">
                        <label class="form-check-label fw-bold" for="toggleManualLocation">Select Location Manually</label>
                    </div>

                    <!-- Manual Location Selects (Hidden by Default) -->
                    <div id="manualLocationContainer" class="p-3 border rounded bg-light mb-3" style="display: none;">
                        @php
                            $pathaoCities = \Illuminate\Support\Facades\DB::table('pathao_cities')->orderBy('name')->get();
                        @endphp
                        <div class="mb-3">
                            <label for="pathao_city" class="form-label fw-bold">City</label>
                            <select class="form-select" id="pathao_city" name="recipient_city">
                                <option value="">Select City</option>
                                @foreach($pathaoCities as $city)
                                    <option value="{{ $city->id }}">{{ $city->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="pathao_zone" class="form-label fw-bold">Zone</label>
                            <select class="form-select" id="pathao_zone" name="recipient_zone" disabled>
                                <option value="">Select Zone</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="pathao_area" class="form-label fw-bold">Area</label>
                            <select class="form-select" id="pathao_area" name="recipient_area" disabled>
                                <option value="">Select Area</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-pathao">
                        <i class="bi bi-send-check me-1"></i> Send to Pathao
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Pathao Bulk Courier Modal --}}
<div class="modal fade" id="pathaoBulkModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title text-pathao-brand"><i class="bi bi-truck me-2"></i>Bulk Send to Pathao</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-3">Please configure default options for the bulk dispatch to Pathao Courier:</p>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="bulk_pathao_item_type" class="form-label fw-bold">Item Type</label>
                        <select class="form-select" id="bulk_pathao_item_type">
                            <option value="2">Parcel</option>
                            <option value="1">Document</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="bulk_pathao_delivery_type" class="form-label fw-bold">Delivery Type</label>
                        <select class="form-select" id="bulk_pathao_delivery_type">
                            <option value="48">Normal (48h)</option>
                            <option value="12">On Demand (12h)</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="bulk_pathao_item_weight" class="form-label fw-bold">Default Weight (KG) per package</label>
                    <input type="number" step="0.1" class="form-control" id="bulk_pathao_item_weight" value="0.5" required>
                </div>

                <div class="mb-3">
                    <label for="bulk_pathao_special_instruction" class="form-label fw-bold">Delivery Notes / Special Instructions</label>
                    <textarea class="form-control" id="bulk_pathao_special_instruction" rows="2" placeholder="Instructions applied to all selected orders"></textarea>
                </div>
                
                <p class="small text-warning mt-2 mb-0">
                    <i class="bi bi-exclamation-triangle-fill"></i> Addresses will be auto-resolved on the backend using customer's Bangladeshi shipping district and upazila name mapping.
                </p>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-pathao" id="confirmPathaoBulkBtn">Confirm & Bulk Send</button>
            </div>
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // --- Steadfast Modal Logic ---
    const steadfastModalEl = document.getElementById('steadfastModal');
    if (steadfastModalEl) {
        steadfastModalEl.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const orderId = button.getAttribute('data-order-id');
            
            // Set action URL
            const form = document.getElementById('steadfastForm');
            form.action = '/admin/orders/' + orderId + '/steadfast';
            
            // Populate data
            document.getElementById('sf_recipient_name').value = button.getAttribute('data-customer-name') || '';
            document.getElementById('sf_recipient_phone').value = button.getAttribute('data-customer-phone') || '';
            document.getElementById('sf_recipient_address').value = button.getAttribute('data-customer-address') || '';
            document.getElementById('sf_cod_amount').value = button.getAttribute('data-order-total') || '';
            document.getElementById('sf_steadfast_note').value = button.getAttribute('data-order-notes') || '';
        });
    }

    const form = document.getElementById('bulkOrderForm');
    const selectAll = document.getElementById('selectAllOrders');
    const checkboxes = Array.from(document.querySelectorAll('.order-checkbox'));
    const selectedCount = document.getElementById('selectedOrderCount');
    const applyButton = document.getElementById('applyBulkActionBtn');
    const actionSelect = document.getElementById('bulkActionSelect');

    const syncSelectionState = function () {
        const total = checkboxes.length;
        const checked = checkboxes.filter(function (checkbox) {
            return checkbox.checked;
        }).length;

        selectedCount.textContent = String(checked);
        applyButton.disabled = checked === 0;

        if (total === 0) {
            selectAll.checked = false;
            selectAll.indeterminate = false;
            return;
        }

        selectAll.checked = checked > 0 && checked === total;
        selectAll.indeterminate = checked > 0 && checked < total;
    };

    selectAll.addEventListener('change', function () {
        checkboxes.forEach(function (checkbox) {
            checkbox.checked = selectAll.checked;
        });
        syncSelectionState();
    });

    checkboxes.forEach(function (checkbox) {
        checkbox.addEventListener('change', syncSelectionState);
    });

    let isBulkCancelConfirmed = false;
    const cancelModalEl = document.getElementById('cancelOrderModal');
    let cancelModal = null;

    if (cancelModalEl) {
        cancelModal = new bootstrap.Modal(cancelModalEl);
        document.getElementById('confirmCancelOrderBtn').addEventListener('click', function() {
            let val = document.getElementById('cancelReasonSelect').value;
            if (val === 'other') {
                val = document.getElementById('cancelReasonOtherInput').value.trim();
                if (!val) { alert('Please specify a reason'); return; }
            }
            isBulkCancelConfirmed = true;
            cancelModal.hide();
            
            if (val) {
                let hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'cancel_reason';
                hiddenInput.value = val;
                form.appendChild(hiddenInput);
            }
            form.submit();
        });
        
        document.getElementById('cancelReasonSelect').addEventListener('change', function() {
            document.getElementById('cancelReasonOtherDiv').style.display = this.value === 'other' ? 'block' : 'none';
            if (this.value === 'other') document.getElementById('cancelReasonOtherInput').focus();
        });
    }

    let isBulkPathaoConfirmed = false;
    const pathaoBulkModalEl = document.getElementById('pathaoBulkModal');
    let pathaoBulkModal = null;

    if (pathaoBulkModalEl) {
        pathaoBulkModal = new bootstrap.Modal(pathaoBulkModalEl);
        document.getElementById('confirmPathaoBulkBtn').addEventListener('click', function() {
            isBulkPathaoConfirmed = true;
            pathaoBulkModal.hide();
            
            const itemType = document.getElementById('bulk_pathao_item_type').value;
            const deliveryType = document.getElementById('bulk_pathao_delivery_type').value;
            const itemWeight = document.getElementById('bulk_pathao_item_weight').value;
            const specInstruction = document.getElementById('bulk_pathao_special_instruction').value;
            
            let inputItemType = document.createElement('input');
            inputItemType.type = 'hidden';
            inputItemType.name = 'item_type';
            inputItemType.value = itemType;
            form.appendChild(inputItemType);
            
            let inputDeliveryType = document.createElement('input');
            inputDeliveryType.type = 'hidden';
            inputDeliveryType.name = 'delivery_type';
            inputDeliveryType.value = deliveryType;
            form.appendChild(inputDeliveryType);
            
            let inputWeight = document.createElement('input');
            inputWeight.type = 'hidden';
            inputWeight.name = 'item_weight';
            inputWeight.value = itemWeight;
            form.appendChild(inputWeight);
            
            let inputInstruction = document.createElement('input');
            inputInstruction.type = 'hidden';
            inputInstruction.name = 'special_instruction';
            inputInstruction.value = specInstruction;
            form.appendChild(inputInstruction);
            
            form.submit();
        });
    }

    form.addEventListener('submit', function (event) {
        if (isBulkCancelConfirmed || isBulkPathaoConfirmed) return; // let it pass if confirmed via modal

        const checked = checkboxes.some(function (checkbox) {
            return checkbox.checked;
        });

        if (!checked) {
            event.preventDefault();
            alert('Please select at least one order.');
            return;
        }

        if (!actionSelect.value) {
            event.preventDefault();
            alert('Please select a bulk action.');
            return;
        }

        let confirmationMessage = 'Are you sure you want to apply this bulk action?';
        if (actionSelect.value === 'trash') {
            confirmationMessage = 'Are you sure you want to move selected orders to trash?';
        } else if (actionSelect.value === 'force_delete') {
            confirmationMessage = 'WARNING: Are you sure you want to permanently delete selected orders? This action cannot be undone!';
        }

        if (actionSelect.value === 'cancelled' && cancelModal) {
            event.preventDefault(); // Pause for modal
            document.getElementById('cancelReasonSelect').value = '';
            document.getElementById('cancelReasonOtherDiv').style.display = 'none';
            document.getElementById('cancelReasonOtherInput').value = '';
            cancelModal.show();
            return;
        }

        if (actionSelect.value === 'pathao_send' && pathaoBulkModal) {
            event.preventDefault(); // Pause for modal
            pathaoBulkModal.show();
            return;
        }

        if (!window.confirm(confirmationMessage)) {
            event.preventDefault();
        }
    });

    // --- Pathao Single Modal Logic ---
    const pathaoModalEl = document.getElementById('pathaoModal');
    if (pathaoModalEl) {
        pathaoModalEl.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const orderId = button.getAttribute('data-order-id');
            const pathaoForm = document.getElementById('pathaoForm');
            pathaoForm.action = '/admin/orders/' + orderId + '/pathao';
            
            document.getElementById('pathao_recipient_name').value = button.getAttribute('data-customer-name') || '';
            document.getElementById('pathao_recipient_phone').value = button.getAttribute('data-customer-phone') || '';
            document.getElementById('pathao_recipient_address').value = button.getAttribute('data-customer-address') || '';
            document.getElementById('pathao_cod_amount').value = button.getAttribute('data-order-total') || '';
            document.getElementById('pathao_item_quantity').value = button.getAttribute('data-order-items') || '1';
            document.getElementById('pathao_special_instruction').value = button.getAttribute('data-order-notes') || '';
            
            document.getElementById('toggleManualLocation').checked = false;
            document.getElementById('manualLocationContainer').style.display = 'none';
            const citySel = document.getElementById('pathao_city');
            if (citySel) {
                citySel.value = '';
                citySel.required = false;
            }
            const zoneSel = document.getElementById('pathao_zone');
            if (zoneSel) {
                zoneSel.innerHTML = '<option value="">Select Zone</option>';
                zoneSel.disabled = true;
                zoneSel.required = false;
            }
            const areaSel = document.getElementById('pathao_area');
            if (areaSel) {
                areaSel.innerHTML = '<option value="">Select Area</option>';
                areaSel.disabled = true;
                areaSel.required = false;
            }
        });
    }

    // --- Pathao Single Modal AJAX Loader ---
    const toggleManualLocation = document.getElementById('toggleManualLocation');
    const manualLocationContainer = document.getElementById('manualLocationContainer');
    const citySelect = document.getElementById('pathao_city');
    const zoneSelect = document.getElementById('pathao_zone');
    const areaSelect = document.getElementById('pathao_area');

    if (toggleManualLocation && manualLocationContainer) {
        toggleManualLocation.addEventListener('change', function() {
            if (this.checked) {
                manualLocationContainer.style.display = 'block';
                if (citySelect) citySelect.required = true;
                if (zoneSelect) zoneSelect.required = true;
                if (areaSelect) areaSelect.required = true;
            } else {
                manualLocationContainer.style.display = 'none';
                if (citySelect) citySelect.required = false;
                if (zoneSelect) zoneSelect.required = false;
                if (areaSelect) areaSelect.required = false;
            }
        });
    }

    if (citySelect) {
        citySelect.addEventListener('change', function() {
            const cityId = this.value;
            zoneSelect.innerHTML = '<option value="">Select Zone</option>';
            zoneSelect.disabled = true;
            areaSelect.innerHTML = '<option value="">Select Area</option>';
            areaSelect.disabled = true;

            if (!cityId) return;

            fetch(`/admin/pathao/zones?city_id=${cityId}`)
                .then(res => res.json())
                .then(data => {
                    data.forEach(zone => {
                        const option = document.createElement('option');
                        option.value = zone.id;
                        option.textContent = zone.name;
                        zoneSelect.appendChild(option);
                    });
                    zoneSelect.disabled = false;
                });
        });
    }

    if (zoneSelect) {
        zoneSelect.addEventListener('change', function() {
            const zoneId = this.value;
            areaSelect.innerHTML = '<option value="">Select Area</option>';
            areaSelect.disabled = true;

            if (!zoneId) return;

            fetch(`/admin/pathao/areas?zone_id=${zoneId}`)
                .then(res => res.json())
                .then(data => {
                    data.forEach(area => {
                        const option = document.createElement('option');
                        option.value = area.id;
                        option.textContent = area.name;
                        areaSelect.appendChild(option);
                    });
                    areaSelect.disabled = false;
                });
        });
    }

    syncSelectionState();
});
</script>
@endpush
