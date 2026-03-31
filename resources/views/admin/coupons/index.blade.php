@extends('admin.layouts.app')

@section('title', 'Coupons')
@section('page-title', 'Coupons')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-ticket-perforated me-2"></i>Manage Coupons</h5>
        <a href="{{ route('admin.coupons.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i> Create Coupon
        </a>
    </div>
    <div class="card-body">
        {{-- Filters --}}
        <form action="{{ route('admin.coupons.index') }}" method="GET" class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" name="search" value="{{ request('search') }}" placeholder="Search by code or name...">
                </div>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Expired</option>
                    <option value="scheduled" {{ request('status') == 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="type" class="form-select">
                    <option value="">All Types</option>
                    <option value="fixed" {{ request('type') == 'fixed' ? 'selected' : '' }}>Fixed Amount</option>
                    <option value="percentage" {{ request('type') == 'percentage' ? 'selected' : '' }}>Percentage</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-outline-primary w-100">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('admin.coupons.index') }}" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-x-lg me-1"></i> Clear
                </a>
            </div>
        </form>

        {{-- Stats --}}
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="border rounded p-3 text-center">
                    <div class="text-muted small">Total Coupons</div>
                    <div class="fs-4 fw-bold">{{ \App\Models\Coupon::count() }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="border rounded p-3 text-center">
                    <div class="text-muted small">Active</div>
                    <div class="fs-4 fw-bold text-success">{{ \App\Models\Coupon::active()->count() }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="border rounded p-3 text-center">
                    <div class="text-muted small">Total Used</div>
                    <div class="fs-4 fw-bold text-primary">{{ \App\Models\Coupon::sum('used_count') }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="border rounded p-3 text-center">
                    <div class="text-muted small">Total Discounts</div>
                    <div class="fs-4 fw-bold text-info">৳{{ number_format(\App\Models\CouponUsage::sum('discount_amount'), 2) }}</div>
                </div>
            </div>
        </div>

        {{-- Coupons Table --}}
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Discount</th>
                        <th>Usage</th>
                        <th>Valid Period</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($coupons as $coupon)
                        <tr>
                            <td>
                                <code class="fs-6 bg-light px-2 py-1 rounded">{{ $coupon->code }}</code>
                                @if($coupon->free_shipping)
                                    <span class="badge bg-info ms-1" title="Free Shipping"><i class="bi bi-truck"></i></span>
                                @endif
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $coupon->name }}</div>
                                @if($coupon->minimum_order_amount)
                                    <small class="text-muted">Min: ৳{{ number_format($coupon->minimum_order_amount) }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-{{ $coupon->type == 'percentage' ? 'warning' : 'success' }} text-dark">
                                    {{ $coupon->formatted_value }}
                                </span>
                                @if($coupon->type == 'percentage' && $coupon->maximum_discount)
                                    <br><small class="text-muted">Max: ৳{{ number_format($coupon->maximum_discount) }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="fw-semibold">{{ $coupon->used_count }}</span>
                                @if($coupon->usage_limit)
                                    <span class="text-muted">/ {{ $coupon->usage_limit }}</span>
                                @else
                                    <span class="text-muted">/ ∞</span>
                                @endif
                            </td>
                            <td>
                                @if($coupon->starts_at || $coupon->expires_at)
                                    <small>
                                        @if($coupon->starts_at)
                                            <i class="bi bi-calendar-event me-1"></i>{{ $coupon->starts_at->format('M d, Y') }}
                                        @endif
                                        @if($coupon->starts_at && $coupon->expires_at)
                                            <br>
                                        @endif
                                        @if($coupon->expires_at)
                                            <i class="bi bi-calendar-x me-1"></i>{{ $coupon->expires_at->format('M d, Y') }}
                                        @endif
                                    </small>
                                @else
                                    <span class="text-muted small">No limit</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $statusColors = [
                                        'active' => 'success',
                                        'inactive' => 'secondary',
                                        'expired' => 'danger',
                                        'scheduled' => 'info',
                                        'exhausted' => 'warning',
                                    ];
                                @endphp
                                <span class="badge bg-{{ $statusColors[$coupon->status] ?? 'secondary' }}">
                                    {{ ucfirst($coupon->status) }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('admin.coupons.show', $coupon) }}" class="btn btn-outline-info" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.coupons.edit', $coupon) }}" class="btn btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('admin.coupons.toggle-status', $coupon) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-outline-{{ $coupon->is_active ? 'warning' : 'success' }}" title="{{ $coupon->is_active ? 'Deactivate' : 'Activate' }}">
                                            <i class="bi bi-{{ $coupon->is_active ? 'pause' : 'play' }}"></i>
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.coupons.duplicate', $coupon) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-secondary" title="Duplicate">
                                            <i class="bi bi-copy"></i>
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.coupons.destroy', $coupon) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this coupon?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-ticket-perforated" style="font-size: 3rem;"></i>
                                <p class="mt-2 mb-0">No coupons found</p>
                                <a href="{{ route('admin.coupons.create') }}" class="btn btn-primary btn-sm mt-3">
                                    <i class="bi bi-plus-lg me-1"></i> Create First Coupon
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="d-flex justify-content-center mt-4">
            {{ $coupons->links() }}
        </div>
    </div>
</div>
@endsection
