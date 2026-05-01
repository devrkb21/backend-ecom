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
            @if($abandonedCart->status === 'pending')
            <form action="{{ route('admin.abandoned-carts.mark-follow-up', $abandonedCart) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-info">
                    <i class="fas fa-phone me-1"></i> Mark Follow Up
                </button>
            </form>
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
                            <table class="table table-borderless mb-0">
                                <tr>
                                    <th width="120">Name</th>
                                    <td>
                                        @if($abandonedCart->name)
                                            <strong>{{ $abandonedCart->name }}</strong>
                                        @else
                                            <span class="text-muted">Not provided</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Phone</th>
                                    <td>
                                        @if($abandonedCart->phone)
                                            <a href="tel:{{ $abandonedCart->phone }}" class="btn btn-sm btn-success">
                                                <i class="fas fa-phone me-1"></i>{{ $abandonedCart->phone }}
                                            </a>
                                        @else
                                            <span class="text-muted">Not provided</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Email</th>
                                    <td>
                                        @if($abandonedCart->email)
                                            <a href="mailto:{{ $abandonedCart->email }}">{{ $abandonedCart->email }}</a>
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

                                            $variantLabel = $variantName !== ''
                                                ? $variantName
                                                : ($variantAttributes !== ''
                                                    ? $variantAttributes
                                                    : ($variantId ? ('Variant #' . $variantId) : ''));
                                        @endphp
                                        <div class="d-flex align-items-center">
                                            @if(!empty($item['product_image']))
                                            <img src="{{ $item['product_image'] }}" alt="" class="rounded me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                            @endif
                                            <div>
                                                <strong>{{ $item['product_name'] ?? 'Unknown Product' }}</strong>
                                                @if(!empty($item['product_sku']))
                                                <br><small class="text-muted">SKU: {{ $item['product_sku'] }}</small>
                                                @endif

                                                @if($variantAttributes !== '')
                                                    <br><small class="text-muted">{{ $variantAttributes }}</small>
                                                @elseif($variantLabel !== '')
                                                    <br><small class="text-muted">{{ $variantLabel }}</small>
                                                @endif

                                                @if($variantSku !== '')
                                                    <br><small class="text-muted">Variant SKU: {{ $variantSku }}</small>
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
@endsection
