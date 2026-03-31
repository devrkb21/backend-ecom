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
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-receipt me-2"></i>Order #{{ $order->id }}</h6>
                </div>
                <span class="badge badge-status-{{ $order->status }} fs-6">
                    {{ ucfirst($order->status) }}
                </span>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="text-muted small text-uppercase mb-2">Customer Information</h6>
                        <p class="mb-1"><strong>{{ $order->user->name }}</strong></p>
                        <p class="mb-1">{{ $order->user->email }}</p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted small text-uppercase mb-2">Shipping Address</h6>
                        <p class="mb-0">{{ $order->shipping_address ?? 'Not provided' }}</p>
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
                                        @if($item->product)
                                            <a href="{{ route('admin.products.show', $item->product_id) }}" class="text-decoration-none">
                                                {{ $item->product->name }}
                                            </a>
                                        @else
                                            <span class="text-muted">Product Deleted</span>
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
                            <option value="pending" {{ $order->status == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="processing" {{ $order->status == 'processing' ? 'selected' : '' }}>Processing</option>
                            <option value="shipped" {{ $order->status == 'shipped' ? 'selected' : '' }}>Shipped</option>
                            <option value="delivered" {{ $order->status == 'delivered' ? 'selected' : '' }}>Delivered</option>
                            <option value="cancelled" {{ $order->status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
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
    </div>
</div>
@endsection
