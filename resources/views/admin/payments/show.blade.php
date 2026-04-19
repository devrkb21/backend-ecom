@extends('admin.layouts.app')

@section('title', 'Payment Details')
@section('page-title', 'Payment Details')

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>
                    <a href="{{ route('admin.payments.index') }}" class="btn btn-sm btn-outline-secondary me-2">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                    Payment #{{ $payment->id }}
                </span>
                <span class="badge badge-status-{{ $payment->status }} fs-6">
                    {{ ucfirst($payment->status) }}
                </span>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr>
                        <th style="width: 200px;">Payment ID</th>
                        <td>#{{ $payment->id }}</td>
                    </tr>
                    <tr>
                        <th>Order</th>
                        <td>
                            <a href="{{ route('admin.orders.show', $payment->order_id) }}">
                                Order #{{ $payment->order?->order_number ?? $payment->order_id }}
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <th>Customer</th>
                        <td>
                            @if($payment->order && $payment->order->user)
                                <a href="{{ route('admin.users.show', $payment->order->user_id) }}">
                                    {{ $payment->order->user->name }}
                                </a>
                            @else
                                N/A
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Payment Method</th>
                        <td>{{ ucfirst($payment->payment_method) }}</td>
                    </tr>
                    <tr>
                        <th>Amount</th>
                        <td><strong class="fs-5">৳{{ number_format($payment->amount, 2) }}</strong></td>
                    </tr>
                    <tr>
                        <th>Transaction ID</th>
                        <td><code>{{ $payment->transaction_id ?? 'N/A' }}</code></td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            <span class="badge badge-status-{{ $payment->status }}">
                                {{ ucfirst($payment->status) }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>Created At</th>
                        <td>{{ $payment->created_at->format('M d, Y H:i:s') }}</td>
                    </tr>
                    <tr>
                        <th>Updated At</th>
                        <td>{{ $payment->updated_at->format('M d, Y H:i:s') }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
