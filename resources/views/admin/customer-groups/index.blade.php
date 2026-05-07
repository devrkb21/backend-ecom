@extends('admin.layouts.app')

@section('title', 'Customer Groups')
@section('page-title', 'Customer Groups (Loyalty Tiers)')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Customer Groups</h5>
        <div>
            <a href="{{ route('admin.customers.index') }}" class="btn btn-outline-secondary btn-sm me-2">
                <i class="bi bi-people me-1"></i> Customers List
            </a>
            <a href="{{ route('admin.customer-groups.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle me-1"></i> Create Group
            </a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Sort Order</th>
                    <th>Name</th>
                    <th>Rules (Min Orders / Min Spent)</th>
                    <th>Discount %</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($groups as $group)
                    <tr>
                        <td>{{ $group->sort_order }}</td>
                        <td>
                            <strong>{{ $group->name }}</strong>
                            @if($group->description)
                                <br><small class="text-muted">{{ Str::limit($group->description, 30) }}</small>
                            @endif
                        </td>
                        <td>
                            @if($group->min_order_count > 0)
                                <span class="badge bg-info text-dark">{{ $group->min_order_count }} Orders</span>
                            @endif
                            @if($group->min_total_spent > 0)
                                <span class="badge bg-warning text-dark">৳{{ number_format($group->min_total_spent, 2) }}</span>
                            @endif
                            @if($group->min_order_count == 0 && $group->min_total_spent == 0)
                                <span class="badge bg-secondary">Manual Only</span>
                            @endif
                        </td>
                        <td>{{ $group->discount_percentage }}%</td>
                        <td>
                            @if($group->is_active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-danger">Inactive</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('admin.customer-groups.edit', $group) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form action="{{ route('admin.customer-groups.destroy', $group) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Are you sure you want to delete this group?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">No customer groups found. Create one to start your loyalty program!</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
