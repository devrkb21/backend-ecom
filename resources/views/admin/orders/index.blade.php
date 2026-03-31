@extends('admin.layouts.app')

@section('title', 'Orders')
@section('page-title', 'Orders')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-receipt me-2"></i>All Orders ({{ $orders->total() }})</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
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
                            <td><strong>#{{ $order->id }}</strong></td>
                            <td>
                                <a href="{{ route('admin.users.show', $order->user_id) }}" class="text-decoration-none">
                                    {{ $order->user->name }}
                                </a>
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
                                <span class="badge badge-status-{{ $order->status }}">
                                    {{ ucfirst($order->status) }}
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
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                No orders found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($orders->hasPages())
        <div class="card-footer">
            {{ $orders->links() }}
        </div>
    @endif
</div>
@endsection
