@extends('admin.layouts.app')

@section('title', 'Abandoned Carts')

@push('styles')
<style>
    .abandoned-carts-page .abandoned-stat-card .card-body {
        padding: 0.75rem 0.85rem;
    }

    .abandoned-carts-page .abandoned-stat-card h6 {
        font-size: 0.84rem;
        font-weight: 600;
        margin-bottom: 0.18rem;
        letter-spacing: 0.01em;
    }

    .abandoned-carts-page .abandoned-stat-card .stat-value {
        font-size: 1.55rem;
        line-height: 1.05;
        margin-bottom: 0;
        font-weight: 700;
    }

    .abandoned-carts-page .abandoned-stat-card small {
        font-size: 0.74rem;
    }

    .abandoned-carts-page .abandoned-secondary-stat .card-body {
        padding: 0.65rem 0.8rem;
    }

    .abandoned-carts-page .abandoned-secondary-stat h6 {
        font-size: 0.84rem;
        margin-bottom: 0.2rem;
    }

    .abandoned-carts-page .abandoned-secondary-stat h2 {
        font-size: 2rem;
        line-height: 1;
        margin-bottom: 0.08rem;
    }

    .abandoned-carts-page .abandoned-filter-card .card-body {
        padding: 0.85rem;
    }

    .abandoned-carts-page .abandoned-filter-card .form-label {
        font-size: 0.72rem;
        margin-bottom: 0.24rem;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        font-weight: 600;
    }

    .abandoned-carts-page .abandoned-filter-card .form-control,
    .abandoned-carts-page .abandoned-filter-card .form-select {
        min-height: calc(1.5em + 0.5rem + 2px);
    }

    .abandoned-carts-page .abandoned-bulk-actions .card-body {
        padding: 0.65rem 0.85rem;
    }

    .abandoned-carts-page .abandoned-bulk-actions .btn {
        min-width: 150px;
        font-weight: 600;
    }

    .abandoned-carts-page .abandoned-carts-table thead th {
        font-size: 0.74rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #5f6f82;
        font-weight: 600;
        white-space: nowrap;
    }

    .abandoned-carts-page .abandoned-carts-table tbody td {
        font-size: 0.95rem;
        padding: 0.6rem 0.65rem;
        vertical-align: middle;
    }

    .abandoned-carts-page .abandoned-carts-table .contact-user-badge {
        font-size: 0.72rem;
        font-weight: 600;
        padding: 0.3rem 0.48rem;
    }

    .abandoned-carts-page .abandoned-action-stack {
        display: flex;
        flex-wrap: wrap;
        gap: 0.38rem;
    }

    .abandoned-carts-page .abandoned-action-btn {
        min-width: 92px;
        font-size: 0.74rem;
        font-weight: 600;
        padding: 0.24rem 0.5rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.32rem;
    }

    .abandoned-carts-page .abandoned-action-btn i {
        font-size: 0.76rem;
    }

    @media (max-width: 991.98px) {
        .abandoned-carts-page .abandoned-action-btn {
            min-width: 82px;
        }

        .abandoned-carts-page .abandoned-bulk-actions .btn {
            min-width: 120px;
        }
    }
</style>
@endpush

