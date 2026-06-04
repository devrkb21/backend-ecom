@extends('admin.layouts.app')

@section('title', 'Customer Profile')
@section('page-title', 'Customer Profile')

@section('content')
<div class="row">
    <!-- Sidebar / Profile Card -->
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body text-center mt-4">
                <div class="avatar bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3 shadow" style="width: 80px; height: 80px; font-size: 2rem;">
                    {{ strtoupper(substr($customer->name, 0, 1)) }}
                </div>
                <h4 class="mb-1 fw-bold">{{ $customer->name }}</h4>
                <p class="text-muted mb-2">{{ $customer->email ?? 'No email provided' }}</p>
                <p class="text-dark mb-3"><i class="bi bi-telephone-fill text-muted me-2"></i>{{ $customer->phone }}</p>

                @if($customer->group)
                    <div class="badge bg-success fs-6 py-2 px-3 rounded-pill mb-4 shadow-sm">
                        <i class="bi bi-award-fill me-1"></i> {{ $customer->group->name }} Member
                    </div>
                @else
                    <div class="badge bg-light text-dark fs-6 py-2 px-3 rounded-pill border mb-4">
                        Regular Customer
                    </div>
                @endif

                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editCustomerModal">
                        <i class="bi bi-pencil me-1"></i> Edit Details
                    </button>
                    <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#assignGroupModal">
                        <i class="bi bi-award me-1"></i> Assign Group
                    </button>
                </div>
            </div>
            
            <div class="card-footer bg-white border-top-0 pt-0 pb-4">
                <hr>
                <div class="row text-center">
                    <div class="col-6 border-end">
                        <h6 class="text-muted mb-1 small text-uppercase">Total Spent</h6>
                        <h5 class="mb-0 fw-bold">৳{{ number_format($customer->total_spent, 2) }}</h5>
                    </div>
                    <div class="col-6">
                        <h6 class="text-muted mb-1 small text-uppercase">Total Orders</h6>
                        <h5 class="mb-0 fw-bold">{{ $customer->total_orders }}</h5>
                    </div>
                </div>
                <div class="mt-4 text-center">
                    <p class="text-muted small mb-0">Customer Since: {{ $customer->first_order_date->format('M d, Y') }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="col-md-8">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-bottom pt-3 pb-0">
                <ul class="nav nav-tabs card-header-tabs" id="customerTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold text-dark" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button" role="tab" aria-controls="history" aria-selected="true">
                            <i class="bi bi-clock-history me-1"></i> Order History
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold text-dark" id="addresses-tab" data-bs-toggle="tab" data-bs-target="#addresses" type="button" role="tab" aria-controls="addresses" aria-selected="false">
                            <i class="bi bi-geo-alt me-1"></i> Saved Addresses
                        </button>
                    </li>
                </ul>
            </div>
            <div class="card-body p-0">
                <div class="tab-content" id="customerTabsContent">
                    <!-- Order History Tab -->
                    <div class="tab-pane fade show active" id="history" role="tabpanel" aria-labelledby="history-tab">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Order ID</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Items</th>
                                        <th class="text-end pe-3">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($orders as $order)
                                        <tr>
                                            <td class="ps-3">
                                                <a href="{{ route('admin.orders.show', $order) }}" class="fw-bold">#{{ $order->order_number ?? $order->id }}</a>
                                            </td>
                                            <td>{{ $order->created_at->format('M d, Y') }}</td>
                                            <td>
                                                @php
                                                    $statusLabel = $order->statusConfig?->label ?? ucfirst(str_replace('_', ' ', $order->status));
                                                    $statusColor = $order->statusConfig?->color ?? '#6C757D';
                                                @endphp
                                                <span class="badge" style="background-color: {{ $statusColor }};">{{ $statusLabel }}</span>
                                            </td>
                                            <td>{{ $order->items->sum('quantity') }} items</td>
                                            <td class="text-end pe-3 fw-bold">৳{{ number_format($order->total, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted">No orders found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Addresses Tab -->
                    <div class="tab-pane fade p-3" id="addresses" role="tabpanel" aria-labelledby="addresses-tab">
                        @if(empty($customer->addresses))
                            <div class="text-center py-5 text-muted">
                                <i class="bi bi-house-slash fs-1 d-block mb-2"></i>
                                No addresses found for this customer.
                            </div>
                        @else
                            <div class="row g-3">
                                @foreach($customer->addresses as $addr)
                                    <div class="col-md-6">
                                        <div class="card border h-100 shadow-sm">
                                            <div class="card-body">
                                                <div class="d-flex align-items-start mb-2">
                                                    <i class="bi bi-geo-alt-fill text-primary mt-1 me-2"></i>
                                                    <div>
                                                        <h6 class="mb-1 fw-bold">{{ $addr['city'] ?? 'City Not Provided' }}</h6>
                                                        <p class="mb-0 text-muted small">{{ $addr['address'] }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="card-footer bg-light border-0 py-2">
                                                <small class="text-muted">Used {{ $addr['times_used'] }} time(s). Last used: {{ \Carbon\Carbon::parse($addr['last_used'])->format('M d, Y') }}</small>
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
    </div>
</div>

<!-- Edit Customer Modal -->
<div class="modal fade" id="editCustomerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('admin.customers.update', $customer->phone) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header border-bottom-0 bg-light">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil me-2 text-primary"></i>Edit Customer Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-0">
                    <div class="alert alert-warning py-2 small mt-3">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> Updating these details will apply to all historical orders placed with phone number <strong>{{ $customer->phone }}</strong>.
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Phone Number <span class="text-danger">*</span></label>
                        <input type="text" name="phone" class="form-control form-control-lg" value="{{ $customer->phone }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-lg" value="{{ $customer->name }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email</label>
                        <input type="email" name="email" class="form-control form-control-lg" value="{{ $customer->email }}">
                    </div>
                </div>
                <div class="modal-footer border-top-0 bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Assign Group Modal -->
<div class="modal fade" id="assignGroupModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('admin.customers.assign-group', $customer->phone) }}" method="POST">
                @csrf
                <div class="modal-header border-bottom-0 bg-light">
                    <h5 class="modal-title fw-bold"><i class="bi bi-award me-2 text-success"></i>Assign Loyalty Group</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-0">
                    <p class="mb-3 mt-3 text-muted">Assign <strong>{{ $customer->name }} ({{ $customer->phone }})</strong> to a loyalty group manually. Manual assignment overrides automatic order thresholds.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Select Group</label>
                        <select name="group_id" class="form-select form-select-lg">
                            <option value="">-- Auto Assign (Based on Orders/Spent) --</option>
                            @foreach($groups as $g)
                                <option value="{{ $g->id }}" {{ $customer->group && $customer->group->id == $g->id ? 'selected' : '' }}>
                                    {{ $g->name }} ({{ $g->discount_percentage }}% Off)
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-top-0 bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success px-4">Assign Group</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
