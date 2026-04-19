@extends('admin.layouts.app')

@section('title', 'User Details')
@section('page-title', 'User Details')

@section('content')
<div class="row g-3">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header d-flex align-items-center">
                <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-outline-secondary me-2">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <h6 class="mb-0 fw-semibold"><i class="bi bi-person me-2"></i>{{ $user->name }}</h6>
            </div>
            <div class="card-body">
                <table class="table table-borderless mb-0">
                    <tr>
                        <th class="text-muted small text-uppercase" style="width: 150px;">ID</th>
                        <td>{{ $user->id }}</td>
                    </tr>
                    <tr>
                        <th class="text-muted small text-uppercase">Name</th>
                        <td><strong>{{ $user->name }}</strong></td>
                    </tr>
                    <tr>
                        <th class="text-muted small text-uppercase">Email</th>
                        <td>{{ $user->email }}</td>
                    </tr>
                    <tr>
                        <th class="text-muted small text-uppercase">Role</th>
                        <td>
                            @php
                                $badgeMap = [
                                    'admin' => 'bg-danger',
                                    'shop_manager' => 'bg-primary',
                                    'cashier' => 'bg-warning text-dark',
                                    'sales' => 'bg-info text-dark',
                                    'customer' => 'bg-secondary',
                                ];
                                $roleBadge = $badgeMap[$user->role] ?? 'bg-secondary';
                            @endphp
                            <span class="badge {{ $roleBadge }}">{{ $roleLabels[$user->role] ?? ucfirst(str_replace('_', ' ', $user->role)) }}</span>
                        </td>
                    </tr>
                    <tr>
                        <th class="text-muted small text-uppercase">Status</th>
                        <td>
                            @if($user->trashed())
                                <span class="badge bg-danger">Inactive</span>
                            @else
                                <span class="badge bg-success">Active</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th class="text-muted small text-uppercase">Phone</th>
                        <td>{{ $user->phone ?? 'Not provided' }}</td>
                    </tr>
                    <tr>
                        <th class="text-muted small text-uppercase">Address</th>
                        <td>{{ $user->address ?? 'Not provided' }}</td>
                    </tr>
                    <tr>
                        <th class="text-muted small text-uppercase">Email Verified</th>
                        <td>
                            @if($user->email_verified_at)
                                <span class="badge bg-success">Verified</span>
                                <small class="text-muted ms-2">{{ $user->email_verified_at->format('M d, Y') }}</small>
                            @else
                                <span class="badge bg-warning text-dark">Not Verified</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th class="text-muted small text-uppercase">Joined</th>
                        <td>{{ $user->created_at->format('M d, Y H:i') }}</td>
                    </tr>
                </table>
            </div>
        </div>

        @if($user->orders->count() > 0)
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-receipt me-2"></i>Recent Orders</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th class="text-end">Total</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($user->orders->take(10) as $order)
                                    <tr>
                                        <td>#{{ $order->order_number ?? $order->id }}</td>
                                        <td>৳{{ number_format($order->total, 2) }}</td>
                                        <td>
                                            <span class="badge badge-status-{{ $order->status }}">
                                                {{ ucfirst($order->status) }}
                                            </span>
                                        </td>
                                        <td>{{ $order->created_at->format('M d, Y') }}</td>
                                        <td>
                                            <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-outline-info">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">Statistics</div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-3">
                    <span class="text-muted">Total Orders</span>
                    <strong>{{ $user->orders->count() }}</strong>
                </div>
                <div class="d-flex justify-content-between mb-3">
                    <span class="text-muted">Total Spent</span>
                    <strong>৳{{ number_format($user->orders->sum('total'), 2) }}</strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-muted">Last Order</span>
                    <strong>
                        @if($user->orders->count() > 0)
                            {{ $user->orders->sortByDesc('created_at')->first()->created_at->format('M d, Y') }}
                        @else
                            N/A
                        @endif
                    </strong>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