@section('content')
<div class="container-fluid abandoned-carts-page">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-1">Abandoned Carts</h1>
            <p class="text-muted mb-0">Track and recover incomplete checkouts</p>
        </div>
        <a href="{{ route('admin.abandoned-carts.export', request()->query()) }}" class="btn btn-sm btn-outline-secondary" data-no-admin-ajax="1">
            <i class="fas fa-download me-1"></i> Export CSV
        </a>
    </div>

    <!-- Stats Cards -->
    <div class="row g-2 mb-2">
        <div class="col-md-3 col-sm-6">
            <div class="card bg-warning bg-opacity-10 border-warning abandoned-stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-warning mb-1">Open Carts</h6>
                            <h3 class="stat-value">{{ number_format($stats['open']) }}</h3>
                            <small class="text-muted">Pending + Follow Up</small>
                        </div>
                        <div class="text-warning opacity-50">
                            <i class="fas fa-clock fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card bg-info bg-opacity-10 border-info abandoned-stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-info mb-1">Overdue Follow Up</h6>
                            <h3 class="stat-value">{{ number_format($stats['overdue_follow_up']) }}</h3>
                            <small class="text-muted">Need immediate action</small>
                        </div>
                        <div class="text-info opacity-50">
                            <i class="fas fa-phone fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card bg-success bg-opacity-10 border-success abandoned-stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-success mb-1">Recovered (30d)</h6>
                            <h3 class="stat-value">৳{{ number_format($stats['recovered_revenue_30d'], 0) }}</h3>
                            <small class="text-muted">Revenue won back</small>
                        </div>
                        <div class="text-success opacity-50">
                            <i class="fas fa-check-circle fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card bg-primary bg-opacity-10 border-primary abandoned-stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-primary mb-1">Potential Revenue</h6>
                            <h3 class="stat-value">৳{{ number_format($stats['potential_revenue'], 0) }}</h3>
                            <small class="text-muted">Avg ৳{{ number_format($stats['avg_open_value'], 0) }}/cart</small>
                        </div>
                        <div class="text-primary opacity-50">
                            <i class="fas fa-coins fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Stats -->
    <div class="row g-2 mb-3">
        <div class="col-md-3">
            <div class="card abandoned-secondary-stat">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-2">Recovery Rate (30 days)</h6>
                    <h2 class="mb-0 {{ $stats['recovery_rate'] >= 10 ? 'text-success' : 'text-warning' }}">
                        {{ $stats['recovery_rate'] }}%
                    </h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card abandoned-secondary-stat">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-2">With Contact Info</h6>
                    <h2 class="mb-0 text-info">{{ number_format($stats['with_contact']) }}</h2>
                    <small class="text-muted">{{ $stats['contactable_rate'] }}% of open leads</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card abandoned-secondary-stat">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-2">High Value Open</h6>
                    <h2 class="mb-0 text-danger">{{ number_format($stats['high_value_open']) }}</h2>
                    <small class="text-muted">Over ৳5,000 carts</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card abandoned-secondary-stat">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-2">Reminder Queue</h6>
                    <h2 class="mb-0 text-primary">{{ number_format($stats['reminder_due']) }}</h2>
                    <small class="text-muted">Ready for reminder send</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-3 abandoned-filter-card">
        <div class="card-body">
            <form method="GET" class="row g-2">
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="follow_up" {{ request('status') == 'follow_up' ? 'selected' : '' }}>Follow Up</option>
                        <option value="recovered" {{ request('status') == 'recovered' ? 'selected' : '' }}>Recovered</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Checkout Step</label>
                    <select name="checkout_step" class="form-select form-select-sm">
                        <option value="">All Steps</option>
                        <option value="cart" {{ request('checkout_step') == 'cart' ? 'selected' : '' }}>Cart Page</option>
                        <option value="shipping" {{ request('checkout_step') == 'shipping' ? 'selected' : '' }}>Shipping Info</option>
                        <option value="payment" {{ request('checkout_step') == 'payment' ? 'selected' : '' }}>Payment</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Has Contact</label>
                    <select name="has_contact" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="yes" {{ request('has_contact') == 'yes' ? 'selected' : '' }}>With Contact</option>
                        <option value="no" {{ request('has_contact') == 'no' ? 'selected' : '' }}>No Contact</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Min Value</label>
                    <input type="number" name="min_value" class="form-control form-control-sm" placeholder="৳0" value="{{ request('min_value') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Email, Phone, Name" value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Priority</label>
                    <select name="priority" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="high_value" {{ request('priority') == 'high_value' ? 'selected' : '' }}>High Value</option>
                        <option value="overdue_follow_up" {{ request('priority') == 'overdue_follow_up' ? 'selected' : '' }}>Overdue Follow Up</option>
                        <option value="reminder_due" {{ request('priority') == 'reminder_due' ? 'selected' : '' }}>Reminder Due</option>
                        <option value="actionable" {{ request('priority') == 'actionable' ? 'selected' : '' }}>Actionable Leads</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Sort</label>
                    <select name="sort_by" class="form-select form-select-sm">
                        <option value="">Latest</option>
                        <option value="oldest" {{ request('sort_by') == 'oldest' ? 'selected' : '' }}>Oldest First</option>
                        <option value="highest_value" {{ request('sort_by') == 'highest_value' ? 'selected' : '' }}>Highest Value</option>
                        <option value="oldest_activity" {{ request('sort_by') == 'oldest_activity' ? 'selected' : '' }}>Oldest Activity</option>
                        <option value="latest_activity" {{ request('sort_by') == 'latest_activity' ? 'selected' : '' }}>Latest Activity</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    <a href="{{ route('admin.abandoned-carts.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Bulk Actions Form -->
    <form id="bulkForm" action="{{ route('admin.abandoned-carts.bulk-action') }}" method="POST">
        @csrf
        
        <!-- Bulk Actions Bar -->
        <div class="card mb-3 abandoned-bulk-actions" id="bulkActionsBar" style="display: none;">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <span class="text-muted small fw-semibold"><span id="selectedCount">0</span> selected</span>
                    <button type="submit" name="action" value="follow_up" class="btn btn-sm btn-outline-info">
                        <i class="fas fa-phone me-1"></i> Mark Follow Up
                    </button>
                    <button type="submit" name="action" value="recovered" class="btn btn-sm btn-outline-success">
                        <i class="fas fa-check-circle me-1"></i> Recover + Create Order
                    </button>
                    <button type="submit" name="action" value="cancelled" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-ban me-1"></i> Cancel
                    </button>
                    <button type="submit" name="action" value="delete" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete selected abandoned carts?')">
                        <i class="fas fa-trash me-1"></i> Delete
                    </button>
                </div>
            </div>
        </div>

        <!-- Abandoned Carts Table -->
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0 abandoned-carts-table">
                    <thead class="table-light">
                        <tr>
                            <th width="40">
                                <input type="checkbox" class="form-check-input" id="selectAll">
                            </th>
                            <th>Contact Info</th>
                            <th>Cart Summary</th>
                            <th>Step</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Time</th>
                            <th>Priority</th>
                            <th width="260">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($abandonedCarts as $cart)
                        <tr>
                            <td>
                                <input type="checkbox" class="form-check-input cart-checkbox" name="ids[]" value="{{ $cart->id }}">
                            </td>
                            <td>
                                <div>
                                    @if($cart->name)
                                        <strong>{{ $cart->name }}</strong><br>
                                    @endif
                                    @if($cart->phone)
                                        <a href="tel:{{ $cart->phone }}" class="text-decoration-none">
                                            <i class="fas fa-phone text-success me-1"></i>{{ $cart->phone }}
                                        </a><br>
                                    @endif
                                    @if($cart->email)
                                        <small class="text-muted">
                                            <i class="fas fa-envelope me-1"></i>{{ $cart->email }}
                                        </small>
                                    @endif
                                    @if(!$cart->phone && !$cart->email)
                                        <span class="text-muted">No contact info</span>
                                    @endif
                                </div>
                                @if($cart->user)
                                    <small class="badge bg-light text-dark contact-user-badge mt-1">
                                        <i class="fas fa-user me-1"></i>{{ $cart->user->name }}
                                    </small>
                                @endif
                            </td>
                            <td>
                                <span class="fw-semibold">{{ $cart->item_count }} items</span>
                                @if($cart->cart_items && count($cart->cart_items) > 0)
                                    @php
                                        $cartItemPreview = collect($cart->cart_items)
                                            ->map(function ($item) {
                                                $productName = trim((string) ($item['product_name'] ?? 'Unknown Product'));
                                                $variantName = trim((string) ($item['variant_name'] ?? ''));
                                                $variantAttributes = trim((string) ($item['variant_attributes'] ?? ''));
                                                $variantId = !empty($item['variant_id']) ? (int) $item['variant_id'] : null;

                                                $variantLabel = $variantName !== ''
                                                    ? $variantName
                                                    : ($variantAttributes !== ''
                                                        ? $variantAttributes
                                                        : ($variantId ? ('Variant #' . $variantId) : ''));

                                                return $variantLabel !== ''
                                                    ? "{$productName} ({$variantLabel})"
                                                    : $productName;
                                            })
                                            ->implode(', ');
                                    @endphp
                                    <br>
                                    <small class="text-muted">
                                        {{ \Illuminate\Support\Str::limit($cartItemPreview, 70) }}
                                    </small>
                                @endif
                                @if($cart->coupon_code)
                                    <br><span class="badge bg-success">{{ $cart->coupon_code }}</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $stepColors = [
                                        'cart' => 'secondary',
                                        'shipping' => 'warning',
                                        'payment' => 'info',
                                    ];
                                @endphp
                                <span class="badge bg-{{ $stepColors[$cart->checkout_step] ?? 'secondary' }}">
                                    {{ $cart->checkout_step_label }}
                                </span>
                            </td>
                            <td>
                                <strong>৳{{ number_format($cart->total, 0) }}</strong>
                                @if($cart->discount_amount > 0)
                                    <br><small class="text-success">-৳{{ number_format($cart->discount_amount, 0) }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-{{ $cart->status_color }}">
                                    {{ $cart->status_label }}
                                </span>
                                @if($cart->followed_up_at)
                                    <br><small class="text-muted">{{ $cart->followed_up_at->format('M d') }}</small>
                                @endif
                            </td>
                            <td>
                                <small>
                                    {{ $cart->created_at->format('M d, H:i') }}<br>
                                    <span class="text-muted">{{ $cart->time_since_abandoned }}</span>
                                </small>
                            </td>
                            <td>
                                <span class="badge bg-{{ $cart->priority_color }}">
                                    {{ ucfirst($cart->priority_level) }}
                                </span>
                                @if($cart->reminder_count > 0)
                                    <br><small class="text-muted">{{ $cart->reminder_count }} reminder(s)</small>
                                @endif
                            </td>
                            <td>
                                <div class="abandoned-action-stack">
                                    <a href="{{ route('admin.abandoned-carts.show', $cart) }}" class="btn btn-outline-primary btn-sm abandoned-action-btn" title="View Details">
                                        <i class="fas fa-eye"></i>
                                        <span>View</span>
                                    </a>

                                    @if(in_array($cart->status, ['pending', 'follow_up'], true))
                                    <form action="{{ route('admin.abandoned-carts.mark-recovered', $cart) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-success btn-sm abandoned-action-btn" title="Recover and create order">
                                            <i class="fas fa-check-circle"></i>
                                            <span>Recover</span>
                                        </button>
                                    </form>
                                    @endif

                                    @if($cart->status === 'pending')
                                    <form action="{{ route('admin.abandoned-carts.mark-follow-up', $cart) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-info btn-sm abandoned-action-btn" title="Mark Follow Up">
                                            <i class="fas fa-phone"></i>
                                            <span>Follow Up</span>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-5">
                                <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                                <p class="text-muted mb-0">No abandoned carts found</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($abandonedCarts->hasPages())
            <div class="card-footer">
                {{ $abandonedCarts->links() }}
            </div>
            @endif
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.cart-checkbox');
    const bulkActionsBar = document.getElementById('bulkActionsBar');
    const selectedCount = document.getElementById('selectedCount');

    function updateBulkBar() {
        const checked = document.querySelectorAll('.cart-checkbox:checked').length;
        selectedCount.textContent = checked;
        bulkActionsBar.style.display = checked > 0 ? 'block' : 'none';
    }

    selectAll.addEventListener('change', function() {
        checkboxes.forEach(cb => cb.checked = this.checked);
        updateBulkBar();
    });

    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateBulkBar);
    });
});
</script>
@endpush
@endsection
