@extends('admin.layouts.app')

@section('title', 'Loyalty Redemptions')
@section('page-title', 'Loyalty Redemptions')

@section('content')
<div class="card mb-4">
    <div class="card-header">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-funnel me-2"></i>Filters</h6>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.loyalty.redemptions') }}" method="GET">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small text-muted">Search User</label>
                    <input type="text" class="form-control form-control-sm" name="search" value="{{ request('search') }}" placeholder="Name, email, coupon...">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Status</label>
                    <select class="form-select form-select-sm" name="status">
                        <option value="">All Status</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="applied" {{ request('status') === 'applied' ? 'selected' : '' }}>Applied</option>
                        <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expired</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Reward</label>
                    <select class="form-select form-select-sm" name="reward_id">
                        <option value="">All Rewards</option>
                        @foreach($rewards as $reward)
                            <option value="{{ $reward->id }}" {{ request('reward_id') == $reward->id ? 'selected' : '' }}>
                                {{ $reward->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Date From</label>
                    <input type="date" class="form-control form-control-sm" name="date_from" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-sm btn-primary me-2">
                        <i class="bi bi-search"></i> Search
                    </button>
                    <a href="{{ route('admin.loyalty.redemptions') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-x"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-gift me-2"></i>All Redemptions ({{ $redemptions->total() }})</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Date</th>
                        <th>User</th>
                        <th>Reward</th>
                        <th>Points Used</th>
                        <th>Coupon Code</th>
                        <th>Discount</th>
                        <th>Order</th>
                        <th>Status</th>
                        <th>Expires</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($redemptions as $redemption)
                        <tr>
                            <td>{{ $redemption->id }}</td>
                            <td class="small">{{ $redemption->created_at->format('M d, Y') }}</td>
                            <td>
                                <a href="{{ route('admin.loyalty.members.show', $redemption->user) }}">
                                    {{ $redemption->user->name }}
                                </a>
                            </td>
                            <td>
                                {{ $redemption->reward->name ?? 'Unknown' }}
                                <div class="small text-muted">{{ ucfirst(str_replace('_', ' ', $redemption->reward->reward_type ?? '')) }}</div>
                            </td>
                            <td class="text-danger fw-semibold">-{{ number_format($redemption->points_used) }}</td>
                            <td>
                                @if($redemption->coupon_code)
                                    <code class="bg-light p-1 rounded">{{ $redemption->coupon_code }}</code>
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                @if($redemption->discount_value)
                                    ৳{{ number_format($redemption->discount_value, 2) }}
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                @if($redemption->order_id)
                                    <a href="{{ route('admin.orders.show', $redemption->order_id) }}" class="btn btn-sm btn-outline-info">
                                        #{{ $redemption->order?->order_number ?? $redemption->order_id }}
                                    </a>
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                @switch($redemption->status)
                                    @case('pending')
                                        <span class="badge bg-warning text-dark">Pending</span>
                                        @break
                                    @case('applied')
                                        <span class="badge bg-success">Applied</span>
                                        @break
                                    @case('expired')
                                        <span class="badge bg-secondary">Expired</span>
                                        @break
                                    @case('cancelled')
                                        <span class="badge bg-danger">Cancelled</span>
                                        @break
                                @endswitch
                            </td>
                            <td class="small">
                                @if($redemption->expires_at)
                                    @if($redemption->expires_at < now() && $redemption->status === 'pending')
                                        <span class="text-danger">{{ $redemption->expires_at->format('M d, Y') }}</span>
                                    @else
                                        {{ $redemption->expires_at->format('M d, Y') }}
                                    @endif
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">
                                <i class="bi bi-gift fs-1 d-block mb-2"></i>
                                No redemptions found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $redemptions->links() }}
    </div>
</div>
@endsection
