@extends('admin.layouts.app')

@section('title', 'Payments')
@section('page-title', 'Payments')

@section('content')
<div class="card">
    <div class="card-header">
        All Payments
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Order</th>
                        <th>Customer</th>
                        <th>Method</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                        <tr>
                            @php
                                $displayOrderNumber = $payment->order?->order_number ?? $payment->order_id;
                            @endphp
                            <td>#{{ $payment->id }}</td>
                            <td>
                                <a href="{{ route('admin.orders.show', $payment->order_id) }}">
                                    Order #{{ $displayOrderNumber }}
                                </a>
                            </td>
                            <td>{{ $payment->order?->user?->name ?? 'N/A' }}</td>
                            <td>{{ ucfirst($payment->payment_method) }}</td>
                            <td><strong>৳{{ number_format($payment->amount, 2) }}</strong></td>
                            <td>
                                <span class="badge badge-status-{{ $payment->status }}">
                                    {{ ucfirst($payment->status) }}
                                </span>
                            </td>
                            <td>{{ $payment->created_at->format('M d, Y H:i') }}</td>
                            <td>
                                <a href="{{ route('admin.payments.show', $payment) }}" class="btn btn-sm btn-outline-info">
                                    <i class="bi bi-eye"></i> View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted">No payments found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('admin.partials.pagination', ['paginator' => $payments])
    </div>
</div>
@endsection
