@extends('admin.layouts.app')

@section('title', 'Abandoned Cart Details')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Abandoned Cart #{{ $abandonedCart->id }}</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.abandoned-carts.index') }}">Abandoned Carts</a></li>
                    <li class="breadcrumb-item active">#{{ $abandonedCart->id }}</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            @if($abandonedCart->status !== 'pending')
            <form action="{{ route('admin.abandoned-carts.mark-pending', $abandonedCart) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-warning">
                    <i class="fas fa-undo me-1"></i> Mark Incomplete
                </button>
            </form>
            @endif

            @if(in_array($abandonedCart->status, ['pending', 'follow_up']))
            <button type="button" 
                    class="btn btn-info follow-up-trigger"
                    data-url="{{ route('admin.abandoned-carts.mark-follow-up', $abandonedCart) }}"
                    data-notes="{{ $abandonedCart->admin_notes }}"
                    data-date="{{ $abandonedCart->follow_up_date ? $abandonedCart->follow_up_date->format('Y-m-d') : '' }}">
                <i class="fas fa-phone me-1"></i> Mark Follow Up
            </button>
            @endif

            @if(in_array($abandonedCart->status, ['pending', 'follow_up']))
            <form action="{{ route('admin.abandoned-carts.mark-recovered', $abandonedCart) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-check-circle me-1"></i> Mark Recovered + Create Order
                </button>
            </form>
            <form action="{{ route('admin.abandoned-carts.mark-cancelled', $abandonedCart) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-secondary">
                    <i class="fas fa-ban me-1"></i> Cancel
                </button>
            </form>
            @endif
            <a href="{{ route('admin.abandoned-carts.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Main Info -->
        <div class="col-lg-8">
            <!-- Status Banner -->
            <div class="alert alert-{{ $abandonedCart->status_color }} d-flex justify-content-between align-items-center mb-4">
                <div>
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Status:</strong> {{ $abandonedCart->status_label }}
                    @if($abandonedCart->follow_up_date)
                        | <span class="badge bg-white text-{{ $abandonedCart->status_color }} ms-1">Next Follow Up: {{ $abandonedCart->follow_up_date->format('M d, Y') }}</span>
                    @endif
                    @if($abandonedCart->followed_up_at)
                        | Followed up {{ $abandonedCart->followed_up_at->diffForHumans() }}
                        @if($abandonedCart->followedUpBy)
                            by {{ $abandonedCart->followedUpBy->name }}
                        @endif
                    @endif
                </div>
                <span class="badge bg-{{ $abandonedCart->status_color }} fs-6">
                    {{ $abandonedCart->checkout_step_label }}
                </span>
            </div>

            <!-- Contact Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-user me-2"></i>Contact Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            @php
                                $payload = is_array($abandonedCart->checkout_fields_payload) ? $abandonedCart->checkout_fields_payload : [];
                                
                                $contactName = $abandonedCart->name 
                                    ?: ($payload['shipping_name'] ?? null)
                                    ?: ($payload['billing_name'] ?? null)
                                    ?: trim(($payload['billing_first_name'] ?? '') . ' ' . ($payload['billing_last_name'] ?? ''));
                                $contactName = $contactName !== '' ? $contactName : null;

                                $contactPhone = $abandonedCart->phone 
                                    ?: ($payload['shipping_phone'] ?? null)
                                    ?: ($payload['billing_phone'] ?? null);
                                $contactPhone = $contactPhone !== '' ? $contactPhone : null;

                                $contactEmail = $abandonedCart->email 
                                    ?: ($payload['shipping_email'] ?? null)
                                    ?: ($payload['billing_email'] ?? null);
                                $contactEmail = $contactEmail !== '' ? $contactEmail : null;
                            @endphp
                            <table class="table table-borderless mb-0">
                                <tr>
                                    <th width="120">Name</th>
                                    <td>
                                        @if($contactName)
                                            <strong>{{ $contactName }}</strong>
                                        @else
                                            <span class="text-muted">Not provided</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Phone</th>
                                    <td>
                                        @if($contactPhone)
                                            <a href="tel:{{ $contactPhone }}" class="btn btn-sm btn-success">
                                                <i class="fas fa-phone me-1"></i>{{ $contactPhone }}
                                            </a>
                                        @else
                                            <span class="text-muted">Not provided</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Email</th>
                                    <td>
                                        @if($contactEmail)
                                            <a href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a>
                                        @else
                                            <span class="text-muted">Not provided</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            @if($abandonedCart->user)
                            <div class="alert alert-info mb-0">
                                <strong><i class="fas fa-user-check me-1"></i> Registered User</strong><br>
                                {{ $abandonedCart->user->name }}<br>
                                <small>{{ $abandonedCart->user->email }}</small>
                            </div>
                            @else
                            <div class="alert alert-secondary mb-0">
                                <strong><i class="fas fa-user-secret me-1"></i> Guest Checkout</strong><br>
                                <small class="text-muted">User was not logged in</small>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Shipping Address -->
            @php
                $hasShippingDetails =
                    !empty($abandonedCart->shipping_address)
                    || !empty($abandonedCart->shipping_location_text)
                    || !empty($abandonedCart->shipping_area)
                    || !empty($abandonedCart->shipping_division)
                    || !empty($abandonedCart->shipping_district)
                    || !empty($abandonedCart->shipping_upazila)
                    || !empty($abandonedCart->shipping_union)
                    || !empty($abandonedCart->shipping_city)
                    || !empty($abandonedCart->shipping_state)
                    || !empty($abandonedCart->shipping_zip)
                    || !empty($abandonedCart->shipping_country);
            @endphp
            @if($hasShippingDetails)
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-map-marker-alt me-2"></i>Shipping Address
                    </h5>
                </div>
                <div class="card-body">
                    @php
                        $locationText = trim((string) $abandonedCart->shipping_location_text);
                        $shippingAddressLine = trim((string) $abandonedCart->shipping_address);
                        $showLocationText = $locationText !== '' && strcasecmp($locationText, $shippingAddressLine) !== 0;
                        $dropdownParts = array_filter([
                            $abandonedCart->shipping_union,
                            $abandonedCart->shipping_upazila,
                            $abandonedCart->shipping_district,
                            $abandonedCart->shipping_division,
                        ]);
                    @endphp

                    <div class="small text-muted mb-3">Captured directly from checkout form input.</div>

                    <address class="mb-0">
                        @if($shippingAddressLine !== '')
                            <div class="mb-1"><strong>Address:</strong> {{ $shippingAddressLine }}</div>
                        @endif

                        @if(!empty($abandonedCart->shipping_area))
                            <div class="mb-1"><strong>Area:</strong> {{ $abandonedCart->shipping_area }}</div>
                        @endif

                        @if($showLocationText)
                            <div class="mb-1"><strong>Location Text:</strong> {{ $locationText }}</div>
                        @endif

                        @if(!empty($dropdownParts))
                            <div class="mb-1"><strong>Dropdown Location:</strong> {{ implode(', ', $dropdownParts) }}</div>
                        @endif

                        @if($abandonedCart->shipping_city || $abandonedCart->shipping_state || $abandonedCart->shipping_zip)
                            <div class="mb-1">
                                <strong>City/State:</strong>
                                @if($abandonedCart->shipping_city){{ $abandonedCart->shipping_city }}@endif
                                @if($abandonedCart->shipping_state), {{ $abandonedCart->shipping_state }}@endif
                                @if($abandonedCart->shipping_zip) {{ $abandonedCart->shipping_zip }}@endif
                            </div>
                        @endif

                        @if($abandonedCart->shipping_country)
                            <div><strong>Country:</strong> {{ $abandonedCart->shipping_country }}</div>
                        @endif
                    </address>
                </div>
            </div>
            @endif

            @php
                $checkoutFieldSnapshot = collect(is_array($abandonedCart->checkout_fields_payload) ? $abandonedCart->checkout_fields_payload : [])
                    ->filter(function ($value, $key) {
                        $normalizedKey = strtolower(trim((string) $key));

                        if ($normalizedKey === '') {
                            return false;
                        }

                        if (is_string($value)) {
                            return trim($value) !== '';
                        }

                        return is_bool($value) || is_int($value) || is_float($value);
                    });
            @endphp
            @if($checkoutFieldSnapshot->isNotEmpty())
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-list-alt me-2"></i>Checkout Field Snapshot
                    </h5>
                </div>
                <div class="card-body">
                    <div class="small text-muted mb-3">Live field values synced from checkout (including additional and custom fields).</div>

                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <tbody>
                                @foreach($checkoutFieldSnapshot as $fieldKey => $fieldValue)
                                    @php
                                        $normalizedFieldKey = strtolower(trim((string) $fieldKey));
                                        $fieldLabel = match ($normalizedFieldKey) {
                                            'order_notes', 'notes' => 'Order Notes',
                                            'shipping_name' => 'Shipping Name',
                                            'shipping_email' => 'Shipping Email',
                                            'shipping_phone' => 'Shipping Phone',
                                            'shipping_address' => 'Shipping Address',
                                            'shipping_location_text' => 'Shipping Location Text',
                                            'shipping_division_id' => 'Shipping Division',
                                            'shipping_district_id' => 'Shipping District',
                                            'shipping_upazila_id' => 'Shipping Upazila',
                                            'shipping_union_id' => 'Shipping Union',
                                            default => \Illuminate\Support\Str::headline(str_replace('_', ' ', $normalizedFieldKey)),
                                        };

                                        $formattedValue = is_bool($fieldValue)
                                            ? ($fieldValue ? 'Yes' : 'No')
                                            : trim((string) $fieldValue);
                                    @endphp
                                    <tr>
                                        <th class="text-muted" style="width: 240px;">{{ $fieldLabel }}</th>
                                        <td>
                                            @if($formattedValue !== '')
                                                <div style="white-space: pre-line;">{{ $formattedValue }}</div>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <!-- Cart Items -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-shopping-cart me-2"></i>Cart Items
                    </h5>
                    <span class="badge bg-primary">{{ $abandonedCart->item_count }} items</span>
                </div>
                <div class="card-body p-0">
                    @if($abandonedCart->cart_items && count($abandonedCart->cart_items) > 0)
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end">Price</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($abandonedCart->cart_items as $item)
                                <tr>
                                    <td>
                                        @php
                                            $variantName = trim((string) ($item['variant_name'] ?? ''));
                                            $variantAttributes = trim((string) ($item['variant_attributes'] ?? ''));
                                            $variantSku = trim((string) ($item['variant_sku'] ?? ''));
                                            $variantId = !empty($item['variant_id']) ? (int) $item['variant_id'] : null;

                                            if ($variantId) {
                                                $variantModel = \App\Models\ProductVariant::with('attributeValues.attribute')->find($variantId);
                                                if ($variantModel) {
                                                    $variantSku = $variantSku !== '' ? $variantSku : trim((string) $variantModel->sku);
                                                    $variantName = $variantName !== '' ? $variantName : trim((string) $variantModel->name);
                                                    
                                                    $dynamicAttributes = $variantModel->attributeValues->map(function ($attrVal) {
                                                        $attrName = trim((string) ($attrVal->attribute?->name ?? ''));
                                                        $val = trim((string) ($attrVal->value ?? ''));
                                                        if ($val === '') return null;
                                                        return $attrName !== '' ? "{$attrName}: {$val}" : $val;
                                                    })->filter()->implode(', ');
                                                    
                                                    if ($dynamicAttributes !== '') {
                                                        $variantAttributes = $dynamicAttributes;
                                                    }
                                                }
                                            }

                                            $variantLabel = $variantAttributes !== ''
                                                ? $variantAttributes
                                                : ($variantName !== ''
                                                    ? $variantName
                                                    : ($variantId ? ('Variant #' . $variantId) : ''));
                                        @endphp
                                        <div class="d-flex align-items-center">
                                            <div class="flex-shrink-0 me-3">
                                                @php
                                                    $imgUrl = $item['product_image'] ?? '';
                                                    if ($imgUrl !== '' && !str_starts_with($imgUrl, 'http') && !str_starts_with($imgUrl, 'data:')) {
                                                        $imgUrl = Storage::url($imgUrl);
                                                    }
                                                @endphp
                                                @if($imgUrl !== '')
                                                    <img src="{{ $imgUrl }}" alt="Product Image" class="rounded" style="width: 48px; height: 48px; object-fit: cover;">
                                                @else
                                                    <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                                        <i class="fas fa-image text-muted opacity-50"></i>
                                                    </div>
                                                @endif
                                            </div>
                                            <div>
                                                <strong>{{ $item['product_name'] ?? ('Product #' . ($item['product_id'] ?? 'Unknown')) }}</strong>
                                                @if($variantLabel)
                                                    <div class="small text-muted mt-1">{{ $variantLabel }}</div>
                                                @endif
                                                @if(!empty($item['product_sku']))
                                                    <div class="small text-muted mt-1">SKU: {{ $item['product_sku'] }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">{{ $item['quantity'] ?? 1 }}</td>
                                    <td class="text-end">৳{{ number_format($item['price'] ?? 0, 2) }}</td>
                                    <td class="text-end">৳{{ number_format($item['subtotal'] ?? 0, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Subtotal</strong></td>
                                    <td class="text-end">৳{{ number_format($abandonedCart->subtotal, 2) }}</td>
                                </tr>
                                @if($abandonedCart->discount_amount > 0)
                                <tr class="text-success">
                                    <td colspan="3" class="text-end">
                                        Discount
                                        @if($abandonedCart->coupon_code)
                                        <span class="badge bg-success">{{ $abandonedCart->coupon_code }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end">-৳{{ number_format($abandonedCart->discount_amount, 2) }}</td>
                                </tr>
                                @endif
                                <tr>
                                    <th colspan="3" class="text-end">Total</th>
                                    <th class="text-end fs-5">৳{{ number_format($abandonedCart->total, 2) }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-box-open fa-3x mb-3 opacity-50"></i>
                        <p class="mb-0">No cart items data available</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Quick Info -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2"></i>Details
                    </h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <th>Created</th>
                            <td>{{ $abandonedCart->created_at->format('M d, Y H:i') }}</td>
                        </tr>
                        <tr>
                            <th>Last Activity</th>
                            <td>
                                @if($abandonedCart->last_activity_at)
                                    {{ $abandonedCart->last_activity_at->format('M d, Y H:i') }}
                                    <br><small class="text-muted">{{ $abandonedCart->last_activity_at->diffForHumans() }}</small>
                                @else
                                    <span class="text-muted">Unknown</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Payment Method</th>
                            <td>
                                @if($abandonedCart->payment_method)
                                    {{ ucfirst($abandonedCart->payment_method) }}
                                @else
                                    <span class="text-muted">Not selected</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Shipping Method</th>
                            <td>
                                @if($abandonedCart->shipping_method)
                                    {{ ucfirst($abandonedCart->shipping_method) }}
                                @else
                                    <span class="text-muted">Not selected</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>IP Address</th>
                            <td>{{ $abandonedCart->ip_address ?? 'Unknown' }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Recovery Info -->
            @if($abandonedCart->status === 'recovered')
            <div class="card mb-4 border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-check-circle me-2"></i>Recovered!
                    </h5>
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        <strong>Recovered:</strong> {{ $abandonedCart->recovered_at->format('M d, Y H:i') }}
                    </p>
                    @if($abandonedCart->recoveredOrder)
                    <a href="{{ route('admin.orders.show', $abandonedCart->recoveredOrder) }}" class="btn btn-success w-100">
                        <i class="fas fa-receipt me-1"></i> View Order #{{ $abandonedCart->recoveredOrder->order_number }}
                    </a>
                    @endif
                </div>
            </div>
            @endif

            <!-- Admin Notes -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-sticky-note me-2"></i>Admin Notes
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.abandoned-carts.update-notes', $abandonedCart) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <textarea name="admin_notes" class="form-control" rows="4" placeholder="Add notes about this abandoned cart...">{{ $abandonedCart->admin_notes }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-save me-1"></i> Save Notes
                        </button>
                    </form>
                </div>
            </div>

            {{-- Fraud Blocker Quick Actions --}}
            <div class="card mb-4 border-danger border-opacity-25">
                <div class="card-header bg-danger bg-opacity-10 d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-shield-alt me-2 text-danger"></i><strong class="text-danger">Fraud Blocker</strong></span>
                    <a href="{{ route('admin.fraud-blocks.index') }}" class="btn btn-sm btn-outline-danger py-0 px-2">
                        <small>View All</small>
                    </a>
                </div>
                <div class="card-body p-0">
                    @php
                        $fraudPhone = trim((string) ($abandonedCart->phone ?? ''));
                        if ($fraudPhone === '') {
                            $fraudPhone = trim((string) ($payload['shipping_phone'] ?? ($payload['billing_phone'] ?? '')));
                        }
                        
                        $fraudEmail = trim((string) ($abandonedCart->email ?? ''));
                        if ($fraudEmail === '') {
                            $fraudEmail = trim((string) ($payload['shipping_email'] ?? ($payload['billing_email'] ?? '')));
                        }
                        
                        $fraudIp = trim((string) ($abandonedCart->ip_address ?? ''));
                        $fraudDevice = trim((string) ($abandonedCart->user_agent ?? ''));
                        
                        $fraudItems = [];
                        if ($fraudPhone !== '') {
                            $fraudItems[] = ['type' => 'phone', 'value' => $fraudPhone, 'icon' => 'fa-phone', 'label' => 'Phone'];
                        }
                        if ($fraudEmail !== '' && filter_var($fraudEmail, FILTER_VALIDATE_EMAIL)) {
                            $fraudItems[] = ['type' => 'email', 'value' => $fraudEmail, 'icon' => 'fa-envelope', 'label' => 'Email'];
                        }
                        if ($fraudIp !== '') {
                            $fraudItems[] = ['type' => 'ip', 'value' => $fraudIp, 'icon' => 'fa-globe', 'label' => 'IP'];
                        }
                        if ($fraudDevice !== '') {
                            $fraudItems[] = ['type' => 'device', 'value' => $fraudDevice, 'icon' => 'fa-laptop', 'label' => 'Device'];
                        }
                    @endphp

                    <div class="list-group list-group-flush">
                        @forelse($fraudItems as $fi)
                            @php
                                $isCurrentlyBlocked = \App\Models\FraudBlock::isBlocked($fi['type'], $fi['value']);
                            @endphp
                            <div class="list-group-item d-flex justify-content-between align-items-center py-2 fraud-block-item" data-type="{{ $fi['type'] }}" data-value="{{ $fi['value'] }}">
                                <div class="text-truncate me-2">
                                    <i class="fas {{ $fi['icon'] }} me-1 text-muted"></i>
                                    <small class="text-truncate" title="{{ $fi['value'] }}">{{ Str::limit($fi['value'], 25) }}</small>
                                </div>
                                @if($isCurrentlyBlocked)
                                    <button type="button"
                                        class="btn btn-sm btn-danger fraud-unblock-btn"
                                        title="Click to unblock">
                                        <i class="fas fa-shield-alt"></i>
                                        <small>Blocked</small>
                                    </button>
                                @else
                                    <button type="button"
                                        class="btn btn-sm btn-outline-danger fraud-open-modal-btn"
                                        title="Click to block">
                                        <i class="fas fa-plus"></i>
                                        <small>Block</small>
                                    </button>
                                @endif
                            </div>
                        @empty
                            <div class="list-group-item text-center text-muted py-3">
                                <small>No blockable data found.</small>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="card border-danger">
                <div class="card-header bg-danger bg-opacity-10">
                    <h5 class="card-title mb-0 text-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>Danger Zone
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.abandoned-carts.destroy', $abandonedCart) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this abandoned cart record?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger w-100">
                            <i class="fas fa-trash me-1"></i> Delete Record
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<div class="modal fade" id="followUpModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="followUpForm" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Schedule Follow Up</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Next Follow Up Date</label>
                        <input type="date" name="follow_up_date" id="follow_up_date" class="form-control" min="{{ date('Y-m-d') }}">
                        <small class="text-muted">Set a date when you plan to follow up with this customer.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Admin Notes</label>
                        <textarea name="admin_notes" id="admin_notes" class="form-control" rows="3" placeholder="Add any specific details for this follow up..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Follow Up</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Fraud Block Modal --}}
<div class="modal fade" id="fraudBlockModal" tabindex="-1" aria-labelledby="fraudBlockModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger bg-opacity-10 border-0">
                <h5 class="modal-title text-danger" id="fraudBlockModalLabel">
                    <i class="fas fa-shield-alt me-2"></i>Block Confirmation
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
                    <i class="fas fa-times me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-danger" id="fraudModalBlockBtn">
                    <i class="fas fa-shield-alt me-1"></i>Block
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const followUpModal = new bootstrap.Modal(document.getElementById('followUpModal'));
    const followUpForm = document.getElementById('followUpForm');
    const followUpDateInput = document.getElementById('follow_up_date');
    const followUpNotesInput = document.getElementById('admin_notes');

    document.querySelectorAll('.follow-up-trigger').forEach(btn => {
        btn.addEventListener('click', function() {
            const url = this.getAttribute('data-url');
            const notes = this.getAttribute('data-notes');
            const date = this.getAttribute('data-date');

            followUpForm.action = url;
            followUpNotesInput.value = notes || '';
            followUpDateInput.value = date || '';
            
            followUpModal.show();
        });
    });

    // Fraud Blocker logic
    const defaultMessages = {
        'phone': @json(\App\Models\Setting::getValue('fraud_blocks', 'default_phone_msg', '')),
        'email': @json(\App\Models\Setting::getValue('fraud_blocks', 'default_email_msg', '')),
        'ip': @json(\App\Models\Setting::getValue('fraud_blocks', 'default_ip_msg', '')),
        'device': @json(\App\Models\Setting::getValue('fraud_blocks', 'default_device_msg', ''))
    };

    const fraudModalEl = document.getElementById('fraudBlockModal');
    if(fraudModalEl) {
        const fraudModal = new bootstrap.Modal(fraudModalEl);
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
                
                modalReason.value = 'Blocked from Abandoned Cart #{{ $abandonedCart->id }}';
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
                        reason: reason,
                        custom_message: customMessage
                    })
                })
                .then(r => r.json())
                .then(data => {
                    modalBlockBtn.disabled = false;
                    modalBlockBtn.innerHTML = '<i class="fas fa-shield-alt me-1"></i>Block';
                    
                    if (data.success && currentBlockButton) {
                        fraudModal.hide();
                        window.location.reload();
                    } else if(data.message) {
                        alert(data.message);
                    }
                })
                .catch(() => {
                    modalBlockBtn.disabled = false;
                    modalBlockBtn.innerHTML = '<i class="fas fa-shield-alt me-1"></i>Block';
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
                    btn.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error';
                });
            });
        });
    }
});
</script>
@endpush
@endsection
