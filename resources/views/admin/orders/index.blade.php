@extends('admin.layouts.app')

@section('title', 'Orders')
@section('page-title', 'Orders')

@section('content')
@php
    $activeView = $view ?? 'all';
@endphp

<div class="mb-3 small fw-semibold">
    <a href="{{ route('admin.orders.index') }}" class="text-decoration-none {{ $activeView === 'all' ? 'text-primary' : 'text-muted' }}">
        All ({{ $filterCounts['all'] ?? 0 }})
    </a>

    @foreach($statuses as $statusTab)
        <span class="mx-1 text-muted">|</span>
        <a
            href="{{ route('admin.orders.index', ['view' => $statusTab->key]) }}"
            class="text-decoration-none {{ $activeView === $statusTab->key ? 'text-primary' : 'text-muted' }}"
        >
            {{ $statusTab->label }} ({{ $filterCounts[$statusTab->key] ?? 0 }})
        </a>
    @endforeach

    <span class="mx-1 text-muted">|</span>
    <a href="{{ route('admin.orders.index', ['view' => 'trash']) }}" class="text-decoration-none {{ $activeView === 'trash' ? 'text-primary' : 'text-muted' }}">
        Trash ({{ $filterCounts['trash'] ?? 0 }})
    </a>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-receipt me-2"></i>Order Management ({{ $orders->total() }})</h6>
        @if($activeView === 'trash')
            <span class="badge bg-secondary">Trash View</span>
        @endif
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
                        @else
                            @foreach($bulkStatuses as $statusOption)
                                <option value="{{ $statusOption->key }}">Mark as {{ $statusOption->label }}</option>
                            @endforeach
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

        <div class="card-body p-0">
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
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                            <tr>
                                <td class="text-center">
                                    <input type="checkbox" class="form-check-input order-checkbox" name="order_ids[]" value="{{ $order->id }}" aria-label="Select order {{ $order->id }}">
                                </td>
                                <td><strong>#{{ $order->id }}</strong></td>
                                <td>
                                    @php
                                        $customerName = $order->user?->name ?? $order->shipping_name ?? 'Guest Checkout';
                                    @endphp
                                    @if($order->user)
                                        <a href="{{ route('admin.users.show', $order->user_id) }}" class="text-decoration-none">
                                            {{ $customerName }}
                                        </a>
                                    @else
                                        <span class="text-muted">{{ $customerName }}</span>
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
                                    @php
                                        $statusLabel = $order->statusConfig?->label ?? ucfirst(str_replace('_', ' ', $order->status));
                                        $statusColor = $order->statusConfig?->color ?? '#6C757D';
                                    @endphp
                                    <span class="badge text-white" style="background-color: {{ $statusColor }};">
                                        {{ $statusLabel }}
                                    </span>
                                </td>
                                <td>{{ $order->created_at->format('M d, Y H:i') }}</td>
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

    @if($orders->hasPages())
        <div class="card-footer">
            {{ $orders->links() }}
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
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

    form.addEventListener('submit', function (event) {
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

        const confirmationMessage = actionSelect.value === 'trash'
            ? 'Are you sure you want to move selected orders to trash?'
            : 'Are you sure you want to apply this bulk action?';

        if (!window.confirm(confirmationMessage)) {
            event.preventDefault();
        }
    });

    syncSelectionState();
});
</script>
@endpush
