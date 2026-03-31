@extends('admin.layouts.app')

@section('title', 'Order Tracking - #' . $order->order_number)

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Order Tracking</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.orders.index') }}">Orders</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.orders.show', $order) }}">#{{ $order->order_number }}</a></li>
                    <li class="breadcrumb-item active">Tracking</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Order
        </a>
    </div>

    <div class="row">
        <!-- Tracking Form -->
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-shipping-fast me-2"></i>Shipping Information
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.orders.tracking.update', $order) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label">Tracking Number <span class="text-danger">*</span></label>
                            <input type="text" name="tracking_number" class="form-control @error('tracking_number') is-invalid @enderror"
                                value="{{ old('tracking_number', $order->tracking_number) }}"
                                placeholder="Enter tracking number">
                            @error('tracking_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Carrier <span class="text-danger">*</span></label>
                            <select name="carrier" class="form-select @error('carrier') is-invalid @enderror">
                                <option value="">Select Carrier</option>
                                @foreach($carriers as $code => $name)
                                    <option value="{{ $code }}" {{ old('carrier', $order->carrier) == $code ? 'selected' : '' }}>
                                        {{ $name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('carrier')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Custom Tracking URL</label>
                            <input type="url" name="carrier_tracking_url" class="form-control @error('carrier_tracking_url') is-invalid @enderror"
                                value="{{ old('carrier_tracking_url', $order->carrier_tracking_url) }}"
                                placeholder="Leave blank to auto-generate">
                            <small class="text-muted">Leave blank to auto-generate based on carrier</small>
                            @error('carrier_tracking_url')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Estimated Delivery Date</label>
                            <input type="date" name="estimated_delivery_at" class="form-control @error('estimated_delivery_at') is-invalid @enderror"
                                value="{{ old('estimated_delivery_at', $order->estimated_delivery_at?->format('Y-m-d')) }}">
                            @error('estimated_delivery_at')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> {{ $order->tracking_number ? 'Update' : 'Save' }} Tracking Info
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Quick Actions -->
            @if($order->tracking_number && $order->status !== 'delivered')
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-bolt me-2"></i>Quick Actions
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.orders.mark-delivered', $order) }}" method="POST" class="mb-3">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Delivery Notes (Optional)</label>
                            <textarea name="delivery_notes" class="form-control" rows="2" placeholder="e.g., Delivered to reception"></textarea>
                        </div>
                        <button type="submit" class="btn btn-success" onclick="return confirm('Mark this order as delivered?')">
                            <i class="fas fa-check-circle me-1"></i> Mark as Delivered
                        </button>
                    </form>
                </div>
            </div>
            @endif

            <!-- Add Tracking Event -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-plus-circle me-2"></i>Add Tracking Event
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.orders.tracking.add-event', $order) }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                                <option value="">Select Status</option>
                                <option value="order_placed">Order Placed</option>
                                <option value="payment_confirmed">Payment Confirmed</option>
                                <option value="processing">Processing</option>
                                <option value="packed">Packed</option>
                                <option value="shipped">Shipped</option>
                                <option value="in_transit">In Transit</option>
                                <option value="out_for_delivery">Out for Delivery</option>
                                <option value="delivered">Delivered</option>
                                <option value="returned">Returned</option>
                                <option value="refunded">Refunded</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control @error('description') is-invalid @enderror"
                                rows="2" placeholder="e.g., Package picked up from warehouse"></textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Location</label>
                                <input type="text" name="location" class="form-control @error('location') is-invalid @enderror"
                                    placeholder="e.g., Dhaka Hub">
                                @error('location')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Occurred At</label>
                                <input type="datetime-local" name="occurred_at" class="form-control @error('occurred_at') is-invalid @enderror"
                                    value="{{ now()->format('Y-m-d\TH:i') }}">
                                @error('occurred_at')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <button type="submit" class="btn btn-outline-primary">
                            <i class="fas fa-plus me-1"></i> Add Event
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Tracking History & Order Info -->
        <div class="col-lg-6">
            <!-- Current Status -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2"></i>Current Status
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="me-3">
                            @php
                                $statusColors = [
                                    'pending' => 'warning',
                                    'processing' => 'info',
                                    'shipped' => 'primary',
                                    'delivered' => 'success',
                                    'cancelled' => 'danger'
                                ];
                                $statusIcons = [
                                    'pending' => 'clock',
                                    'processing' => 'cog',
                                    'shipped' => 'truck',
                                    'delivered' => 'check-circle',
                                    'cancelled' => 'times-circle'
                                ];
                            @endphp
                            <span class="badge bg-{{ $statusColors[$order->status] ?? 'secondary' }} fs-6 px-3 py-2">
                                <i class="fas fa-{{ $statusIcons[$order->status] ?? 'question' }} me-1"></i>
                                {{ ucfirst($order->status) }}
                            </span>
                        </div>
                        <div>
                            <div class="progress" style="width: 200px; height: 10px;">
                                <div class="progress-bar bg-{{ $statusColors[$order->status] ?? 'secondary' }}" 
                                    style="width: {{ $order->tracking_progress }}%"></div>
                            </div>
                            <small class="text-muted">{{ $order->tracking_progress }}% complete</small>
                        </div>
                    </div>

                    @if($order->tracking_number)
                    <div class="alert alert-info mb-0">
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Tracking #:</strong> {{ $order->tracking_number }}
                            </div>
                            <div class="col-md-6">
                                <strong>Carrier:</strong> {{ ucfirst($order->carrier) }}
                            </div>
                        </div>
                        @if($order->carrier_tracking_url)
                        <div class="mt-2">
                            <a href="{{ $order->carrier_tracking_url }}" target="_blank" class="btn btn-sm btn-outline-info">
                                <i class="fas fa-external-link-alt me-1"></i> Track on {{ ucfirst($order->carrier) }}
                            </a>
                        </div>
                        @endif
                    </div>
                    @else
                    <div class="alert alert-warning mb-0">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        No tracking information added yet.
                    </div>
                    @endif
                </div>
            </div>

            <!-- Shipping Details -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-map-marker-alt me-2"></i>Shipping Details
                    </h5>
                </div>
                <div class="card-body">
                    <address class="mb-0">
                        <strong>{{ $order->shipping_name }}</strong><br>
                        {{ $order->shipping_address }}<br>
                        {{ $order->shipping_city }}@if($order->shipping_state), {{ $order->shipping_state }}@endif {{ $order->shipping_zip }}<br>
                        {{ $order->shipping_country }}<br>
                        @if($order->shipping_phone)
                        <i class="fas fa-phone me-1"></i> {{ $order->shipping_phone }}<br>
                        @endif
                        <i class="fas fa-envelope me-1"></i> {{ $order->shipping_email }}
                    </address>
                </div>
            </div>

            <!-- Tracking History Timeline -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-history me-2"></i>Tracking History
                    </h5>
                    <span class="badge bg-secondary">{{ $order->trackingHistory->count() }} events</span>
                </div>
                <div class="card-body">
                    @if($order->trackingHistory->count() > 0)
                    <div class="timeline">
                        @foreach($order->trackingHistory as $event)
                        <div class="timeline-item pb-3 {{ !$loop->last ? 'border-start border-2 ms-2 ps-4' : 'ms-2 ps-4' }}">
                            <div class="timeline-marker position-absolute" style="left: -8px; top: 0;">
                                <span class="badge rounded-pill bg-primary">{{ $event->status_icon }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong>{{ $event->status_label }}</strong>
                                    @if($event->location)
                                    <span class="text-muted">• {{ $event->location }}</span>
                                    @endif
                                    @if($event->description)
                                    <p class="mb-0 text-muted small">{{ $event->description }}</p>
                                    @endif
                                    <small class="text-muted">
                                        {{ $event->occurred_at->format('M d, Y h:i A') }}
                                    </small>
                                </div>
                                <form action="{{ route('admin.orders.tracking.delete-event', [$order, $event->id]) }}" 
                                    method="POST" class="ms-2" onsubmit="return confirm('Delete this event?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-history fa-3x mb-3 opacity-50"></i>
                        <p class="mb-0">No tracking events yet.</p>
                        <small>Add tracking info to start recording events.</small>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
}
.timeline-item {
    position: relative;
}
.timeline-marker {
    z-index: 1;
}
</style>
@endsection
