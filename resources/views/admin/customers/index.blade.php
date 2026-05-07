@extends('admin.layouts.app')

@section('title', 'Customers')
@section('page-title', 'Customers (Loyalty Program)')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Customer Analytics</h5>
        <a href="{{ route('admin.customer-groups.index') }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-award me-1"></i> Manage Customer Groups
        </a>
    </div>
    <div class="card-body border-bottom">
        <form action="{{ route('admin.customers.index') }}" method="GET" class="d-flex gap-2 w-md-50">
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by phone, name, or email..." value="{{ $search }}">
            <button type="submit" class="btn btn-primary btn-sm">Search</button>
            @if($search)
                <a href="{{ route('admin.customers.index') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
            @endif
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Phone Number</th>
                    <th>Latest Name</th>
                    <th>Total Orders</th>
                    <th>Total Spent</th>
                    <th>Loyalty Group</th>
                    <th>Last Order Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $customer)
                    <tr>
                        <td>
                            <strong>{{ $customer->phone }}</strong>
                        </td>
                        <td>
                            {{ $customer->latest_name }}<br>
                            <small class="text-muted">{{ $customer->latest_email }}</small>
                        </td>
                        <td>
                            <span class="badge bg-secondary rounded-pill">{{ $customer->total_orders }}</span>
                        </td>
                        <td>৳{{ number_format($customer->total_spent, 2) }}</td>
                        <td>
                            @if($customer->group)
                                <span class="badge bg-success">{{ $customer->group->name }}</span>
                                <br><small class="text-muted">{{ $customer->group->discount_percentage }}% Off</small>
                            @else
                                <span class="badge bg-light text-dark">Regular</span>
                            @endif
                        </td>
                        <td>{{ \Carbon\Carbon::parse($customer->last_order_date)->format('M d, Y h:i A') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">No customers found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($customers->hasPages())
        <div class="card-footer border-top">
            {{ $customers->links() }}
        </div>
    @endif
</div>
@endsection
